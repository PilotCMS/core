<?php

namespace Pilot\Core\Livewire\Admin\Content;

use Illuminate\Support\Str;
use Livewire\Component;
use Pilot\Core\Models\Content;
use Pilot\Core\Models\ContentType;
use Pilot\Core\Models\Space;

class Create extends Component
{
    public $spaceId = null;

    public $parentId = null;

    public $type = 'page';

    public $contentTypeId = null;

    public $name = '';

    public $slug = '';

    public $status = 'draft';

    public function mount($parent_id = null, $type = 'page')
    {
        $space = Space::first();
        $this->spaceId = $space?->id;
        $this->parentId = $parent_id;
        $this->type = $type;

        if ($parent_id) {
            $parent = Content::find($parent_id);
            if ($parent && $parent->isFolder()) {
                $this->type = $type ?: 'page';
            }
        }
    }

    protected $rules = [
        'spaceId' => 'required|exists:spaces,id',
        'parentId' => 'nullable|exists:contents,id',
        'type' => 'required|in:page,folder',
        'contentTypeId' => 'nullable|exists:content_types,id',
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255',
    ];

    public function updatedName($value)
    {
        $this->slug = Str::slug($value);
    }

    public function save()
    {
        $this->validate([
            ...$this->rules,
            'slug' => 'required|string|max:255|unique:contents,slug,NULL,id,space_id,'.$this->spaceId,
        ]);

        $content = Content::create([
            'space_id' => $this->spaceId,
            'parent_id' => $this->parentId ?: null,
            'content_type_id' => $this->type === 'page' ? $this->contentTypeId : null,
            'type' => $this->type,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => 'draft',
            'workflow_status' => 'draft',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'published_at' => null,
        ]);

        session()->flash('toast', [
            'message' => 'Content created as a draft',
            'type' => 'success',
        ]);

        return $this->redirect(
            $content->isPage()
                ? route('admin.content.editor', $content)
                : route('admin.content.index'),
            navigate: true
        );
    }

    public function render()
    {
        return view('livewire.admin.content.create', [
            'spaces' => Space::all(),
            'contentTypes' => ContentType::query()->where('is_active', true)->orderBy('name')->get(),
            'parent' => $this->parentId ? Content::find($this->parentId) : null,
        ])->layout('layouts.admin');
    }
}
