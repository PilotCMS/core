<?php

namespace Pilot\Core\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    public string $search = '';

    public bool $showCreateModal = false;

    public ?int $selectedUserId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $roleName = 'Viewer';

    public string $editName = '';

    public string $editEmail = '';

    public string $editPassword = '';

    public string $editRoleName = '';

    public function openCreateUser(): void
    {
        $this->authorizeUserManagement();
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function createUser(): void
    {
        $this->authorizeUserManagement();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'roleName' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->assignRole($validated['roleName']);

        $this->selectedUserId = $user->id;
        $this->loadSelectedUserForm($user);
        $this->resetCreateForm();
        $this->showCreateModal = false;

        $this->dispatch('user-created');
    }

    public function selectUser(int $userId): void
    {
        $user = User::with('roles')->findOrFail($userId);

        $this->selectedUserId = $user->id;
        $this->loadSelectedUserForm($user);
    }

    public function updateSelectedUser(): void
    {
        $this->authorizeUserManagement();

        if (! $this->selectedUserId) {
            return;
        }

        $user = User::with('roles')->findOrFail($this->selectedUserId);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'editPassword' => ['nullable', 'string', Password::defaults()],
            'editRoleName' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $user->fill([
            'name' => $validated['editName'],
            'email' => $validated['editEmail'],
        ]);

        if (filled($validated['editPassword'])) {
            $user->password = $validated['editPassword'];
        }

        $user->save();

        if ($user->isNot(auth()->user())) {
            $user->syncRoles([$validated['editRoleName']]);
        }

        $this->loadSelectedUserForm($user->fresh('roles'));

        $this->dispatch('user-updated');
    }

    public function deleteUser(int $userId): void
    {
        $this->selectUser($userId);
        $this->deleteSelectedUser();
    }

    public function deleteSelectedUser(): void
    {
        $this->authorizeUserManagement();

        if (! $this->selectedUserId) {
            return;
        }

        $user = User::findOrFail($this->selectedUserId);

        if ($user->is(auth()->user())) {
            $this->addError('selectedUserId', 'You cannot delete your own account.');

            return;
        }

        $user->delete();

        $this->selectedUserId = null;
        $this->resetEditForm();

        $this->dispatch('user-deleted');
    }

    public function render(): View
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhereHas('roles', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderBy('name')
            ->get();

        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'selectedUser' => $this->selectedUserId
                ? User::with('roles.permissions')->find($this->selectedUserId)
                : null,
        ])
            ->layout('layouts.admin');
    }

    protected function resetCreateForm(): void
    {
        $this->resetValidation();
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->roleName = Role::query()->where('name', 'Viewer')->exists() ? 'Viewer' : (Role::query()->orderBy('name')->value('name') ?? '');
    }

    protected function resetEditForm(): void
    {
        $this->editName = '';
        $this->editEmail = '';
        $this->editPassword = '';
        $this->editRoleName = '';
    }

    protected function loadSelectedUserForm(User $user): void
    {
        $this->resetValidation();
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editPassword = '';
        $this->editRoleName = $user->roles->first()?->name ?? (Role::query()->where('name', 'Viewer')->exists() ? 'Viewer' : '');
    }

    protected function authorizeUserManagement(): void
    {
        abort_unless(auth()->user()?->can('manage users'), 403);
    }
}
