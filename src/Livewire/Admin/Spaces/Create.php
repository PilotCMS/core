<?php

namespace Pilot\Core\Livewire\Admin\Spaces;

use Illuminate\Support\Str;
use Livewire\Component;
use Pilot\Core\Models\Space;

class Create extends Component
{
    public $name = '';

    public $slug = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:spaces,slug',
    ];

    public function updatedName($value)
    {
        $this->slug = Str::slug($value);
    }

    public function save()
    {
        $this->validate();

        $space = Space::create([
            'name' => $this->name,
            'slug' => $this->slug,
        ]);

        // Create default locale
        $space->locales()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
        ]);

        session()->flash('toast', ['message' => 'Space created', 'type' => 'success']);

        return $this->redirect(route('admin.spaces.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.spaces.create')
            ->layout('layouts.admin');
    }
}
