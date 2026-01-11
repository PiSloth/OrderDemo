<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">{{ $document->title }}</h1>
            <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                <span class="font-medium">Type:</span> {{ $document->type?->name ?? '-' }}
                <span class="mx-2 text-slate-300">|</span>
                <span class="font-medium">Department:</span> {{ $document->department?->name }}
                <span class="mx-2 text-slate-300">|</span>
                <span class="font-medium">Announced:</span> {{ $document->announced_at?->format('Y-m-d') ?? '-' }}
            </div>
            <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                <span class="font-medium">Written by:</span> {{ $document->author?->name ?? '-' }}
                <span class="mx-2 text-slate-300">|</span>
                <span class="font-medium">Last edited by:</span> {{ $document->lastEditor?->name ?? '-' }}
                <span class="mx-2 text-slate-300">|</span>
                <span class="font-medium">Updated:</span> {{ $document->updated_at?->format('Y-m-d H:i') ?? '-' }}
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('document.library.index') }}"
                class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                Back
            </a>
            <a wire:navigate href="{{ route('document.library.edit', $document) }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                Edit
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-6 prose prose-document dark:prose-invert max-w-none">
            {!! $document->body !!}
        </div>
    </div>

    <div class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Edit History</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-300">Each save creates a revision snapshot.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-900/40">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Version</th>
                            <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Edited By</th>
                            <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Edited At</th>
                            <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Department</th>
                            <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Type</th>
                            <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Announcement</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                        @forelse ($document->revisions as $rev)
                            <tr>
                                <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">v{{ $rev->version }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $rev->editor?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $rev->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $rev->department?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $rev->type?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ $rev->announced_at?->format('Y-m-d') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-sm text-center text-slate-500 dark:text-slate-300">No revisions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
