@props([
    'mobile' => false,
])

<div class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700 flex flex-col lg:max-h-[calc(100vh-7rem)]">
    <div class="p-4 border-b border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between gap-3">
            <div class="text-sm font-semibold text-slate-900 dark:text-white">Files</div>

            <div class="flex items-center gap-2">
                @if ($mobile)
                    <button type="button"
                        x-on:click="$dispatch('tree-close')"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                        aria-label="Close files panel">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                @endif

                <div class="inline-flex rounded-md border border-slate-300 dark:border-slate-600 overflow-hidden">
                    <button type="button" wire:click="$set('mode','department')"
                        class="px-3 py-1.5 text-xs font-medium {{ $mode === 'department' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                        By Department
                    </button>
                    <button type="button" wire:click="$set('mode','type')"
                        class="px-3 py-1.5 text-xs font-medium {{ $mode === 'type' ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300' }}">
                        By Type
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <input type="text" placeholder="Search..." wire:model.live.debounce.300ms="search"
                class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
        </div>
    </div>

    <div class="p-2 flex-1 overflow-y-auto">
        @php
            $tree = $mode === 'type' ? $treeByType : $treeByDepartment;
        @endphp

        @forelse ($tree as $groupName => $subGroups)
            <details class="group" open>
                <summary class="flex items-center gap-2 px-2 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700 rounded">
                    <x-icon name="folder" class="w-4 h-4 text-slate-400" />
                    <span class="truncate">{{ $groupName }}</span>
                </summary>

                <div class="pl-4">
                    @foreach ($subGroups as $subName => $items)
                        <details class="group" open>
                            <summary class="flex items-center gap-2 px-2 py-1.5 text-sm text-slate-700 dark:text-slate-200 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700 rounded">
                                <x-icon name="folder" class="w-4 h-4 text-slate-400" />
                                <span class="truncate">{{ $subName }}</span>
                                <span class="ml-auto text-xs text-slate-400">{{ $items->count() }}</span>
                            </summary>

                            <ul class="pl-4 py-1 space-y-1">
                                @foreach ($items as $docItem)
                                    @php $active = (string) $docItem->id === (string) $doc; @endphp
                                    <li>
                                        <button type="button" wire:click="openDocument({{ $docItem->id }})"
                                            @if ($mobile) x-on:click="$dispatch('tree-close')" @endif
                                            class="w-full flex items-center gap-2 px-2 py-1.5 text-sm rounded text-left {{ $active ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-200' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                                            <x-icon name="document-text" class="w-4 h-4 {{ $active ? 'text-indigo-600 dark:text-indigo-200' : 'text-slate-400' }}" />
                                            <span class="truncate">{{ $docItem->title }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </div>
            </details>
        @empty
            <div class="p-6 text-sm text-slate-500 dark:text-slate-300">No documents found.</div>
        @endforelse
    </div>
</div>
