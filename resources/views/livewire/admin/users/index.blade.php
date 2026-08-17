<div class="cms-drawer-page flex min-h-0 w-full min-w-0 flex-1 flex-col bg-gray-50">
    <x-jaunt.shell.dynamic-header :title="'Users & Roles'" subtitle="Manage team members and permissions." top="0px" as="header" scroll-target="#users-list-scroll" aria-label="Page header">
        <x-slot:actions>
        <div class="cms-actions pb-0.5">
            <button type="button" wire:click="openCreateUser" class="cms-btn cms-btn-primary">
                <x-jaunt.icon name="plus" size="sm" />
                New user
            </button>
        </div>
        </x-slot:actions>
    </x-jaunt.shell.dynamic-header>

    <div class="flex min-h-0 flex-1">
        <main id="users-list-scroll" class="min-w-0 flex-1 overflow-y-auto">
            <div class="w-full space-y-8 p-6 md:p-8">
                <section class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Users</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($users->count()) }}</p>
                    </div>

                    @foreach($roles as $role)
                        <button
                            type="button"
                            wire:click="$set('search', '{{ $role->name }}')"
                            class="rounded-sm border border-default bg-card p-4 text-left shadow-xs transition-[background-color,border-color,box-shadow] hover:border-strong hover:bg-hover hover:shadow-sm"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-900">{{ $role->name }}</p>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">{{ $role->users_count }}</span>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ $role->permissions->count() }} permissions</p>
                        </button>
                    @endforeach
                </section>

                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <flux:heading size="md">Team members</flux:heading>
                            <flux:text class="mt-1 text-sm text-slate-500">Create users, assign roles, and control admin access.</flux:text>
                        </div>

                        <div class="w-full md:w-80">
                            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search users or roles..." icon="magnifying-glass" />
                        </div>
                    </div>

                    <flux:table>
                        <flux:table.head>
                            <flux:table.row>
                                <flux:table.header>User</flux:table.header>
                                <flux:table.header>Role</flux:table.header>
                                <flux:table.header>Email verified</flux:table.header>
                                <flux:table.header>Created</flux:table.header>
                                <flux:table.header class="text-right">Actions</flux:table.header>
                            </flux:table.row>
                        </flux:table.head>

                        <flux:table.body>
                            @forelse($users as $user)
                                <flux:table.row wire:key="user-row-{{ $user->id }}" class="transition-colors hover:bg-slate-50">
                                    <flux:table.cell>
                                        <button type="button" wire:click="selectUser({{ $user->id }})" class="flex items-center gap-3 text-left">
                                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                                                {{ $user->initials() }}
                                            </span>
                                            <span>
                                                <span class="block font-medium text-slate-900">{{ $user->name }}</span>
                                                <span class="block text-sm text-slate-500">{{ $user->email }}</span>
                                            </span>
                                        </button>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        @forelse($user->roles as $role)
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-sm text-slate-400">No role</span>
                                        @endforelse
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        @if($user->email_verified_at)
                                            <span class="inline-flex items-center gap-1 text-sm text-emerald-700">
                                                <x-jaunt.icon name="circle-check" size="sm" />
                                                Verified
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-sm text-amber-700">
                                                <x-jaunt.icon name="circle-alert" size="sm" />
                                                Pending
                                            </span>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <span class="text-sm text-slate-500">{{ $user->created_at->format('M d, Y') }}</span>
                                    </flux:table.cell>

                                    <flux:table.cell class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <flux:button wire:click="selectUser({{ $user->id }})" size="sm" variant="ghost">
                                                <flux:icon.pencil class="size-4" />
                                                Edit
                                            </flux:button>

                                            @if($user->is(auth()->user()))
                                                <flux:button size="sm" variant="ghost" class="text-slate-300" disabled>
                                                    <flux:icon.trash class="size-4" />
                                                </flux:button>
                                            @else
                                                <flux:button
                                                    wire:click="deleteUser({{ $user->id }})"
                                                    wire:confirm="Delete {{ $user->name }}? This cannot be undone."
                                                    size="sm"
                                                    variant="ghost"
                                                    class="text-red-600 hover:text-red-700"
                                                >
                                                    <flux:icon.trash class="size-4" />
                                                </flux:button>
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @empty
                                <flux:table.row>
                                    <flux:table.cell colspan="5" class="py-16">
                                        <div class="flex flex-col items-center justify-center text-center">
                                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-100">
                                                <flux:icon.users class="size-7 text-blue-600" />
                                            </div>
                                            <flux:heading size="sm" class="mt-4">No users found</flux:heading>
                                            <flux:text class="mt-2 max-w-sm text-sm text-slate-500">Adjust your search or create a new user.</flux:text>
                                            <flux:button wire:click="openCreateUser" variant="primary" class="mt-6">
                                                <flux:icon.plus class="size-4" />
                                                Create user
                                            </flux:button>
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforelse
                        </flux:table.body>
                    </flux:table>
                </section>
            </div>
        </main>

        <aside class="cms-drawer" aria-label="Details">
            <div class="cms-drawer-header">
                <h2 class="cms-drawer-title">{{ $selectedUser ? 'Edit user' : 'Role details' }}</h2>
            </div>

            <div class="cms-drawer-body">
                @if($selectedUser)
                    <form wire:submit="updateSelectedUser" class="space-y-5">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-5">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-base font-bold text-blue-700">
                                {{ $selectedUser->initials() }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $selectedUser->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $selectedUser->email }}</p>
                            </div>
                        </div>

                        <flux:error name="selectedUserId" />

                        <flux:field>
                            <flux:label>Name</flux:label>
                            <flux:input wire:model="editName" />
                            <flux:error name="editName" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Email</flux:label>
                            <flux:input type="email" wire:model="editEmail" />
                            <flux:error name="editEmail" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Password</flux:label>
                            <flux:input type="password" wire:model="editPassword" placeholder="Leave blank to keep current password" />
                            <flux:error name="editPassword" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Role</flux:label>
                            @if($selectedUser->is(auth()->user()))
                                <flux:select wire:model="editRoleName" disabled>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:select wire:model="editRoleName">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </flux:select>
                            @endif
                            <flux:error name="editRoleName" />
                            @if($selectedUser->is(auth()->user()))
                                <p class="mt-2 text-xs text-slate-500">You cannot change your own role from this screen.</p>
                            @endif
                        </flux:field>

                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-5">
                            @if($selectedUser->is(auth()->user()))
                                <flux:button type="button" variant="ghost" class="text-slate-300" disabled>
                                    Delete
                                </flux:button>
                            @else
                                <flux:button
                                    type="button"
                                    wire:click="deleteSelectedUser"
                                    wire:confirm="Delete this user? This cannot be undone."
                                    variant="ghost"
                                    class="text-red-600 hover:text-red-700"
                                >
                                    Delete
                                </flux:button>
                            @endif

                            <flux:button type="submit" variant="primary">
                                Save changes
                            </flux:button>
                        </div>
                    </form>
                @else
                    <div class="space-y-5">
                        <p class="text-sm text-slate-500">Select a user to edit their profile and role. Current roles are seeded from Pilot permissions.</p>

                        @foreach($roles as $role)
                            <section class="rounded-lg border border-slate-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-slate-900">{{ $role->name }}</h3>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">{{ $role->users_count }} users</span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach($role->permissions as $permission)
                                        <span class="rounded-full bg-slate-50 px-2 py-1 text-xs text-slate-600">{{ $permission->name }}</span>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>
    </div>

    <flux:modal wire:model="showCreateModal">
        <div class="space-y-1">
            <flux:heading size="lg">Create user</flux:heading>
            <flux:text class="text-sm text-slate-500">Add a team member and assign their starting role.</flux:text>
        </div>

        <form wire:submit="createUser" class="mt-5 space-y-4">
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="name" placeholder="Jane Doe" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email</flux:label>
                <flux:input type="email" wire:model="email" placeholder="jane@example.com" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Password</flux:label>
                <flux:input type="password" wire:model="password" />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Role</flux:label>
                <flux:select wire:model="roleName">
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="roleName" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showCreateModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    Create user
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
