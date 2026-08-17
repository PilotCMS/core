<?php

namespace Pilot\Core\Livewire\Admin\Spaces;

use Illuminate\Support\Str;
use Livewire\Component;
use Pilot\Core\Models\CmsSetting;
use Pilot\Core\Models\Space;

class Edit extends Component
{
    public Space $space;

    public $name = '';

    public $slug = '';

    public array $previewTargets = [];

    public string $previewSecret = '';

    public function mount(Space $space)
    {
        $this->space = $space;
        $this->name = $space->name;
        $this->slug = $space->slug;
        $this->previewSecret = CmsSetting::previewSecret();
        $this->previewTargets = $space->previewTargets()
            ->get()
            ->map(fn ($target): array => [
                'id' => $target->id,
                'name' => $target->name,
                'url' => $target->url,
                'is_default' => $target->is_default,
            ])
            ->values()
            ->all();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:spaces,slug,'.$this->space->id,
            'previewTargets' => ['array'],
            'previewTargets.*.id' => ['nullable', 'integer', 'exists:space_preview_targets,id'],
            'previewTargets.*.name' => ['required', 'string', 'max:255'],
            'previewTargets.*.url' => ['required', 'url', 'max:2048'],
            'previewTargets.*.is_default' => ['boolean'],
        ];
    }

    public function updatedName($value)
    {
        if ($this->slug === Str::slug($this->space->name)) {
            $this->slug = Str::slug($value);
        }
    }

    public function addPreviewTarget(): void
    {
        $this->previewTargets[] = [
            'id' => null,
            'name' => '',
            'url' => '',
            'is_default' => count($this->previewTargets) === 0,
        ];
    }

    public function removePreviewTarget(int $index): void
    {
        unset($this->previewTargets[$index]);
        $this->previewTargets = array_values($this->previewTargets);
    }

    public function markDefaultPreviewTarget(int $index): void
    {
        foreach ($this->previewTargets as $targetIndex => $target) {
            $this->previewTargets[$targetIndex]['is_default'] = $targetIndex === $index;
        }
    }

    public function save()
    {
        $this->validate();

        $this->space->update([
            'name' => $this->name,
            'slug' => $this->slug,
        ]);

        $this->syncPreviewTargets();

        session()->flash('toast', ['message' => 'Space saved', 'type' => 'success']);

        return $this->redirect(route('admin.spaces.index'), navigate: true);
    }

    protected function syncPreviewTargets(): void
    {
        $seenIds = [];

        foreach (array_values($this->previewTargets) as $index => $target) {
            $previewTarget = $this->space->previewTargets()->updateOrCreate(
                ['id' => $target['id'] ?? null],
                [
                    'name' => $target['name'],
                    'url' => rtrim($target['url'], '/'),
                    'sort_order' => $index,
                    'is_default' => (bool) ($target['is_default'] ?? false),
                ],
            );

            $seenIds[] = $previewTarget->id;
        }

        $this->space->previewTargets()
            ->when($seenIds !== [], fn ($query) => $query->whereNotIn('id', $seenIds))
            ->delete();

        if (! $this->space->previewTargets()->where('is_default', true)->exists()) {
            $this->space->previewTargets()->orderBy('sort_order')->first()?->update(['is_default' => true]);
        }
    }

    public function render()
    {
        return view('livewire.admin.spaces.edit')
            ->layout('layouts.admin');
    }
}
