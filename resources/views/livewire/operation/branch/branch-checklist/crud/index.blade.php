<div class="mx-auto max-w-7xl space-y-5 px-4 py-5">
    <section class="rounded-3xl bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-slate-900">Checklist Config</h1>
            <button wire:click="openCreate" type="button" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Create</button>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Branch</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Active</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($items as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm">{{ $item->title }}</td>
                            <td class="px-4 py-3 text-sm">{{ $item->branch?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $item->department?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $item->location?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $item->is_active ? 'Yes' : 'No' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex gap-2">
                                    <button wire:click="openEdit({{ $item->id }})" class="rounded-xl bg-amber-500 px-3 py-1 text-white">Edit</button>
                                    <button wire:click="delete({{ $item->id }})" class="rounded-xl bg-rose-500 px-3 py-1 text-white">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-3">{{ $items->links() }}</div>
    </section>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 p-4 backdrop-blur-md sm:items-center">
            <div class="w-full max-w-xl rounded-3xl bg-white p-5 shadow-2xl">
                <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit' : 'Create' }} Checklist</h2>
                <div class="mt-4 space-y-3">
                    <x-input label="Title" wire:model.defer="title" />
                    <x-textarea label="Description" wire:model.defer="description" />
                    <x-select label="Branch" placeholder="Optional" :options="$branches" option-label="name" option-value="id" wire:model.live="branch_id" />
                    <x-select label="Department" placeholder="Optional" :options="$departments" option-label="name" option-value="id" wire:model.live="department_id" />
                    <x-select label="Location" placeholder="Optional" :options="$locations" option-label="name" option-value="id" wire:model.live="location_id" />
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model.defer="is_active">
                        Active
                    </label>
                </div>

                <div class="mt-5 flex gap-3">
                    <button wire:click="save" class="rounded-2xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Save</button>
                    <button wire:click="$set('showForm', false)" class="rounded-2xl bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
