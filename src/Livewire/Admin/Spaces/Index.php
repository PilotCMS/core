<?php

namespace Pilot\Core\Livewire\Admin\Spaces;

use Livewire\Component;
use Pilot\Core\Models\Space;

class Index extends Component
{
    public function deleteSpace($id)
    {
        $space = Space::findOrFail($id);

        if ($space->contents()->exists()) {
            $this->addError('space', 'Delete or move this space\'s content before deleting the space.');
            $this->dispatch('error', message: 'Delete or move this space\'s content before deleting the space.');

            return;
        }

        $space->delete();

        $this->dispatch('space-deleted');
    }

    public function render()
    {
        return view('livewire.admin.spaces.index', [
            'spaces' => Space::withCount(['contents', 'assets', 'datasources'])->get(),
        ])->layout('layouts.admin');
    }
}
