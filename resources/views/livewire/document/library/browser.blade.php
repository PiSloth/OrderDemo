<div x-data="{ sidebarOpen: false, showSuggestions: false, openingDocument: false }"
    x-on:tree-close.window="sidebarOpen = false"
    x-on:document-open-finished.window="openingDocument = false; $nextTick(() => document.getElementById('document-viewer')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
    x-on:keydown.window.prevent.slash="$refs.searchInput?.focus(); showSuggestions = true"
    class="grid gap-6 lg:grid-cols-12">
    <div class="lg:hidden">
        <button type="button" x-on:click="sidebarOpen = true"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium rounded-md border border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
            aria-label="Open files panel">
            <x-icon name="menu" class="w-5 h-5" />
            Files
        </button>
    </div>

    <div class="hidden lg:block lg:col-span-4">
        <div class="lg:sticky lg:top-6">
            @include('livewire.document.library._tree')
        </div>
    </div>

    <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-50 lg:hidden" style="display:none">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="sidebarOpen = false"></div>
        <div class="absolute left-0 top-0 h-full w-[min(22rem,85vw)] p-4" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-4 opacity-0" x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="-translate-x-4 opacity-0">
            @include('livewire.document.library._tree', ['mobile' => true])
        </div>
    </div>

    <div class="lg:col-span-8 space-y-4">
        <section class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700 p-4">
            <div class="relative">
                <input x-ref="searchInput" type="text" wire:model.live.debounce.350ms="search"
                    x-on:focus="showSuggestions = true" x-on:click.outside="showSuggestions = false"
                    placeholder="Search by title, content, category, creator, department..."
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100">

                <div x-show="showSuggestions" x-cloak
                    class="absolute z-20 mt-2 w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                    @if (!empty($suggestions))
                        <div class="text-[11px] uppercase tracking-[0.14em] text-slate-500 px-2 py-1">Suggestions</div>
                        @foreach ($suggestions as $item)
                            <button type="button" wire:click='applySuggestion(@js($item))' x-on:click="showSuggestions = false"
                                class="w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ $item }}
                            </button>
                        @endforeach
                    @endif

                    @if (!empty($recentSearches))
                        <div class="mt-2 flex items-center justify-between px-2">
                            <div class="text-[11px] uppercase tracking-[0.14em] text-slate-500">Recent</div>
                            <button type="button" wire:click="clearRecentSearches" class="text-xs text-rose-600">Clear</button>
                        </div>
                        @foreach ($recentSearches as $item)
                            <button type="button" wire:click='applySuggestion(@js($item))' x-on:click="showSuggestions = false"
                                class="w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                                {{ $item }}
                            </button>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <select wire:model.live="department" class="rounded-xl border-slate-300 text-sm dark:bg-slate-900 dark:border-slate-700">
                    <option value="">All Departments</option>
                    @foreach ($filterOptions['departments'] as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="category" class="rounded-xl border-slate-300 text-sm dark:bg-slate-900 dark:border-slate-700">
                    <option value="">All Categories</option>
                    @foreach ($filterOptions['categories'] as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="creator" class="rounded-xl border-slate-300 text-sm dark:bg-slate-900 dark:border-slate-700">
                    <option value="">All Creators</option>
                    @foreach ($filterOptions['creators'] as $option)
                        <option value="{{ $option->id }}">{{ $option->name }}</option>
                    @endforeach
                </select>
                <input wire:model.live="version" type="number" min="1" placeholder="Min Version"
                    class="rounded-xl border-slate-300 text-sm dark:bg-slate-900 dark:border-slate-700">
                <input wire:model.live="publishedFrom" type="date"
                    class="rounded-xl border-slate-300 text-sm dark:bg-slate-900 dark:border-slate-700">
                <input wire:model.live="publishedTo" type="date"
                    class="rounded-xl border-slate-300 text-sm dark:bg-slate-900 dark:border-slate-700">
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                    <input type="checkbox" wire:model.live="announcementOnly" class="rounded border-slate-300 text-indigo-600">
                    Announcement only
                </label>
                <select wire:model.live="sort" class="rounded-xl border-slate-300 text-sm dark:bg-slate-900 dark:border-slate-700">
                    <option value="relevance">Relevance</option>
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="title_asc">Title A-Z</option>
                    <option value="title_desc">Title Z-A</option>
                </select>
                <button type="button" wire:click="clearFilters" class="px-3 py-2 text-sm rounded-xl border border-slate-300">
                    Clear Filters
                </button>
            </div>
        </section>

        <section class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
            <div wire:loading.delay wire:target="search,department,category,creator,announcementOnly,version,publishedFrom,publishedTo,sort"
                class="p-6 space-y-3">
                <div class="h-5 w-2/3 animate-pulse rounded bg-slate-200 dark:bg-slate-700"></div>
                <div class="h-4 w-full animate-pulse rounded bg-slate-200 dark:bg-slate-700"></div>
                <div class="h-4 w-5/6 animate-pulse rounded bg-slate-200 dark:bg-slate-700"></div>
            </div>

            <div wire:loading.remove wire:target="search,department,category,creator,announcementOnly,version,publishedFrom,publishedTo,sort"
                class="p-5 space-y-4">
                <div class="text-sm text-slate-500 dark:text-slate-300">
                    @if ($searchMeta['total'] > 0)
                        Showing {{ $searchMeta['from'] }}-{{ $searchMeta['to'] }} of {{ $searchMeta['total'] }} results
                    @else
                        No results found
                    @endif
                </div>

                @forelse ($searchResults as $item)
                    <article wire:key="search-result-{{ $item['id'] }}" class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <button type="button" wire:click="openDocument({{ $item['id'] }})" x-on:click="openingDocument = true"
                            class="text-left text-lg font-semibold text-indigo-700 hover:underline dark:text-indigo-300">
                            {!! $item['highlighted_title'] !!}
                        </button>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                            <span>{{ $item['department'] ?? '-' }}</span>
                            <span>•</span>
                            <span>{{ $item['category'] ?? '-' }}</span>
                            <span>•</span>
                            <span>v{{ $item['version'] }}</span>
                            <span>•</span>
                            <span>By {{ $item['creator'] ?? '-' }}</span>
                            <span>•</span>
                            <span>Published {{ $item['published_at'] ?? '-' }}</span>
                            @if ($item['is_announcement'])
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-amber-700">Announcement</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-700 dark:text-slate-200">{!! $item['snippet'] !!}</p>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-slate-700">
                        No matching documents for your search and filters.
                    </div>
                @endforelse

                @if ($searchMeta['last_page'] > 1)
                    <div>
                        {{ $searchPaginator->links() }}
                    </div>
                @endif
            </div>
        </section>

        <section x-show="openingDocument" x-cloak
            class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700 p-6">
            <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-200">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10" stroke-width="3" class="opacity-30"></circle>
                    <path d="M22 12a10 10 0 0 1-10 10" stroke-width="3"></path>
                </svg>
                Loading document...
            </div>
            <div class="mt-4 space-y-3">
                <div class="h-6 w-2/3 animate-pulse rounded bg-slate-200 dark:bg-slate-700"></div>
                <div class="h-4 w-full animate-pulse rounded bg-slate-200 dark:bg-slate-700"></div>
                <div class="h-4 w-5/6 animate-pulse rounded bg-slate-200 dark:bg-slate-700"></div>
                <div class="h-40 w-full animate-pulse rounded bg-slate-200 dark:bg-slate-700"></div>
            </div>
        </section>

        @if ($selected)
            <section id="document-viewer" wire:key="document-viewer-{{ $selected->id }}"
                x-show="!openingDocument"
                class="bg-white border rounded-lg border-slate-200 dark:bg-slate-800 dark:border-slate-700">
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
                        </div>
                        <a wire:navigate href="{{ route('document.library.edit', $selected) }}"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                            Edit
                        </a>
                    </div>
                </div>
                <div class="p-6 prose prose-document dark:prose-invert max-w-none">
                    <div wire:key="document-body-{{ $selected->id }}-{{ optional($selected->updated_at)->timestamp }}">
                        {!! $selected->body !!}
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
