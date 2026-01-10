<div>
    {{-- The Master doesn't talk, he acts. --}}
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Email List</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Create, edit, archive, restore, and delete entries.</p>
        </div>

        <div class="flex items-center gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="checkbox" class="rounded border-slate-300" wire:model.live="archived" />
                Show Archived
            </label>

            <a wire:navigate href="{{ route('document.email-list.create') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <x-icon name="plus" class="w-4 h-4 mr-2" />
                New
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:max-w-md">
            <input type="text" placeholder="Search name, email, department..."
                class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                wire:model.live.debounce.300ms="search" />
        </div>
    </div>

    <div class="overflow-hidden bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Name</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Email</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Department</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                    @forelse ($emailLists as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $row->user_name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $row->email }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $row->department?->name }}</td>
                            <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                @if (!$archived)
                                    <a wire:navigate href="{{ route('document.email-list.edit', $row) }}"
                                        class="text-primary-600 hover:text-primary-700">Edit</a>
                                    <span class="mx-2 text-slate-300">|</span>
                                    <button type="button" class="text-amber-600 hover:text-amber-700"
                                        wire:click="archive({{ $row->id }})"
                                        onclick="if(!confirm('Archive this entry?')){event.preventDefault();event.stopImmediatePropagation();}">Archive</button>
                                @else
                                    <button type="button" class="text-primary-600 hover:text-primary-700"
                                        wire:click="restore({{ $row->id }})">Restore</button>
                                    <span class="mx-2 text-slate-300">|</span>
                                    <button type="button" class="text-red-600 hover:text-red-700"
                                        wire:click="deletePermanently({{ $row->id }})"
                                        onclick="if(!confirm('Delete permanently? This cannot be undone.')){event.preventDefault();event.stopImmediatePropagation();}">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-sm text-center text-slate-500 dark:text-slate-300">
                                No entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 bg-white border-t border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            {{ $emailLists->links() }}
        </div>
    </div>
</div>
