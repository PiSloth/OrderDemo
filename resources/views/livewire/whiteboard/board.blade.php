<div x-data="{
    listOpen: true,
    isDesktop: window.innerWidth >= 1024,
    composeOpen: false,
    decisionSectionOpen: false,
    activeSidebarPanel: null,
    activeTooltip: null,
    tooltipTimer: null,
    detailHeaderVisible: true,
    lastDetailScrollTop: 0,
    toggleSidebarPanel(panel) {
        this.activeSidebarPanel = this.activeSidebarPanel === panel ? null : panel;
    },
    handleViewportChange() {
        this.isDesktop = window.innerWidth >= 1024;
    },
    closeSidebarPanels() {
        this.activeSidebarPanel = null;
    },
    sidebarStyle() {
        if (this.isDesktop) {
            return this.listOpen ?
                'width: 24rem; max-height: none; opacity: 1; transform: scaleX(1);' :
                'width: 0rem; max-height: none; opacity: 0; transform: scaleX(0);';
        }

        return this.listOpen ?
            'width: 100%; max-height: 32rem; opacity: 1; transform: scaleY(1);' :
            'width: 100%; max-height: 0rem; opacity: 0; transform: scaleY(0.96);';
    },
    handleDetailScroll(event) {
        const currentTop = event.target.scrollTop;

        if (currentTop <= 12) {
            this.detailHeaderVisible = true;
            this.lastDetailScrollTop = currentTop;

            return;
        }

        if (currentTop > this.lastDetailScrollTop + 10) {
            this.detailHeaderVisible = false;
        } else if (currentTop < this.lastDetailScrollTop - 10) {
            this.detailHeaderVisible = true;
        }

        this.lastDetailScrollTop = currentTop;
    },
    showTooltip(key) {
        this.closeTooltip();
        this.activeTooltip = key;
        this.tooltipTimer = setTimeout(() => {
            this.closeTooltip();
        }, 2000);
    },
    closeTooltip() {
        this.activeTooltip = null;
        if (this.tooltipTimer) {
            clearTimeout(this.tooltipTimer);
            this.tooltipTimer = null;
        }
    }
}" @whiteboard-show-content-info.window="showTooltip('content-info')"
    @whiteboard-compose-open.window="composeOpen = true" @whiteboard-compose-close.window="composeOpen = false"
    @resize.window="handleViewportChange()"
    @click="if ($refs.sidebarControls && !$refs.sidebarControls.contains($event.target)) closeSidebarPanels()"
    @keydown.escape.window="if (composeOpen) composeOpen = false" class="space-y-4">
    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Management Whiteboard</h1>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="button" wire:click="startCreatingContent"
                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                <span>New Content</span>
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div x-show="composeOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/45" style="display: none;">
    </div>

    <section x-show="composeOpen" x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" style="display: none;">
        <div class="absolute inset-0" @click="composeOpen = false"></div>

        <div
            class="relative z-10 flex max-h-[calc(100vh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5 sm:px-8">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $editingContentId !== '' ? 'Edit Content' : 'Post New Content' }}</h2>
                    <p class="text-sm text-slate-500">
                        {{ $editingContentId !== ''
                            ? 'Update the title, routing, and recipients without losing existing read history for people who are still included.'
                            : 'Use the shared email list directory to route issues, proposals, and announcements to the right audience.' }}
                    </p>
                </div>

                <button type="button" @click="composeOpen = false"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-500 hover:bg-slate-50 hover:text-slate-700"
                    aria-label="Close composer">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M4.22 4.22a.75.75 0 011.06 0L10 8.94l4.72-4.72a.75.75 0 111.06 1.06L11.06 10l4.72 4.72a.75.75 0 01-1.06 1.06L10 11.06l-4.72 4.72a.75.75 0 01-1.06-1.06L8.94 10 4.22 5.28a.75.75 0 010-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-6 sm:px-8">
                <form wire:submit.prevent="saveContent" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Title</label>
                        <input type="text" wire:model.defer="title"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('title')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea wire:model.defer="description" rows="4"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        @error('description')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Proposed Solution</label>
                        <div class="mt-1" wire:ignore>
                            <script type="application/json" data-quill-initial>@json($propose_solution)</script>
                            <div data-quill-editor data-whiteboard-propose-solution data-model="propose_solution"
                                data-upload-url="{{ route('document.library.upload-image') }}"
                                data-csrf="{{ csrf_token() }}"
                                class="min-h-[12rem] rounded-lg border border-slate-300 bg-white"></div>
                        </div>
                        @error('propose_solution')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Content Type</label>
                        <select wire:model.live="content_type_id"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select type</option>
                            @foreach ($contentTypes as $contentType)
                                <option value="{{ $contentType->id }}">{{ $contentType->name }}</option>
                            @endforeach
                        </select>
                        @error('content_type_id')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Flag</label>
                        <select wire:model.defer="flag_id"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">No flag</option>
                            @foreach ($flags as $flag)
                                <option value="{{ $flag->id }}">{{ $flag->name }}</option>
                            @endforeach
                        </select>
                        @error('flag_id')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Reported By</label>
                        <select wire:model.defer="report_by"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select reporter</option>
                            @foreach ($emailLists as $emailList)
                                <option value="{{ $emailList->id }}">
                                    {{ $emailList->user_name }}{{ $emailList->department ? ' - ' . $emailList->department->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('report_by')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Decision Due</label>
                        <input type="datetime-local" wire:model.defer="propose_decision_due_at"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @if ($this->selectedContentTypeRequiresDecision())
                            <p class="mt-1 text-xs font-medium text-amber-700">This content type requires a decision, so
                                the due
                                date is required.</p>
                        @endif
                        @error('propose_decision_due_at')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Received Mail At</label>
                        <input type="datetime-local" wire:model.defer="received_mail_at"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('received_mail_at')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="lg:col-span-2" x-data="{
                        recipientSearch: '',
                        selectedRecipients: $wire.entangle('recipient_ids'),
                        recipients: @js(
    $emailLists
        ->map(
            fn($emailList) => [
                'id' => (string) $emailList->id,
                'name' => $emailList->user_name,
                'email' => $emailList->email,
                'department' => $emailList->department?->name,
            ],
        )
        ->values(),
),
                        isSelected(recipientId) {
                            return (this.selectedRecipients || []).map(String).includes(String(recipientId));
                        },
                        toggleRecipient(recipientId) {
                            const normalizedId = String(recipientId);
                            const selected = (this.selectedRecipients || []).map(String);
                    
                            if (selected.includes(normalizedId)) {
                                this.selectedRecipients = selected.filter((id) => id !== normalizedId);
                    
                                return;
                            }
                    
                            this.selectedRecipients = [...selected, normalizedId];
                        },
                        removeRecipient(recipientId) {
                            const normalizedId = String(recipientId);
                            this.selectedRecipients = (this.selectedRecipients || []).map(String).filter((id) => id !== normalizedId);
                        },
                        selectedRecipientRecords() {
                            const selected = (this.selectedRecipients || []).map(String);
                    
                            return this.recipients.filter((recipient) => selected.includes(String(recipient.id)));
                        },
                        filteredRecipients() {
                            const term = this.recipientSearch.trim().toLowerCase();
                    
                            if (!term) {
                                return this.recipients;
                            }
                    
                            return this.recipients.filter((recipient) => {
                                return [recipient.name, recipient.email, recipient.department || '']
                                    .join(' ')
                                    .toLowerCase()
                                    .includes(term);
                            });
                        }
                    }">
                        <label class="block text-sm font-medium text-slate-700">Share With</label>
                        <input type="text" x-model="recipientSearch"
                            placeholder="Search people, email, or department"
                            class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                        <div x-show="selectedRecipientRecords().length" class="mt-3 flex flex-wrap gap-2">
                            <template x-for="recipient in selectedRecipientRecords()" :key="recipient.id">
                                <button type="button" @click="removeRecipient(recipient.id)"
                                    class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                                    <span x-text="recipient.name"></span>
                                    <span class="text-sm leading-none text-slate-500">×</span>
                                </button>
                            </template>
                        </div>

                        <div
                            class="mt-3 max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                            <template x-if="filteredRecipients().length === 0">
                                <div class="px-3 py-4 text-sm text-slate-500">No recipients match this search.</div>
                            </template>

                            <template x-for="recipient in filteredRecipients()" :key="recipient.id">
                                <button type="button" @click="toggleRecipient(recipient.id)"
                                    class="flex w-full items-start justify-between gap-3 rounded-lg px-3 py-2 text-left transition"
                                    :class="isSelected(recipient.id) ? 'bg-slate-900 text-white' :
                                        'text-slate-700 hover:bg-slate-50'">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium" x-text="recipient.name"></span>
                                        <span class="block text-xs opacity-80"
                                            x-text="recipient.department ? `${recipient.email} - ${recipient.department}` : recipient.email"></span>
                                        <div class="ml-auto flex items-center gap-1.5 overflow-visible">
                                            <span
                                                class="inline-flex items-center rounded-full {{ $selectedContent && $this->isContentRead($selectedContent) ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }} px-3 py-1 text-xs font-semibold">
                                                {{ $selectedContent && $this->isContentRead($selectedContent) ? 'Read' : 'Unread' }}
                                            </span>
                                </button>
                            </template>
                        </div>

                        <p class="mt-2 text-xs text-slate-500">Search above and click names to select multiple
                            recipients.</p>
                        @error('recipient_ids')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                        @error('recipient_ids.*')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="lg:col-span-2 flex justify-end gap-3 pt-2">
                        <button type="button" @click="composeOpen = false"
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                            {{ $editingContentId !== '' ? 'Save Changes' : 'Post to Board' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section
        class="h-[calc(100vh-8.5rem)] min-h-[38rem] overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
        <div class="flex h-full min-h-0 flex-col lg:flex-row">
            <aside
                class="origin-top w-full overflow-hidden border-b border-slate-200 bg-slate-50 transition-[max-height,width,opacity,transform,border-color] duration-300 ease-in-out lg:h-full lg:shrink-0 lg:origin-left lg:border-b-0 lg:border-r"
                :class="listOpen
                    ?
                    'pointer-events-auto' :
                    'pointer-events-none border-transparent lg:border-r-0'"
                :style="sidebarStyle()" <div class="flex h-full min-h-0 flex-col">
                <div class="relative border-b border-slate-200 bg-white/80 p-3 backdrop-blur" x-ref="sidebarControls">
                    <div class="flex items-center justify-start gap-1.5">
                        <button type="button" @click.stop="toggleSidebarPanel('search')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white shadow-sm hover:bg-slate-50"
                            :class="activeSidebarPanel === 'search' ?
                                'border-cyan-500 bg-cyan-500 text-white shadow-sm' : 'text-slate-600'"
                            aria-label="Search contents">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <circle cx="11" cy="11" r="6" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                        </button>

                        <button type="button" @click.stop="toggleSidebarPanel('sort')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white shadow-sm hover:bg-slate-50"
                            :class="activeSidebarPanel === 'sort' ?
                                'border-cyan-500 bg-cyan-500 text-white shadow-sm' : 'text-slate-600'"
                            aria-label="Sort contents">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                aria-hidden="true">
                                <path d="M7 6h10" />
                                <path d="M7 12h7" />
                                <path d="M7 18h4" />
                                <path d="M17 5v14" />
                                <path d="m14.5 16.5 2.5 2.5 2.5-2.5" />
                            </svg>
                        </button>

                        @php
                            $activeTypeColor =
                                $contentTypeFilter !== 'all'
                                    ? $contentTypes->firstWhere('id', (int) $contentTypeFilter)?->color ?? '#94A3B8'
                                    : '#94A3B8';
                            $activeFlagColor =
                                $flagFilter !== 'all'
                                    ? $flags->firstWhere('id', (int) $flagFilter)?->color ?? '#94A3B8'
                                    : '#94A3B8';
                            $archiveFilterActive = $archiveFilter === 'archived';
                            $receivedMailDateFilterActive = trim($receivedMailDateFilter) !== '';
                        @endphp

                        <button type="button" @click.stop="toggleSidebarPanel('type')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white shadow-sm hover:bg-slate-50"
                            :class="activeSidebarPanel === 'type' ?
                                'border-cyan-500 bg-cyan-500 text-white shadow-sm' : 'text-slate-600'"
                            aria-label="Filter by content type">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 7.5h14"
                                    :stroke="activeSidebarPanel === 'type' ? 'currentColor' : '{{ $activeTypeColor }}'"
                                    stroke-width="1.8" stroke-linecap="round" />
                                <path d="M5 12h14"
                                    :stroke="activeSidebarPanel === 'type' ? 'currentColor' : '{{ $activeTypeColor }}'"
                                    stroke-width="1.8" stroke-linecap="round" />
                                <path d="M5 16.5h14"
                                    :stroke="activeSidebarPanel === 'type' ? 'currentColor' : '{{ $activeTypeColor }}'"
                                    stroke-width="1.8" stroke-linecap="round" />
                                <circle cx="7" cy="7.5" r="1.4"
                                    :fill="activeSidebarPanel === 'type' ? 'currentColor' : '{{ $activeTypeColor }}'" />
                                <circle cx="7" cy="12" r="1.4"
                                    :fill="activeSidebarPanel === 'type' ? 'currentColor' : '{{ $activeTypeColor }}'" />
                                <circle cx="7" cy="16.5" r="1.4"
                                    :fill="activeSidebarPanel === 'type' ? 'currentColor' : '{{ $activeTypeColor }}'" />
                            </svg>
                        </button>

                        <button type="button" @click.stop="toggleSidebarPanel('flag')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white shadow-sm hover:bg-slate-50"
                            :class="activeSidebarPanel === 'flag' ?
                                'border-cyan-500 bg-cyan-500 text-white shadow-sm' : 'text-slate-600'"
                            aria-label="Filter by flag">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 3v18"
                                    :stroke="activeSidebarPanel === 'flag' ? 'currentColor' : '#475569'"
                                    stroke-width="1.8" stroke-linecap="round" />
                                <path d="M7.5 4.5h8.5l-2.2 3.5 2.2 3.5H7.5z"
                                    :fill="activeSidebarPanel === 'flag' ? 'currentColor' : '{{ $activeFlagColor }}'"
                                    :stroke="activeSidebarPanel === 'flag' ? 'currentColor' : '{{ $activeFlagColor }}'"
                                    stroke-width="1.2" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <button type="button" @click.stop="toggleSidebarPanel('archive')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white shadow-sm hover:bg-slate-50"
                            :class="activeSidebarPanel === 'archive' ?
                                'border-cyan-500 bg-cyan-500 text-white shadow-sm' : 'text-slate-600'"
                            aria-label="Filter archived contents">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M21 8v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8"
                                    :stroke="activeSidebarPanel === 'archive' ? 'currentColor' :
                                        '{{ $archiveFilterActive ? '#E11D48' : '#64748B' }}'"
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M1 3h22v5H1z"
                                    :stroke="activeSidebarPanel === 'archive' ? 'currentColor' :
                                        '{{ $archiveFilterActive ? '#E11D48' : '#64748B' }}'"
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M10 12h4"
                                    :stroke="activeSidebarPanel === 'archive' ? 'currentColor' :
                                        '{{ $archiveFilterActive ? '#E11D48' : '#64748B' }}'"
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>

                        <button type="button" @click.stop="toggleSidebarPanel('received-mail-date')"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white shadow-sm hover:bg-slate-50"
                            :class="activeSidebarPanel === 'received-mail-date' ?
                                'border-cyan-500 bg-cyan-500 text-white shadow-sm' : 'text-slate-600'"
                            aria-label="Filter by received mail date">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="16" rx="2"
                                    :stroke="activeSidebarPanel === 'received-mail-date' ? 'currentColor' :
                                        '{{ $receivedMailDateFilterActive ? '#0F766E' : '#64748B' }}'"
                                    stroke-width="1.8" />
                                <path d="M16 3v4M8 3v4M3 10h18"
                                    :stroke="activeSidebarPanel === 'received-mail-date' ? 'currentColor' :
                                        '{{ $receivedMailDateFilterActive ? '#0F766E' : '#64748B' }}'"
                                    stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M8 14h.01M12 14h.01M16 14h.01"
                                    :stroke="activeSidebarPanel === 'received-mail-date' ? 'currentColor' :
                                        '{{ $receivedMailDateFilterActive ? '#0F766E' : '#64748B' }}'"
                                    stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>

                    <div x-show="activeSidebarPanel" x-transition
                        class="absolute left-3 right-3 top-[calc(100%-0.15rem)] z-30 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl"
                        style="display: none;">
                        <div x-show="activeSidebarPanel === 'search'" style="display: none;">
                            <input id="whiteboard-search" type="text" wire:model.live.debounce.300ms="search"
                                placeholder="Search title, description, solution"
                                class="w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div x-show="activeSidebarPanel === 'sort'" style="display: none;">
                            @foreach ($this->sortOptions() as $sortKey => $sortLabel)
                                @php
                                    $sortActive = in_array($sortKey, $this->activeSorts(), true);
                                @endphp
                                <button type="button" wire:click="toggleSort('{{ $sortKey }}')"
                                    class="mt-2 flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm font-medium transition first:mt-0 {{ $sortActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $sortLabel }}</span>
                                    <span
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full border text-[11px] {{ $sortActive ? 'border-white/40 text-white' : 'border-slate-300 text-slate-400' }}">
                                        {{ $sortActive ? '✓' : '+' }}
                                    </span>
                                </button>
                            @endforeach

                            @if ($this->activeSorts())
                                <button type="button" wire:click="clearAllSorts" @click="closeSidebarPanels()"
                                    class="mt-3 flex w-full items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 hover:bg-rose-100">
                                    Clear All Sorts
                                </button>
                            @endif
                        </div>

                        <div x-show="activeSidebarPanel === 'type'" style="display: none;">
                            <button type="button" wire:click="$set('contentTypeFilter', 'all')"
                                @click="closeSidebarPanels()"
                                class="flex w-full items-center justify-center rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-200">
                                All Types
                            </button>

                            @foreach ($contentTypes as $contentType)
                                <button type="button"
                                    wire:click="$set('contentTypeFilter', '{{ $contentType->id }}')"
                                    @click="closeSidebarPanels()"
                                    class="mt-2 flex w-full items-center justify-center rounded-lg border px-3 py-2 text-center text-sm font-medium text-slate-800"
                                    style="border-color: {{ $contentType->color }}; background-color: {{ $contentType->color }}20;">
                                    {{ $contentType->name }}
                                </button>
                            @endforeach
                        </div>

                        <div x-show="activeSidebarPanel === 'flag'" style="display: none;">
                            <button type="button" wire:click="$set('flagFilter', 'all')"
                                @click="closeSidebarPanels()"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6 3v18" stroke="#475569" stroke-width="1.8" stroke-linecap="round" />
                                    <path d="M7.5 4.5h8.5l-2.2 3.5 2.2 3.5H7.5z" fill="#94A3B8" stroke="#94A3B8"
                                        stroke-width="1.2" stroke-linejoin="round" />
                                </svg>
                                <span>All Flags</span>
                            </button>

                            @foreach ($flags as $flag)
                                <button type="button" wire:click="$set('flagFilter', '{{ $flag->id }}')"
                                    @click="closeSidebarPanels()"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M6 3v18" stroke="#475569" stroke-width="1.8"
                                            stroke-linecap="round" />
                                        <path d="M7.5 4.5h8.5l-2.2 3.5 2.2 3.5H7.5z" fill="{{ $flag->color }}"
                                            stroke="{{ $flag->color }}" stroke-width="1.2"
                                            stroke-linejoin="round" />
                                    </svg>
                                    <span>{{ $flag->name }}</span>
                                </button>
                            @endforeach
                        </div>

                        <div x-show="activeSidebarPanel === 'archive'" style="display: none;">
                            @foreach ($this->archiveFilterOptions() as $archiveKey => $archiveLabel)
                                @php
                                    $archiveActive = $archiveFilter === $archiveKey;
                                @endphp
                                <button type="button" wire:click="$set('archiveFilter', '{{ $archiveKey }}')"
                                    @click="closeSidebarPanels()"
                                    class="mt-2 flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm font-medium transition first:mt-0 {{ $archiveActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
                                    <span>{{ $archiveLabel }}</span>
                                    <span
                                        class="inline-flex h-5 w-5 items-center justify-center rounded-full border text-[11px] {{ $archiveActive ? 'border-white/40 text-white' : 'border-slate-300 text-slate-400' }}">
                                        {{ $archiveActive ? '✓' : '+' }}
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <div x-show="activeSidebarPanel === 'received-mail-date'" style="display: none;">
                            <div class="space-y-3">
                                <div class="w-full" wire:ignore x-data="{
                                    value: @entangle('receivedMailDateFilter').live,
                                    picker: null,
                                    initPicker() {
                                        if (!window.flatpickr) {
                                            return;
                                        }
                                
                                        if (this.$refs.dateInput._flatpickr) {
                                            this.picker = this.$refs.dateInput._flatpickr;
                                            return;
                                        }
                                
                                        const alpine = this;
                                
                                        this.picker = window.flatpickr(this.$refs.dateInput, {
                                            dateFormat: 'Y-m-d',
                                            altInput: true,
                                            altFormat: 'M d, Y',
                                            defaultDate: alpine.value || null,
                                            altInputClass: 'block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
                                            onReady(selectedDates, dateStr, instance) {
                                                if (instance?.altInput) {
                                                    instance.altInput.placeholder = 'Select received mail date';
                                                }
                                            },
                                            onChange(selectedDates, dateStr) {
                                                alpine.value = selectedDates.length ? dateStr : '';
                                            }
                                        });
                                
                                        this.$watch('value', (value) => {
                                            if (!this.picker) {
                                                return;
                                            }
                                
                                            if (!value) {
                                                if (this.picker.selectedDates.length) {
                                                    this.picker.clear();
                                                }
                                
                                                return;
                                            }
                                
                                            if (this.picker.input.value !== value) {
                                                this.picker.setDate(value, false, 'Y-m-d');
                                            }
                                        });
                                    },
                                    clear() {
                                        this.value = '';
                                
                                        if (this.picker) {
                                            this.picker.clear();
                                        }
                                    }
                                }" x-init="$nextTick(() => initPicker())">
                                    <input x-ref="dateInput" type="text" class="w-full"
                                        placeholder="Select received mail date" />
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs text-slate-500">Show only mail received on the selected date.</p>
                                    <button type="button" x-show="value" @click="clear()"
                                        class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100"
                                        style="display: none;">
                                        Clear Date
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($this->activeSorts() || $receivedMailDateFilterActive)
                        <div class="mt-2 flex flex-wrap items-center gap-1.5 pr-2">
                            @foreach ($this->activeSorts() as $sortKey)
                                <button type="button" wire:click="clearSort('{{ $sortKey }}')"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-700 hover:bg-slate-200">
                                    <span>{{ $this->sortLabel($sortKey) }}</span>
                                    <span class="text-xs leading-none text-slate-500">×</span>
                                </button>
                            @endforeach
                            @if ($receivedMailDateFilterActive)
                                <button type="button" wire:click="$set('receivedMailDateFilter', '')"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-teal-200 bg-teal-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-teal-700 hover:bg-teal-100">
                                    <span>Received {{ $receivedMailDateFilter }}</span>
                                    <span class="text-xs leading-none text-teal-600">x</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    @forelse ($boardContents as $content)
                        <button type="button" wire:key="whiteboard-item-{{ $content->id }}"
                            wire:click="selectContent({{ $content->id }})"
                            @click="closeTooltip(); decisionSectionOpen = false; if (window.innerWidth < 1024) { listOpen = false; }"
                            class="flex w-full items-start gap-2.5 border-b border-slate-200 px-3 py-3 text-left transition hover:bg-white {{ $selectedContent?->id === $content->id ? 'bg-white ring-1 ring-inset ring-indigo-200' : 'bg-transparent' }}">
                            <div class="mt-0.5 flex flex-col items-center gap-1.5">
                                <span
                                    class="h-2.5 w-2.5 rounded-full {{ $this->isContentRead($content) ? 'bg-slate-300' : 'bg-indigo-500' }}"></span>
                                @if ($content->flag)
                                    <span class="h-9 w-1 rounded-full"
                                        style="background-color: {{ $content->flag->color }};"></span>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <h3
                                                class="truncate text-sm {{ $this->isContentRead($content) ? 'font-medium text-slate-700' : 'font-semibold text-slate-900' }}">
                                                {{ $content->title }}
                                            </h3>
                                            @if ($content->trashed())
                                                <span
                                                    class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700">
                                                    Archived
                                                </span>
                                            @endif
                                            <span
                                                class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold text-slate-700"
                                                style="border-color: {{ $content->contentType->color }}; background-color: {{ $content->contentType->color }}20; color: {{ $content->contentType->color }};">
                                                {{ $content->contentType?->name ?? 'Uncategorized' }}
                                            </span>
                                            @if ($content->flag)
                                                <span
                                                    class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold text-slate-700"
                                                    style="border-color: {{ $content->flag->color }}; background-color: {{ $content->flag->color }}20;">
                                                    {{ $content->flag->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($content->propose_decision_due_at)
                                        <span
                                            class="shrink-0 text-[10px] font-medium text-slate-500">{{ $content->propose_decision_due_at->format('M d, H:i') }}</span>
                                    @endif
                                </div>

                                <p class="mt-1.5 text-xs leading-5 text-slate-600">
                                    {{ \Illuminate\Support\Str::limit(trim(strip_tags($content->description)), 82) }}
                                </p>


                                <div
                                    class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-500">
                                    <span>{{ $content->reporter?->user_name ?? 'Unknown reporter' }}</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 bg-gray-50">{{ $this->isContentRead($content) ? 'Read' : 'Unread' }}</span>
                                    @if ($content->requiresDecision() && !$content->latestDecision)
                                        <span class="font-semibold text-amber-700">Decision needed</span>
                                    @endif
                                    <span class="text-xs">{{ $content->received_mail_at?->format('M d, H:i') }}</span>
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="p-8 text-center text-sm text-slate-500">
                            No whiteboard content matches the current filters.
                        </div>
                    @endforelse
                </div>
        </div>
        </aside>

        <div
            class="relative min-h-0 min-w-0 flex-1 overflow-hidden bg-white transition-[width] duration-300 ease-in-out">
            <button type="button" @click="listOpen = !listOpen"
                class="absolute left-4 top-2 z-20 inline-flex h-12 w-12 items-center justify-center bg-gradient-to-br from-pink-300 via-rose-300 to-fuchsia-400 text-xl font-bold text-white shadow-[0_16px_36px_rgba(236,72,153,0.28)] transition hover:scale-105 hover:shadow-[0_20px_40px_rgba(236,72,153,0.34)] focus:outline-none focus:ring-2 focus:ring-pink-300 focus:ring-offset-2"
                style="border-radius: 58% 42% 63% 37% / 41% 58% 42% 59%;" aria-label="Toggle content sidebar">
                <svg class="h-5 w-5 transition-transform duration-200" viewBox="0 0 20 20" fill="none"
                    aria-hidden="true" :class="listOpen ? 'rotate-180' : 'rotate-0'">
                    <path d="M7 4.75 12.25 10 7 15.25" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>

            <button type="button" wire:click="toggleUnreadFocus"
                class="absolute left-20 top-2 z-20 inline-flex h-12 w-12 items-center justify-center text-white shadow-[0_16px_36px_rgba(20,184,166,0.28)] transition hover:scale-105"
                :class="$wire.unreadOnly ?
                    'bg-gradient-to-br from-cyan-500 via-teal-500 to-emerald-500' :
                    'bg-gradient-to-br from-teal-300 via-cyan-300 to-emerald-400'"
                style="border-radius: 42% 58% 46% 54% / 55% 42% 58% 45%;" aria-label="Show unread decision queue">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6.75 5.75A2.75 2.75 0 0 1 9.5 3h8.75v15.25H9.5a2.75 2.75 0 0 0-2.75 2.75z" />
                    <path d="M6.75 5.75v14.5" />
                    <path d="M9.75 7.75h5.5" />
                    <path d="M9.75 11h5.5" />
                </svg>
                @if ($unreadCount > 0)
                    <span
                        class="absolute -right-1.5 -top-1.5 inline-flex min-h-[1.2rem] min-w-[1.2rem] items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold leading-none text-teal-700 shadow-sm">
                        {{ $unreadCount }}
                    </span>
                @endif
            </button>

            @if (!$selectedContent)
                <div class="flex h-full min-h-0 overflow-y-auto px-6 pt-12 text-center">
                    <div class="m-auto max-w-md py-8">
                        <h2 class="text-xl font-semibold text-slate-900">No whiteboard item selected</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Adjust the filters, open the list, or
                            create a new whiteboard item to start collaborating.</p>
                        <div class="mt-6 flex justify-center gap-3">
                            <button type="button" @click="listOpen = true"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                Show List
                            </button>
                            <button type="button" wire:click="startCreatingContent"
                                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                                New Content
                            </button>
                        </div>
                    </div>
                </div>
            @else
                @php
                    $combinedInfoTooltip = collect([
                        'Recipients:',
                        $selectedContent->reports->isNotEmpty()
                            ? $selectedContent->reports
                                ->map(function ($report) {
                                    $recipientName = $report->emailList?->user_name ?? 'Recipient removed';
                                    $recipientEmail = $report->emailList?->email ?? 'No email';
                                    $recipientStatus = $report->is_read ? 'Readd' : 'Unread';

                                    return "- {$recipientName} ({$recipientEmail}) {$recipientStatus}";
                                })
                                ->implode("\n")
                            : '- No recipients available.',
                        '',
                        'Latest Decision:',
                        $selectedContent->latestDecision
                            ? collect([
                                trim(strip_tags($selectedContent->latestDecision->decision)),
                                $selectedContent->latestDecision->appointment_at
                                    ? 'Appointment: ' .
                                        $selectedContent->latestDecision->appointment_at->format('Y-m-d H:i')
                                    : null,
                                $selectedContent->latestDecision->invited_person
                                    ? 'Invited Person: ' . $selectedContent->latestDecision->invited_person
                                    : null,
                            ])
                                ->filter()
                                ->implode("\n")
                            : 'No decision has been captured for this item yet.',
                    ])->implode("\n");
                @endphp
                <div class="flex h-full min-h-0 flex-col ">
                    <div class="border-b border-slate-200 px-4 py-5 transition-all duration-200 sm:px-5"
                        :class="detailHeaderVisible ? 'max-h-[18rem] translate-y-0 opacity-100' :
                            'pointer-events-none max-h-0 -translate-y-4 overflow-hidden border-b-0 py-0 opacity-0'">
                        <div class="flex flex-col gap-3">
                            <div class="min-w-0">
                                <div class=" flex ml-24 gap-2">
                                    {{-- Left buttons --}}
                                    <div class="p-4">
                                        <button type="button" @click="listOpen = true"
                                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 hover:bg-slate-50 lg:hidden">
                                            Show List
                                        </button>
                                        <span
                                            class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $selectedContent->contentType?->name ?? 'Uncategorized' }}</span>
                                        @if ($selectedContent->flag)
                                            <span
                                                class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold text-slate-700"
                                                style="border-color: {{ $selectedContent->flag->color }}; background-color: {{ $selectedContent->flag->color }}20;">
                                                {{ $selectedContent->flag->name }}
                                            </span>
                                        @endif
                                        @if ($selectedContent->requiresDecision())
                                            <span
                                                class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Decision
                                                Required</span>
                                        @endif
                                        @if ($selectedContent->trashed())
                                            <span
                                                class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Archived</span>
                                        @endif
                                        <span
                                            class="inline-flex items-center rounded-full {{ $this->isContentRead($selectedContent) ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700' }} px-3 py-1 text-xs font-semibold">
                                            {{ $this->isContentRead($selectedContent) ? 'Read' : 'Unread' }}
                                        </span>
                                    </div>
                                    {{-- Right buttons --}}
                                    <div class="ml-auto flex items-center gap-1.5 overflow-visible">
                                        {{-- Change Flag condition --}}
                                        <div x-data="{ open: false }" class="relative">
                                            <button type="button" @click.stop="open = !open"
                                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                                aria-label="Update flag">
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                                    aria-hidden="true">
                                                    <path d="M6 3v18" stroke="#475569" stroke-width="1.8"
                                                        stroke-linecap="round" />
                                                    <path d="M7.5 4.5h8.5l-2.2 3.5 2.2 3.5H7.5z"
                                                        fill="{{ $selectedContent->flag?->color ?? '#94A3B8' }}"
                                                        stroke="{{ $selectedContent->flag?->color ?? '#94A3B8' }}"
                                                        stroke-width="1.2" stroke-linejoin="round" />
                                                </svg>
                                                <span>{{ $selectedContent->flag?->name ?? 'Flag' }}</span>
                                            </button>

                                            <div x-show="open" x-transition @click.away="open = false"
                                                class="absolute right-0 top-10 z-30 min-w-[14rem] rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                                                style="display: none;">
                                                <button type="button" wire:click="updateSelectedFlag('')"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                                        aria-hidden="true">
                                                        <path d="M6 3v18" stroke="#475569" stroke-width="1.8"
                                                            stroke-linecap="round" />
                                                        <path d="M7.5 4.5h8.5l-2.2 3.5 2.2 3.5H7.5z" fill="#CBD5E1"
                                                            stroke="#CBD5E1" stroke-width="1.2"
                                                            stroke-linejoin="round" />
                                                    </svg>
                                                    <span>No flag flag</span>
                                                </button>

                                                @foreach ($flags as $flag)
                                                    <button type="button"
                                                        wire:click="updateSelectedFlag('{{ $flag->id }}')"
                                                        @click="open = false"
                                                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                                            aria-hidden="true">
                                                            <path d="M6 3v18" stroke="#475569" stroke-width="1.8"
                                                                stroke-linecap="round" />
                                                            <path d="M7.5 4.5h8.5l-2.2 3.5 2.2 3.5H7.5z"
                                                                fill="{{ $flag->color }}"
                                                                stroke="{{ $flag->color }}" stroke-width="1.2"
                                                                stroke-linejoin="round" />
                                                        </svg>
                                                        <span>{{ $flag->name }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>

                                        @if ($this->canManageSelectedContent())
                                            @if (!$selectedContent->trashed())
                                                <div class="relative">
                                                    <button type="button" wire:click="startEditingSelectedContent"
                                                        @mouseenter="showTooltip('content-edit')"
                                                        @mouseleave="closeTooltip()"
                                                        @focus="showTooltip('content-edit')" @blur="closeTooltip()"
                                                        class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50"
                                                        aria-label="Edit content">
                                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                                                            stroke="currentColor" stroke-width="1.8"
                                                            stroke-linecap="round" stroke-linejoin="round"
                                                            aria-hidden="true">
                                                            <path d="M12 20h9" />
                                                            <path
                                                                d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                                                        </svg>
                                                    </button>

                                                    <div x-show="activeTooltip === 'content-edit'" x-transition
                                                        class="absolute right-9 top-1/2 z-30 -translate-y-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-medium text-white shadow-xl"
                                                        style="display: none;">
                                                        Edit
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="relative">
                                                <button type="button" @mouseenter="showTooltip('content-archive')"
                                                    @mouseleave="closeTooltip()"
                                                    @focus="showTooltip('content-archive')" @blur="closeTooltip()"
                                                    @click="if (confirm('{{ $selectedContent->trashed() ? 'Unarchive this content?' : 'Archive this content?' }}')) { {{ $selectedContent->trashed() ? '$wire.restoreSelectedContent()' : '$wire.archiveSelectedContent()' }} }"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border {{ $selectedContent->trashed() ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' }} shadow-sm"
                                                    aria-label="{{ $selectedContent->trashed() ? 'Unarchive content' : 'Archive content' }}">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                                                        stroke="currentColor" stroke-width="1.8"
                                                        stroke-linecap="round" stroke-linejoin="round"
                                                        aria-hidden="true">
                                                        @if ($selectedContent->trashed())
                                                            <path d="M12 19V9" />
                                                            <path d="m7 14 5-5 5 5" />
                                                            <path d="M5 5h14" />
                                                        @else
                                                            <path d="M3 6h18" />
                                                            <path
                                                                d="M8 6V4.75A1.75 1.75 0 0 1 9.75 3h4.5A1.75 1.75 0 0 1 16 4.75V6" />
                                                            <path
                                                                d="M19.5 6l-.7 11.1A2 2 0 0 1 16.8 19H7.2a2 2 0 0 1-1.99-1.9L4.5 6" />
                                                            <path d="M10 10.25v5.5" />
                                                            <path d="M14 10.25v5.5" />
                                                        @endif
                                                    </svg>
                                                </button>

                                                <div x-show="activeTooltip === 'content-archive'" x-transition
                                                    class="absolute right-9 top-1/2 z-30 -translate-y-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-medium text-white shadow-xl"
                                                    style="display: none;">
                                                    {{ $selectedContent->trashed() ? 'Unarchive' : 'Archive' }}
                                                </div>
                                            </div>
                                        @endif

                                        <div class="relative">
                                            <button type="button" @click.stop="showTooltip('content-info')"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-[10px] font-semibold text-white shadow-sm hover:bg-slate-800"
                                                aria-label="Show content information">
                                                i
                                            </button>

                                            <div x-show="activeTooltip === 'content-info'" x-transition
                                                class="absolute right-9 top-1/2 z-30 w-80 max-w-[calc(100vw-4rem)] -translate-y-1/2 rounded-xl bg-slate-900 px-4 py-3 text-xs leading-6 text-white shadow-xl"
                                                style="display: none;">
                                                <p class="font-semibold text-slate-100">Content Info</p>
                                                <p class="mt-2 whitespace-pre-line text-slate-200">
                                                    {{ $combinedInfoTooltip }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h2 class="mt-3 text-2xl font-bold text-slate-900">{{ $selectedContent->title }}
                                </h2>
                                @php
                                    $sharedWithNames = $selectedContent->reports
                                        ->map(fn($report) => $report->emailList?->user_name)
                                        ->filter()
                                        ->values();
                                @endphp
                                <div class="grid grid-cols-1 gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Shared With</p>
                                        <p class="mt-1">
                                            {{ $sharedWithNames->isNotEmpty() ? $sharedWithNames->implode(', ') : 'No recipients selected' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Created By</p>
                                        <p class="mt-1">
                                            {{ $selectedContent->creator?->name ?? 'Unknown creator' }}</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-6 sm:px-8"
                        @scroll="handleDetailScroll($event)">
                        <div class="space-y-6">
                            <section>
                                <p class="ml-6 text-lg font-semibold text-slate-700">အကြောင်းအရာ</p>
                                <p class="ml-6 mt-2 font-mono leading-7 text-slate-500">
                                    {{ $selectedContent->description }}</p>

                                @if (!$selectedContent->propose_solution)
                                    <div class="mt-5 flex justify-end">
                                        <button type="button" @click="decisionSectionOpen = true"
                                            class="inline-flex items-center gap-3 rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-medium text-amber-950 shadow-sm hover:bg-amber-400">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path
                                                    d="M8 3.75h6.5L19 8.25V19a1.75 1.75 0 0 1-1.75 1.75h-9.5A1.75 1.75 0 0 1 6 19V5.5A1.75 1.75 0 0 1 7.75 3.75z" />
                                                <path d="M14.5 3.75V8.5H19" />
                                                <path d="M9 12h6" />
                                                <path d="M9 15.5h6" />
                                            </svg>
                                            <span>Add Decision</span>
                                        </button>
                                    </div>
                                @endif
                            </section>

                            @if ($selectedContent->propose_solution)
                                <section class="rounded-2xl border border-sky-100 bg-sky-50 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">
                                        Executive Summary</p>
                                    <div class="prose prose-sm mt-3 max-w-none text-sky-900">
                                        {!! $selectedContent->propose_solution !!}
                                    </div>

                                    <div class="mt-5 flex justify-end">
                                        <button type="button" @click="decisionSectionOpen = true"
                                            class="inline-flex items-center gap-3 rounded-xl bg-amber-300 px-4 py-2.5 text-sm font-medium text-amber-950 shadow-sm hover:bg-amber-400">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                                stroke-linejoin="round" aria-hidden="true">
                                                <path
                                                    d="M8 3.75h6.5L19 8.25V19a1.75 1.75 0 0 1-1.75 1.75h-9.5A1.75 1.75 0 0 1 6 19V5.5A1.75 1.75 0 0 1 7.75 3.75z" />
                                                <path d="M14.5 3.75V8.5H19" />
                                                <path d="M9 12h6" />
                                                <path d="M9 15.5h6" />
                                            </svg>
                                            <span>Add Decision</span>
                                        </button>
                                    </div>
                                </section>
                            @endif

                            <section x-show="decisionSectionOpen" x-transition
                                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-slate-900">Decision Section</h3>
                                        <p class="text-sm text-slate-500">Add a decision, schedule an
                                            appointment, or record follow-up context as rich text.</p>
                                    </div>
                                    @if ($selectedContent->requiresDecision() && !$selectedContent->latestDecision)
                                        <span
                                            class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Pending
                                            required decision</span>
                                    @endif
                                </div>

                                <form wire:submit.prevent="submitDecision" class="mt-5 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Decision</label>
                                        <div class="mt-1" wire:ignore>
                                            <script type="application/json" data-quill-initial>@json($decision)</script>
                                            <div data-quill-editor data-whiteboard-decision-editor
                                                data-model="decision"
                                                data-upload-url="{{ route('document.library.upload-image') }}"
                                                data-csrf="{{ csrf_token() }}"
                                                class="min-h-[14rem] rounded-lg border border-slate-300 bg-white">
                                            </div>
                                        </div>
                                        @error('decision')
                                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">Schedule
                                                Appointment</label>
                                            <input type="datetime-local" wire:model.defer="appointment_at"
                                                class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @error('appointment_at')
                                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">Invited
                                                Person</label>
                                            <input type="text" wire:model.defer="invited_person"
                                                class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder="Meeting owner or invited person">
                                            @error('invited_person')
                                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit"
                                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                            Save Decision
                                        </button>
                                    </div>
                                </form>
                            </section>

                            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-lg font-semibold text-slate-900">Decision History</h3>
                                    <span class="text-sm text-slate-500">{{ $selectedContent->decisions->count() }}
                                        entries</span>
                                </div>

                                <div class="mt-4 space-y-4">
                                    @forelse ($selectedContent->decisions as $decisionRow)
                                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div
                                                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-800">
                                                        {{ $decisionRow->creator?->name ?? 'Unknown user' }}
                                                    </p>
                                                    <p class="text-xs text-slate-500">
                                                        {{ $decisionRow->created_at->format('Y-m-d H:i') }}</p>
                                                </div>
                                                @if ($decisionRow->appointment_at)
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                                                        Appointment:
                                                        {{ $decisionRow->appointment_at->format('Y-m-d H:i') }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="prose prose-sm mt-4 max-w-none text-slate-700">
                                                {!! $decisionRow->decision !!}
                                            </div>

                                            @if ($decisionRow->invited_person)
                                                <p class="mt-3 text-xs text-slate-500">Invited Person:
                                                    {{ $decisionRow->invited_person }}</p>
                                            @endif
                                        </article>
                                    @empty
                                        <div
                                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                                            No decisions have been recorded yet.
                                        </div>
                                    @endforelse
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            @endif
        </div>
</div>
</section>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('whiteboard-propose-solution-reset', () => {
                document.querySelectorAll('[data-whiteboard-propose-solution]').forEach((editorEl) => {
                    if (!editorEl.__quill) {
                        return;
                    }

                    editorEl.__quill.setContents([]);
                });
            });

            Livewire.on('whiteboard-propose-solution-fill', (event) => {
                const html = event?.html || '';

                document.querySelectorAll('[data-whiteboard-propose-solution]').forEach((editorEl) => {
                    if (!editorEl.__quill) {
                        return;
                    }

                    editorEl.__quill.setContents([]);

                    if (html.trim() !== '') {
                        editorEl.__quill.clipboard.dangerouslyPasteHTML(html);
                    }
                });
            });

            Livewire.on('whiteboard-decision-reset', () => {
                document.querySelectorAll('[data-whiteboard-decision-editor]').forEach((editorEl) => {
                    if (!editorEl.__quill) {
                        return;
                    }

                    editorEl.__quill.setContents([]);
                });
            });

            Livewire.on('whiteboard-compose-open', () => {
                window.dispatchEvent(new CustomEvent('whiteboard-compose-open'));
            });

            Livewire.on('whiteboard-compose-close', () => {
                window.dispatchEvent(new CustomEvent('whiteboard-compose-close'));
            });

            Livewire.on('whiteboard-content-selected', () => {
                window.setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('whiteboard-show-content-info'));
                }, 75);
            });
        });
    </script>
@endpush
