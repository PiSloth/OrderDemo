<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Document Library</h1>
            <p class="text-sm text-slate-500 dark:text-slate-300">Company SOPs, workflows, job descriptions, announcements, and policies.</p>
        </div>

        <a wire:navigate href="{{ route('document.library.create') }}"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
            <x-icon name="plus" class="w-4 h-4 mr-2" />
            New Document
        </a>
    </div>

    @if (session('success'))
        <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <input type="text" placeholder="Search title, type, author, department..."
                class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                wire:model.live.debounce.300ms="search" />
        </div>

        <div>
            <select wire:model.live="department_id"
                class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                <option value="">All departments</option>
                @foreach (($departments ?? []) as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <select wire:model.live="company_document_type_id"
                class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                <option value="">All types</option>
                @foreach (($documentTypes ?? []) as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-hidden bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Title</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Type</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Department</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Announced</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Author</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                    @forelse ($documents as $doc)
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
                                <a wire:navigate href="{{ route('document.library.show', $doc) }}" class="text-primary-600 hover:text-primary-700">
                                    {{ $doc->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $doc->type?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $doc->department?->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $doc->announced_at?->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $doc->author?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                <a wire:navigate href="{{ route('document.library.edit', $doc) }}" class="text-primary-600 hover:text-primary-700">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-sm text-center text-slate-500 dark:text-slate-300">
                                No documents found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 bg-white border-t border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            {{ $documents->links() }}
        </div>
    </div>
</div>
