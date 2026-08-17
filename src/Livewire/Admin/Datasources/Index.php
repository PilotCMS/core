<?php

namespace Pilot\Core\Livewire\Admin\Datasources;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Pilot\Core\Models\Datasource;
use Pilot\Core\Models\DatasourceEntry;
use Pilot\Core\Models\Space;

class Index extends Component
{
    public ?int $spaceId = null;

    public string $search = '';

    public ?int $selectedDatasourceId = null;

    public bool $showCreateModal = false;

    public string $name = '';

    public string $slug = '';

    public string $editName = '';

    public string $editSlug = '';

    public string $newEntryKey = '';

    public string $newEntryValue = '';

    public ?int $editingEntryId = null;

    public string $editEntryKey = '';

    public string $editEntryValue = '';

    public function mount(): void
    {
        $this->spaceId = Space::query()->orderBy('name')->value('id');
    }

    public function updatedSpaceId(): void
    {
        $this->selectedDatasourceId = null;
        $this->resetDatasourceForm();
        $this->resetEntryForms();
    }

    public function updatedName(string $value): void
    {
        if ($this->slug === '') {
            $this->slug = Str::slug($value);
        }
    }

    public function openCreateDatasource(): void
    {
        $this->authorizeDatasourceManagement();
        $this->resetValidation();
        $this->name = '';
        $this->slug = '';
        $this->showCreateModal = true;
    }

    public function createDatasource(): void
    {
        $this->authorizeDatasourceManagement();

        $validated = $this->validate([
            'spaceId' => ['required', 'integer', Rule::exists('spaces', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('datasources', 'slug')->where('space_id', $this->spaceId),
            ],
        ]);

        $datasource = Datasource::create([
            'space_id' => $validated['spaceId'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        $this->selectedDatasourceId = $datasource->id;
        $this->loadDatasourceForm($datasource);
        $this->name = '';
        $this->slug = '';
        $this->showCreateModal = false;

        $this->dispatch('datasource-created');
    }

    public function selectDatasource(int $datasourceId): void
    {
        $datasource = Datasource::query()
            ->where('space_id', $this->spaceId)
            ->findOrFail($datasourceId);

        $this->selectedDatasourceId = $datasource->id;
        $this->loadDatasourceForm($datasource);
        $this->resetEntryForms();
    }

    public function saveDatasource(): void
    {
        $this->authorizeDatasourceManagement();

        if (! $this->selectedDatasourceId) {
            return;
        }

        $datasource = Datasource::query()
            ->where('space_id', $this->spaceId)
            ->findOrFail($this->selectedDatasourceId);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editSlug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('datasources', 'slug')
                    ->where('space_id', $this->spaceId)
                    ->ignore($datasource->id),
            ],
        ]);

        $datasource->update([
            'name' => $validated['editName'],
            'slug' => $validated['editSlug'],
        ]);

        $this->loadDatasourceForm($datasource->fresh());

        $this->dispatch('datasource-updated');
    }

    public function deleteDatasource(): void
    {
        $this->authorizeDatasourceManagement();

        if (! $this->selectedDatasourceId) {
            return;
        }

        Datasource::query()
            ->where('space_id', $this->spaceId)
            ->findOrFail($this->selectedDatasourceId)
            ->delete();

        $this->selectedDatasourceId = null;
        $this->resetDatasourceForm();
        $this->resetEntryForms();

        $this->dispatch('datasource-deleted');
    }

    public function createEntry(): void
    {
        $this->authorizeDatasourceManagement();

        $datasource = $this->selectedDatasource();

        if (! $datasource) {
            return;
        }

        $validated = $this->validate([
            'newEntryKey' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('datasource_entries', 'key')->where('datasource_id', $datasource->id),
            ],
            'newEntryValue' => ['required', 'string', 'max:255'],
        ]);

        DatasourceEntry::create([
            'datasource_id' => $datasource->id,
            'key' => $validated['newEntryKey'],
            'value' => ['en' => $validated['newEntryValue']],
            'order' => ($datasource->entries()->max('order') ?? -1) + 1,
        ]);

        $this->newEntryKey = '';
        $this->newEntryValue = '';

        $this->dispatch('datasource-entry-created');
    }

    public function editEntry(int $entryId): void
    {
        $entry = $this->entryForSelectedDatasource($entryId);

        $this->editingEntryId = $entry->id;
        $this->editEntryKey = $entry->key;
        $this->editEntryValue = $entry->value['en'] ?? '';
        $this->resetValidation();
    }

    public function saveEntry(): void
    {
        $this->authorizeDatasourceManagement();

        if (! $this->editingEntryId) {
            return;
        }

        $entry = $this->entryForSelectedDatasource($this->editingEntryId);

        $validated = $this->validate([
            'editEntryKey' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('datasource_entries', 'key')
                    ->where('datasource_id', $entry->datasource_id)
                    ->ignore($entry->id),
            ],
            'editEntryValue' => ['required', 'string', 'max:255'],
        ]);

        $entry->update([
            'key' => $validated['editEntryKey'],
            'value' => ['en' => $validated['editEntryValue']],
        ]);

        $this->resetEntryEditForm();

        $this->dispatch('datasource-entry-updated');
    }

    public function cancelEntryEdit(): void
    {
        $this->resetEntryEditForm();
    }

    public function deleteEntry(int $entryId): void
    {
        $this->authorizeDatasourceManagement();

        $this->entryForSelectedDatasource($entryId)->delete();
        $this->normalizeEntryOrder();

        if ($this->editingEntryId === $entryId) {
            $this->resetEntryEditForm();
        }

        $this->dispatch('datasource-entry-deleted');
    }

    public function moveEntryUp(int $entryId): void
    {
        $this->authorizeDatasourceManagement();
        $this->moveEntry($entryId, -1);
    }

    public function moveEntryDown(int $entryId): void
    {
        $this->authorizeDatasourceManagement();
        $this->moveEntry($entryId, 1);
    }

    public function render(): View
    {
        $spaces = Space::query()->orderBy('name')->get();

        $datasources = Datasource::query()
            ->with('space')
            ->withCount('entries')
            ->when($this->spaceId, fn ($query) => $query->where('space_id', $this->spaceId))
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%')
                        ->orWhereHas('entries', function ($query): void {
                            $query
                                ->where('key', 'like', '%'.$this->search.'%')
                                ->orWhere('value', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.datasources.index', [
            'spaces' => $spaces,
            'datasources' => $datasources,
            'selectedDatasource' => $this->selectedDatasource(),
            'entries' => $this->selectedDatasource()?->entries ?? collect(),
        ])
            ->layout('layouts.admin');
    }

    protected function loadDatasourceForm(Datasource $datasource): void
    {
        $this->resetValidation();
        $this->editName = $datasource->name;
        $this->editSlug = $datasource->slug;
    }

    protected function resetDatasourceForm(): void
    {
        $this->editName = '';
        $this->editSlug = '';
    }

    protected function resetEntryForms(): void
    {
        $this->newEntryKey = '';
        $this->newEntryValue = '';
        $this->resetEntryEditForm();
    }

    protected function resetEntryEditForm(): void
    {
        $this->editingEntryId = null;
        $this->editEntryKey = '';
        $this->editEntryValue = '';
        $this->resetValidation();
    }

    protected function selectedDatasource(): ?Datasource
    {
        if (! $this->selectedDatasourceId) {
            return null;
        }

        return Datasource::query()
            ->with(['entries', 'space'])
            ->where('space_id', $this->spaceId)
            ->find($this->selectedDatasourceId);
    }

    protected function entryForSelectedDatasource(int $entryId): DatasourceEntry
    {
        $datasource = $this->selectedDatasource();

        abort_unless($datasource, 404);

        return DatasourceEntry::query()
            ->where('datasource_id', $datasource->id)
            ->findOrFail($entryId);
    }

    protected function moveEntry(int $entryId, int $direction): void
    {
        $entries = $this->selectedDatasource()?->entries()->orderBy('order')->orderBy('id')->get();

        if (! $entries) {
            return;
        }

        $currentIndex = $entries->search(fn (DatasourceEntry $entry): bool => $entry->id === $entryId);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $currentIndex + $direction;

        if ($targetIndex < 0 || $targetIndex >= $entries->count()) {
            return;
        }

        $current = $entries[$currentIndex];
        $target = $entries[$targetIndex];

        [$current->order, $target->order] = [$target->order, $current->order];
        $current->save();
        $target->save();

        $this->normalizeEntryOrder();
    }

    protected function normalizeEntryOrder(): void
    {
        $this->selectedDatasource()?->entries()
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function (DatasourceEntry $entry, int $index): void {
                if ($entry->order !== $index) {
                    $entry->update(['order' => $index]);
                }
            });
    }

    protected function authorizeDatasourceManagement(): void
    {
        abort_unless(auth()->user()?->can('manage datasources'), 403);
    }
}
