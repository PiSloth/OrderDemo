<div
    x-data="{ sidebarOpen: false }"
    x-on:tree-close.window="sidebarOpen = false"
    class="grid gap-6 lg:grid-cols-12"
>
    <!-- Mobile: hamburger to open the tree -->
    <div class="lg:hidden">
        <button type="button"
            x-on:click="sidebarOpen = true"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
            aria-label="Open files panel">
            <x-icon name="menu" class="w-5 h-5" />
            Files
        </button>
    </div>

    <!-- Desktop sidebar -->
    <div class="hidden lg:block lg:col-span-4">
        <div class="lg:sticky lg:top-6">
            @include('livewire.document.library._tree')
        </div>
    </div>

    <!-- Mobile slide-over -->
    <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-50 lg:hidden" style="display:none">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="sidebarOpen = false"></div>
        <div
            class="absolute left-0 top-0 h-full w-[min(22rem,85vw)] p-4"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-4 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-x-4 opacity-0"
        >
            @include('livewire.document.library._tree', ['mobile' => true])
        </div>
    </div>

    <div class="lg:col-span-8">
        <div class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            @if (!$selected)
                <div class="p-10 text-center">
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">Select a document</div>
                    <div class="mt-2 text-sm text-slate-500 dark:text-slate-300">Choose an item from the tree to view it here.</div>
                    <div class="mt-4">
                        <a wire:navigate href="{{ route('document.library.create') }}"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                            <x-icon name="plus" class="w-4 h-4 mr-2" />
                            New Document
                        </a>
                    </div>
                </div>
            @else
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">{{ $selected->title }}</h2>
                            <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                <span class="font-medium">Department:</span> {{ $selected->department?->name ?? '-' }}
                                <span class="mx-2 text-slate-300">|</span>
                                <span class="font-medium">Type:</span> {{ $selected->type?->name ?? '-' }}
                                <span class="mx-2 text-slate-300">|</span>
                                <span class="font-medium">Announced:</span> {{ $selected->announced_at?->format('Y-m-d') ?? '-' }}
                            </div>
                            <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                <span class="font-medium">Written by:</span> {{ $selected->author?->name ?? '-' }}
                                <span class="mx-2 text-slate-300">|</span>
                                <span class="font-medium">Last edited by:</span> {{ $selected->lastEditor?->name ?? '-' }}
                                <span class="mx-2 text-slate-300">|</span>
                                <span class="font-medium">Updated:</span> {{ $selected->updated_at?->format('Y-m-d H:i') ?? '-' }}
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a wire:navigate href="{{ route('document.library.edit', $selected) }}"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                                Edit
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-6 prose prose-document dark:prose-invert max-w-none">
                    {!! $selected->body !!}
                </div>

                <div class="border-t border-slate-200 dark:border-slate-700">
                    <details>
                        <summary class="px-6 py-4 cursor-pointer text-sm font-semibold text-slate-900 dark:text-white">
                            Edit history ({{ $selected->revisions->count() }})
                        </summary>
                        <div class="px-6 pb-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="bg-slate-50 dark:bg-slate-900/40">
                                    <tr>
                                        <th class="px-3 py-2 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Version</th>
                                        <th class="px-3 py-2 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Edited By</th>
                                        <th class="px-3 py-2 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Edited At</th>
                                        <th class="px-3 py-2 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">Type</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                                    @foreach ($selected->revisions as $rev)
                                        <tr>
                                            <td class="px-3 py-2 text-sm text-slate-900 dark:text-white">v{{ $rev->version }}</td>
                                            <td class="px-3 py-2 text-sm text-slate-700 dark:text-slate-200">{{ $rev->editor?->name ?? '-' }}</td>
                                            <td class="px-3 py-2 text-sm text-slate-700 dark:text-slate-200">{{ $rev->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                            <td class="px-3 py-2 text-sm text-slate-700 dark:text-slate-200">{{ $rev->type?->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>
            @endif
        </div>
    </div>
</div>
