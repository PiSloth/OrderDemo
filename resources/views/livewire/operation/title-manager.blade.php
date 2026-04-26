<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('message') }}
        </div>
    @endif

    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white">
        <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Operations</p>
        <h1 class="mt-2 text-3xl font-semibold">Daily note titles</h1>
        <p class="mt-3 max-w-3xl text-sm text-slate-200">
            Create and maintain the topics your team will use for location-based daily operation notes.
        </p>
    </section>

    <section class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid w-full gap-3 md:grid-cols-2 lg:max-w-3xl">
                <div>
                    <label class="text-sm font-medium text-slate-700">Search title</label>
                    <input type="text" wire:model.live.debounce.300ms="searchTitle"
                        class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700"
                        placeholder="Search by title name">
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Search remark</label>
                    <input type="text" wire:model.live.debounce.300ms="searchRemark"
                        class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700"
                        placeholder="Search by remark">
                </div>
            </div>

            <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Create title</span>
            </button>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Title</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Remark</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($titles as $title)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ $title->name }}</p>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-xs font-medium {{ $title->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $title->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $title->daily_notes_count }} notes -
                                        {{ $title->created_at->format('Y-m-d') }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $title->remark ?: '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button type="button" wire:click="openEditModal({{ $title->id }})"
                                            class="rounded-xl border border-slate-300 p-2 text-slate-700 transition hover:bg-slate-100"
                                            title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.586-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.414-8.586z" />
                                            </svg>
                                        </button>

                                        <button type="button" wire:click="toggleActive({{ $title->id }})"
                                            class="rounded-xl border border-slate-300 p-2 text-slate-700 transition hover:bg-slate-100"
                                            title="{{ $title->is_active ? 'Disable' : 'Enable' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4 12a8 8 0 0116 0M4 12a8 8 0 0016 0M12 8v8m-4-4h8" />
                                            </svg>
                                        </button>

                                        <button type="button" wire:click="delete({{ $title->id }})"
                                            wire:confirm="Are you sure you want to delete this title?"
                                            class="rounded-xl border border-rose-200 p-2 text-rose-600 transition hover:bg-rose-50"
                                            title="Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-7 0h8" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No title records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $titles->links() }}
        </div>
    </section>

    @if ($showModal)
        <div class="fixed inset-0 z-40 overflow-y-auto bg-slate-950/60 p-4">
            <div class="mx-auto w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Operation title</p>
                        <h2 class="mt-1 text-2xl font-semibold text-slate-900">
                            {{ $editingId ? 'Update title' : 'Create title' }}
                        </h2>
                    </div>
                    <button type="button" wire:click="closeModal"
                        class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700">
                        Close
                    </button>
                </div>

                <div class="mt-6 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Title</label>
                        <input type="text" wire:model.defer="formName"
                            class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700"
                            placeholder="Example: Security, Cashier, Delivery">
                        @error('formName')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">Remark</label>
                        <textarea wire:model.defer="formRemark" rows="3"
                            class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700"
                            placeholder="Optional note about this title"></textarea>
                        @error('formRemark')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="button" wire:click="save"
                            class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white">
                            {{ $editingId ? 'Update' : 'Create' }}
                        </button>
                        <button type="button" wire:click="closeModal"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
