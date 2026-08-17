<?php

namespace Pilot\Core\Livewire\Admin\Content;

use Illuminate\Support\Str;
use Livewire\Component;
use Pilot\Core\Models\Content;

class Edit extends Component
{
    public Content $content;

    public $name = '';

    public $slug = '';

    public $status = 'draft';

    public $parentId = null;

    public function mount(Content $content)
    {
        $this->content = $content;
        $this->name = $content->name;
        $this->slug = $content->slug;
        $this->status = $content->status;
        $this->parentId = $content->parent_id;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:contents,slug,'.$this->content->id.',id,space_id,'.$this->content->space_id,
            'status' => 'required|in:draft,published',
            'parentId' => 'nullable|exists:contents,id',
        ];
    }

    public function updatedName($value)
    {
        if ($this->slug === Str::slug($this->content->name)) {
            $this->slug = Str::slug($value);
        }
    }

    public function save()
    {
        $this->validate();

        $this->content->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'parent_id' => $this->parentId ?: null,
            'published_at' => $this->status === 'published' && ! $this->content->published_at ? now() : $this->content->published_at,
            'updated_by' => auth()->id(),
        ]);

        session()->flash('toast', [
            'message' => $this->status === 'published' ? 'Content saved and published' : 'Content saved',
            'type' => 'success',
        ]);

        return $this->redirect(route('admin.content.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.content.edit', [
            'folders' => Content::where('space_id', $this->content->space_id)
                ->where('type', 'folder')
                ->where('id', '!=', $this->content->id)
                ->get(),
        ])->layout('layouts.admin');
    }
}
