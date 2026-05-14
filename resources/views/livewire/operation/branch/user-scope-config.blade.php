<div class="space-y-4">
<section class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <div>
        <h3 class="text-lg font-semibold text-slate-900">User Scope Configuration</h3>
        <p class="mt-1 text-sm text-slate-500">Assign one or more scopes to each user.</p>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        <div>
            <label class="text-sm font-medium text-slate-700">Search user</label>
            <input type="text" wire:model.live.debounce.300ms="searchUser"
                class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700"
                placeholder="Search by name or email">
        </div>

        @if ($isSuperAdmin)
            <div>
                <x-select label="Department" placeholder="Select department" :options="$departments" option-label="name"
                    option-value="id" wire:model.live="filterDepartmentId" />
            </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Scope</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($users as $user)
                        <tr wire:key="scope-user-{{ $user->id }}">
                            <td class="px-4 py-3 text-sm text-slate-700">
                                <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $user->branch?->name ?? '-' }} / {{ $user->department?->name ?? '-' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <x-select placeholder="Select scopes" multiselect searchable :options="$scopeOptions"
                                    option-label="name" option-value="id" wire:model.live="userScopes.{{ $user->id }}" />
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" wire:click="saveUserScopes({{ $user->id }})"
                                    class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white hover:bg-slate-700">
                                    Save
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">Scope List</h3>
            <p class="mt-1 text-sm text-slate-500">Create and maintain available scopes.</p>
        </div>
        <button type="button" wire:click="openCreateScopeModal"
            class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-medium text-white hover:bg-slate-700">
            Add Scope
        </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Scope</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($scopes as $scope)
                        <tr wire:key="scope-row-{{ $scope->id }}">
                            <td class="px-4 py-3 text-sm text-slate-700">{{ $scope->name }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-medium {{ $scope->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $scope->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="openEditScopeModal({{ $scope->id }})"
                                        class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="toggleScopeActive({{ $scope->id }})"
                                        class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        {{ $scope->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button type="button" wire:click="deleteScope({{ $scope->id }})"
                                        wire:confirm="Delete this scope?"
                                        class="rounded-xl border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">No scopes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@if ($showScopeModal)
    <div class="fixed inset-0 z-40 overflow-y-auto bg-slate-950/60 p-4">
        <div class="mx-auto w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Scope</p>
                    <h2 class="mt-1 text-2xl font-semibold text-slate-900">
                        {{ $editingScopeId ? 'Update scope' : 'Create scope' }}
                    </h2>
                </div>
                <button type="button" wire:click="closeScopeModal"
                    class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">
                    Close
                </button>
            </div>

            <div class="mt-6 space-y-4">
                <div>
                    <label class="text-sm font-medium text-slate-700">Scope name</label>
                    <input type="text" wire:model.defer="scopeName"
                        class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700"
                        placeholder="Example: Sales, Service">
                    @error('scopeName')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button type="button" wire:click="saveScope"
                        class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">
                        {{ $editingScopeId ? 'Update' : 'Create' }}
                    </button>
                    <button type="button" wire:click="closeScopeModal"
                        class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
