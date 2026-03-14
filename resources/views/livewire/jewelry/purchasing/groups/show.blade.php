<div x-data="{
    importOpen: false,
    commentsOpen: false,
    viewMode: @js((string) ($group->purchase_status ?? '') === 'done' || (bool) ($group->is_purchase ?? false) ? 'items' : 'batch'),
    extraCols: false,
    startTs: null,
    finishTs: null,
    elapsed: '00:00',
    _timer: null,
    setTimes(startTs, finishTs) {
        this.startTs = (startTs === null || typeof startTs === 'undefined') ? null : Number(startTs);
        this.finishTs = (finishTs === null || typeof finishTs === 'undefined') ? null : Number(finishTs);

        if (this._timer) {
            clearInterval(this._timer);
            this._timer = null;
        }

        this.tick();

        if (this.startTs && !this.finishTs) {
            this._timer = setInterval(() => this.tick(), 1000);
        }
    },
    tick() {
        if (!this.startTs) {
            this.elapsed = '00:00';
            return;
        }
        const nowTs = Math.floor(Date.now() / 1000);
        const endTs = this.finishTs ? this.finishTs : nowTs;
        const total = Math.max(0, endTs - this.startTs);
        const m = Math.floor(total / 60);
        const s = total % 60;
        this.elapsed = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    },
    copied: {},
    copyText(text) {
        if (text === null || typeof text === 'undefined') return;
        const t = String(text);
        if (t === '') return;
        try {
            if (navigator?.clipboard?.writeText) {
                navigator.clipboard.writeText(t);
                return;
            }
        } catch (e) {}

        const el = document.createElement('textarea');
        el.value = t;
        el.setAttribute('readonly', 'readonly');
        el.style.position = 'absolute';
        el.style.left = '-9999px';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
    },
    markCopied(key) {
        if (!key) return;
        this.copied[key] = true;
        setTimeout(() => { this.copied[key] = false; }, 1500);
    },
    isCopied(key) { return !!this.copied[key]; }
}" class="space-y-6">
    <div class="flex items-center justify-between">
        <a wire:navigate href="{{ route('jewelry.groups.index') }}"
            class="inline-flex items-center px-3 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
            <x-icon name="arrow-left" class="w-4 h-4 mr-2" />
            Back
        </a>

        @php
            $isRunning = !empty($group->started_at) && empty($group->finished_at);
            $commentCount = is_countable($comments ?? null) ? count($comments) : 0;
        @endphp
        <div class="flex items-center gap-2">
            {{-- <button type="button" @click="importOpen = true"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <x-icon name="upload" class="w-4 h-4 mr-2" />
                Import Excel
            </button> --}}

            <button type="button" @click="commentsOpen = true"
                class="relative inline-flex items-center justify-center w-10 h-10 border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                aria-label="Open comments">
                <x-icon name="annotation" class="w-5 h-5" />
                @if ($commentCount > 0)
                    <span
                        class="absolute -top-1.5 -right-1.5 inline-flex min-w-[18px] items-center justify-center rounded-full bg-primary-600 px-1.5 py-0.5 text-[11px] font-semibold leading-none text-white">
                        {{ $commentCount }}
                    </span>
                @endif
            </button>

            <button type="button" wire:click="start" @disabled($isRunning) @class([
                'px-3 py-2 text-sm font-medium border rounded-md inline-flex items-center gap-2' => true,
                'border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700' => !$isRunning,
                'border-amber-300 bg-amber-100 text-amber-900 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200 cursor-default' => $isRunning,
            ])>
                @if ($isRunning)
                    <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                    <span>Running</span>
                    <span class="text-xs font-semibold">⚡</span>
                @else
                    <span>Start</span>
                @endif
            </button>
            <button type="button" wire:click="finish" @disabled(empty($canFinish)) @class([
                'px-3 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 dark:border-slate-600 dark:text-slate-200' => true,
                'hover:bg-slate-50 dark:hover:bg-slate-700' => !empty($canFinish),
                'opacity-50 cursor-not-allowed' => empty($canFinish),
            ])>
                Finish
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 text-sm text-green-800 bg-green-100 border border-green-200 rounded">
            {{ session('success') }}
        </div>
    @endif

    <!-- Comments Modal -->
    <div x-show="commentsOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="commentsOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Comments</div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Group {{ $group->number }}</div>
                    </div>
                    <button type="button" @click="commentsOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close comments modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4">
                    <form wire:submit.prevent="addComment" class="space-y-2">
                        <textarea wire:model.live="commentContent" rows="3"
                            class="block w-full rounded-md border border-slate-300 bg-white p-2 text-sm text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                            placeholder="Write a comment..."></textarea>
                        @error('commentContent')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror

                        <div class="flex justify-end">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                                Add Comment
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 max-h-[55vh] space-y-3 overflow-auto">
                        @forelse (($comments ?? []) as $c)
                            <div class="rounded-md border border-slate-200 p-3 dark:border-slate-700">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">
                                        {{ $c->user?->name ?? '—' }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-300">
                                        {{ $c->created_at?->format('Y-m-d H:i') ?? '' }}
                                    </div>
                                </div>
                                <div class="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">
                                    {{ (string) ($c->content ?? '') }}
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-slate-500 dark:text-slate-300">No comments yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info & Action Section -->
    @php
        $grade = strtolower((string) ($gradeLabel ?? ''));
        $infoBg = 'bg-white dark:bg-slate-800';
        if ($grade === 'excellent') {
            $infoBg = 'bg-gradient-to-r from-yellow-100 to-yellow-200 dark:from-yellow-900/20 dark:to-slate-800';
        } elseif ($grade === 'good') {
            $infoBg = 'bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/20 dark:to-slate-800';
        } elseif ($grade === 'fighting') {
            $infoBg = 'bg-gradient-to-r from-red-100 to-red-200 dark:from-red-900/20 dark:to-slate-800';
        }
    @endphp
    <div class="rounded-lg border border-slate-200 p-4 dark:border-slate-700 {{ $infoBg }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900 dark:text-white">Group {{ $group->number }}</h1>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-300">
                    Purchaser: <span
                        class="font-medium text-slate-700 dark:text-slate-200">{{ $group->purchaseBy?->name ?? '—' }}</span>
                    <span class="mx-2">•</span>
                    Status: <span
                        class="font-medium text-slate-700 dark:text-slate-200">{{ $group->purchase_status }}</span>
                </div>
            </div>

            <div class="grid gap-2 sm:text-right">
                <div class="text-sm text-slate-700 dark:text-slate-200">
                    Time taken:
                    <span class="font-semibold">
                        @if (!is_null($durationMinutes))
                            {{ $durationMinutes }} min
                        @else
                            —
                        @endif
                    </span>
                </div>

                <div class="text-sm text-slate-700 dark:text-slate-200"
                    x-effect="setTimes(@js($group->started_at?->getTimestamp()), @js($group->finished_at?->getTimestamp()))">
                    Elapsed:
                    <span class="inline-block w-[42px] text-right font-semibold tabular-nums" x-text="elapsed"></span>
                </div>
                <div class="text-sm text-slate-700 dark:text-slate-200">
                    Grade:
                    @php
                        $gradeClasses = 'bg-slate-100 text-slate-900 dark:bg-slate-700 dark:text-slate-100';
                        if ($grade === 'excellent') {
                            $gradeClasses = 'bg-yellow-200 text-slate-900 dark:bg-yellow-300 dark:text-slate-900';
                        } elseif ($grade === 'good') {
                            $gradeClasses = 'bg-green-100 text-green-900 dark:bg-green-200 dark:text-green-900';
                        } elseif ($grade === 'fighting') {
                            $gradeClasses = 'bg-red-600 text-white dark:bg-red-600 dark:text-white';
                        }
                    @endphp
                    <span
                        class="inline-flex items-center gap-2 rounded-md px-2.5 py-1 font-semibold {{ $gradeClasses }}">
                        @if (!is_null($gradeValue))
                            <span>{{ $gradeLabel }}</span>
                            @if ($grade === 'excellent')
                                <span aria-label="Excellent">👑</span>
                            @elseif($grade === 'fighting')
                                <span aria-label="Fighting">💪</span>
                            @endif
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">PO Reference</label>
                <div class="mt-1 flex gap-2">
                    <input type="text" wire:model.live="po_reference"
                        class="block w-full border rounded-md border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                    <button type="button" wire:click="savePoReference"
                        class="px-3 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">Save</button>
                </div>
                @error('po_reference')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="text-sm text-slate-700 dark:text-slate-200 sm:text-right">
                <div>Started: <span class="font-medium">{{ $group->started_at?->format('Y-m-d H:i') ?? '—' }}</span>
                </div>
                <div>Finished: <span class="font-medium">{{ $group->finished_at?->format('Y-m-d H:i') ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div x-show="importOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" @click="importOpen = false"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl overflow-hidden bg-white rounded-lg shadow-lg dark:bg-slate-800">
                <div class="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <div class="text-base font-semibold text-slate-900 dark:text-white">Import Jewelry Items</div>
                        <div class="text-sm text-slate-500 dark:text-slate-300">Batch column can be empty; batching is
                            auto-generated from matching fields.</div>
                    </div>
                    <button type="button" @click="importOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 border rounded-md border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Close import modal">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>

                <form wire:submit.prevent="import" class="p-4 space-y-4">
                    <div class="text-sm text-slate-600 dark:text-slate-200">
                        Required columns: <span class="font-medium">Branch ID</span>, <span
                            class="font-medium">Product
                            Name</span>, <span class="font-medium">Quality</span>,
                        <span class="font-medium">Total Weight</span>, <span
                            class="font-medium">ပန်းထိမ်အလျော့တွက်</span>,
                        <span class="font-medium">ပန်းထိမ် လက်ခ</span>, <span class="font-medium">ကျောက်ချိန်</span>.
                        Optional: <span class="font-medium">Barcode</span>, <span class="font-medium">Gold
                            Weight</span>,
                        <span class="font-medium">ကျောက်ဖိုး</span>, <span class="font-medium">အမြတ်အလျော့</span>,
                        <span class="font-medium">အမြတ်လက်ခ</span>, <span class="font-medium">Batch Number</span>. If
                        blank,
                        batch IDs are auto-generated by matching the 7 fields above.
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">Limits: max 12 unique batch IDs
                            and
                            120 total items per group. If exceeded, the system will auto-create new group(s) and
                            continue importing.</div>
                    </div>

                    <div>
                        <input type="file" wire:model="importFile" accept=".xlsx,.csv,.ods"
                            class="block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:text-slate-200 dark:file:bg-slate-700 dark:file:text-slate-100" />
                        @error('importFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div wire:loading wire:target="importFile" class="mt-2 text-sm text-slate-500">Uploading…
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="importOpen = false"
                            class="px-4 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700"
                            wire:loading.attr="disabled" wire:target="import">
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (!empty($importErrors ?? []))
        <div class="p-3 text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded">
            <div class="font-medium">Import warnings</div>
            <ul class="mt-2 space-y-1 list-disc list-inside">
                @foreach ($importErrors as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div @class([
        'rounded-lg border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 overflow-hidden' => true,
        'opacity-70' => empty($group->started_at),
    ])>
        @php
            $batchCount = is_countable($batchSummaries ?? null) ? count($batchSummaries) : 0;
            $showGrandTotal = $batchCount > 1;
            // Determine posted state from the *current* checkbox state when available.
            $postState = is_array($batchPostState ?? null) ? $batchPostState : [];
            $anyBatchPosted = false;
            foreach ($batchSummaries ?? [] as $b) {
                $bId = (int) ($b['batch_id'] ?? 0);
                $isPosted = array_key_exists($bId, $postState) ? (bool) $postState[$bId] : !empty($b['is_post']);
                if ($isPosted) {
                    $anyBatchPosted = true;
                    break;
                }
            }

            $displayTotalGram = (float) ($footer['total_weight_plus_deduction'] ?? ($footer['total_weight'] ?? 0));
        @endphp

        <div class="px-4 py-3 border-b dark:border-slate-700 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            @class([
                'border-slate-200 bg-slate-50 dark:bg-slate-900/40 text-slate-700 dark:text-slate-200' => !$showGrandTotal,
                'border-slate-200 bg-slate-50 dark:bg-slate-900/40 text-slate-700 dark:text-slate-200' =>
                    $showGrandTotal && !$anyBatchPosted,
                'border-green-200 dark:border-green-700 bg-green-100 dark:bg-green-900/20 text-green-900 dark:text-green-200' =>
                    $showGrandTotal && $anyBatchPosted,
            ])>
            <div class="text-sm">
                <span class="font-medium">View:</span>

                @if ($showGrandTotal)
                    <span x-show="viewMode === 'batch'" x-cloak>
                        <span class="mx-2">•</span>
                        <span class="font-medium">Grand total:</span>
                        <span class="font-semibold">{{ (int) ($footer['item_count'] ?? 0) }}</span> items
                        <span class="mx-2">•</span>
                        <span class="font-semibold">{{ number_format((float) $displayTotalGram, 3) }}</span>
                        total gram
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium">Batch totals</span>
                    <button type="button" @click="viewMode = (viewMode === 'batch' ? 'items' : 'batch')"
                        class="relative inline-flex h-6 w-12 items-center rounded-full border transition"
                        :class="viewMode === 'items' ? 'bg-primary-600 border-primary-600' :
                            'bg-slate-200 border-slate-300 dark:bg-slate-700 dark:border-slate-600'"
                        aria-label="Toggle batch/items view">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                            :class="viewMode === 'items' ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                    <span class="text-xs font-medium">All items</span>
                </div>

                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium">External/Lot</span>
                    <button type="button" @click="extraCols = !extraCols"
                        class="relative inline-flex h-6 w-12 items-center rounded-full border transition"
                        :class="extraCols ? 'bg-primary-600 border-primary-600' :
                            'bg-slate-200 border-slate-300 dark:bg-slate-700 dark:border-slate-600'"
                        aria-label="Toggle external id and lot/serial columns">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                            :class="extraCols ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                    <span class="text-xs font-medium" x-text="extraCols ? 'On' : 'Off'">Off</span>
                </div>
            </div>
        </div>

        <!-- Batch totals view -->
        <div @class([
            'overflow-x-auto' => true,
            'blur-sm pointer-events-none select-none' => empty($group->started_at),
        ]) x-show="viewMode === 'batch'" x-cloak>
            <table
                class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 border border-slate-200 dark:border-slate-700 border-separate border-spacing-0">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Batch</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                batch_id</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Product</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                product_name</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Quality</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                quality</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Total Weight</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                total_weight</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>ပန်းထိမ်အလျော့တွက်</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                goldsmith_deduction</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>ပန်းထိမ် လက်ခ</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                goldsmith_labor_fee</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>ကျောက်ချိန်</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                kyauk_weight</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>ကျောက်ဖိုး</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                stone_price</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-center text-slate-600 uppercase dark:text-slate-300 border-b border-slate-200 dark:border-slate-700">
                            <div>Post</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                is_post</div>
                        </th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                    @foreach ($batchSummaries as $batch)
                        @php
                            $bId = (int) $batch['batch_id'];
                            $bKey = 'b-' . $bId;
                            $isPosted = !empty($batch['is_post']);
                        @endphp
                        <tr @class([
                            'bg-slate-100/60 dark:bg-slate-900/60' => !$isPosted,
                            'bg-green-100 dark:bg-green-900/20 text-green-900 dark:text-green-200' => $isPosted,
                        ])>
                            <td
                                class="px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white select-none border-r border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-2">
                                    <span>#{{ $bId }}</span>
                                    {{-- <button type="button"
                                        @click="copyText(@js($bId)); markCopied('copy-{{ $bKey }}-id')"
                                        class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                        :class="isCopied('copy-{{ $bKey }}-id') ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span x-show="!isCopied('copy-{{ $bKey }}-id')"><x-icon name="duplicate" class="w-4 h-4" /></span>
                                        <span x-show="isCopied('copy-{{ $bKey }}-id')"><x-icon name="share" class="w-4 h-4" /></span>
                                    </button> --}}
                                </div>
                            </td>

                            <td
                                class="px-4 py-3 text-sm text-slate-900 dark:text-white border-r border-slate-200 dark:border-slate-700">
                                @php
                                    $bCountValue = (int) ($batch['count'] ?? 0);
                                    $bCountLabel = (string) $bCountValue;
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ $batch['product_name'] }}</span>
                                    <button type="button"
                                        @click="copyText(@js($batch['product_name'])); markCopied('copy-{{ $bKey }}-pn')"
                                        class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                        :class="isCopied('copy-{{ $bKey }}-pn') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span x-show="!isCopied('copy-{{ $bKey }}-pn')"><x-icon
                                                name="duplicate" class="w-4 h-4" /></span>
                                        <span x-show="isCopied('copy-{{ $bKey }}-pn')"><x-icon name="share"
                                                class="w-4 h-4" /></span>
                                    </button>

                                    <button type="button"
                                        @click="copyText(@js($bCountLabel)); markCopied('copy-{{ $bKey }}-cnt')"
                                        class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                                        title="Copy">
                                        <span>{{ $bCountValue }} items</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5"
                                            :class="isCopied('copy-{{ $bKey }}-cnt') ?
                                                'text-green-700 dark:text-green-300' :
                                                'text-slate-600 dark:text-slate-200'">
                                            <span x-show="!isCopied('copy-{{ $bKey }}-cnt')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $bKey }}-cnt')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                </div>
                            </td>

                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 border-r border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-2">
                                    <span>{{ $batch['quality'] }}</span>
                                </div>
                            </td>

                            @php
                                $bw =
                                    $batch['total_weight'] == 0 ? '' : number_format((float) $batch['total_weight'], 3);
                                $bded =
                                    ($batch['goldsmith_deduction'] ?? 0) == 0
                                        ? ''
                                        : number_format((float) $batch['goldsmith_deduction'], 3);
                                $blabor =
                                    ($batch['goldsmith_labor_fee'] ?? 0) == 0
                                        ? ''
                                        : (int) $batch['goldsmith_labor_fee'];
                                $bkg =
                                    ($batch['kyauk_weight'] ?? 0) == 0
                                        ? ''
                                        : number_format((float) $batch['kyauk_weight'], 3);
                                $bstone = '';
                                if (($batch['stone_price'] ?? 0) != 0) {
                                    $stoneHalf = ((float) ($batch['stone_price'] ?? 0)) / 2;
                                    $bstone =
                                        fmod($stoneHalf, 1.0) == 0.0
                                            ? (string) ((int) $stoneHalf)
                                            : rtrim(rtrim(number_format($stoneHalf, 1, '.', ''), '0'), '.');
                                }
                            @endphp

                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                @if ($bw !== '')
                                    <button type="button"
                                        @click="copyText(@js($bw)); markCopied('copy-{{ $bKey }}-tw'); $wire.set('batchPostState.{{ $bId }}', true)"
                                        class="inline-flex items-center justify-end gap-1 rounded-md border px-2 py-1 tabular-nums dark:border-slate-600"
                                        :class="isCopied('copy-{{ $bKey }}-tw') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span>{{ $bw }}</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5">
                                            <span x-show="!isCopied('copy-{{ $bKey }}-tw')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $bKey }}-tw')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                @endif
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                @if ($bded !== '')
                                    <button type="button"
                                        @click="copyText(@js($bded)); markCopied('copy-{{ $bKey }}-ded')"
                                        class="inline-flex items-center justify-end gap-1 rounded-md border px-2 py-1 tabular-nums dark:border-slate-600"
                                        :class="isCopied('copy-{{ $bKey }}-ded') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span>{{ $bded }}</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5">
                                            <span x-show="!isCopied('copy-{{ $bKey }}-ded')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $bKey }}-ded')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                @endif
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                @if ($blabor !== '')
                                    <button type="button"
                                        @click="copyText(@js($blabor)); markCopied('copy-{{ $bKey }}-labor')"
                                        class="inline-flex items-center justify-end gap-1 rounded-md border px-2 py-1 tabular-nums dark:border-slate-600"
                                        :class="isCopied('copy-{{ $bKey }}-labor') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span>{{ $blabor }}</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5">
                                            <span x-show="!isCopied('copy-{{ $bKey }}-labor')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $bKey }}-labor')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                @endif
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                @if ($bkg !== '')
                                    <button type="button"
                                        @click="copyText(@js($bkg)); markCopied('copy-{{ $bKey }}-kg')"
                                        class="inline-flex items-center justify-end gap-1 rounded-md border px-2 py-1 tabular-nums dark:border-slate-600"
                                        :class="isCopied('copy-{{ $bKey }}-kg') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span>{{ $bkg }}</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5">
                                            <span x-show="!isCopied('copy-{{ $bKey }}-kg')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $bKey }}-kg')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                @endif
                            </td>

                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                @if ($bstone !== '')
                                    <button type="button"
                                        @click="copyText(@js($bstone)); markCopied('copy-{{ $bKey }}-stone')"
                                        class="inline-flex items-center justify-end gap-1 rounded-md border px-2 py-1 tabular-nums dark:border-slate-600"
                                        :class="isCopied('copy-{{ $bKey }}-stone') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span>{{ $bstone }}</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5">
                                            <span x-show="!isCopied('copy-{{ $bKey }}-stone')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $bKey }}-stone')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" class="rounded border-slate-300"
                                    wire:model.live="batchPostState.{{ $bId }}" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- All items view -->
        <div @class([
            'overflow-auto max-h-[70vh]' => true,
            'blur-sm pointer-events-none select-none' => empty($group->started_at),
        ]) x-show="viewMode === 'items'" x-cloak>
            <table
                class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 border border-slate-200 dark:border-slate-700 border-separate border-spacing-0">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Batch</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                batch_id</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Product</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                product_name</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Quality</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                quality</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Total Weight</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                total_weight</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>ကျောက်ချိန်</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                kyauk_weight</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>ပန်းထိမ်အလျော့တွက်</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                goldsmith_deduction</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>ပန်းထိမ် လက်ခ</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                goldsmith_labor_fee</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>ကျောက်ဖိုး</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                stone_price</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>အမြတ်အလျော့</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                profit_loss</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>အမြတ်လက်ခ</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                profit_labor_fee</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Barcode</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                barcode</div>
                        </th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-center text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>Register</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                is_register</div>
                        </th>

                        <th x-show="extraCols" x-cloak
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-r border-slate-200 dark:border-slate-700">
                            <div>External ID</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                external_id</div>
                        </th>
                        <th x-show="extraCols" x-cloak
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300 sticky top-0 z-10 bg-slate-50 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-700">
                            <div>Lot/Serial</div>
                            <div
                                class="mt-0.5 text-[10px] font-normal normal-case tracking-normal text-slate-500 dark:text-slate-400">
                                lot_serial</div>
                        </th>
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-800 dark:divide-slate-700">
                    @foreach ($items as $item)
                        @php
                            $iKey = 'i-' . $item->id;
                            $itemBatchId = (int) ($item->batch_id ?? 0);
                            $isRegistered = (bool) ($item->is_register ?? false);
                        @endphp
                        <tr @class([
                            'bg-green-100 dark:bg-green-900/20 text-green-900 dark:text-green-200' => $isRegistered,
                        ])>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 select-none border-r border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-2">
                                    <span>#{{ $itemBatchId }}</span>

                                </div>
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-900 dark:text-white border-r border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-2">
                                    <span>{{ $item->product_name }}</span>
                                    <button type="button"
                                        @click="copyText(@js($item->product_name)); markCopied('copy-{{ $iKey }}-pn')"
                                        class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                        :class="isCopied('copy-{{ $iKey }}-pn') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span x-show="!isCopied('copy-{{ $iKey }}-pn')"><x-icon
                                                name="duplicate" class="w-4 h-4" /></span>
                                        <span x-show="isCopied('copy-{{ $iKey }}-pn')"><x-icon name="share"
                                                class="w-4 h-4" /></span>
                                    </button>
                                </div>
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 border-r border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-2">
                                    <span>{{ $item->quality }}</span>

                                </div>
                            </td>
                            @php
                                $ibc = (string) ($item->barcode ?? '');
                                $iexternalId = (string) ($item->external_id ?? '');
                                $ilotSerial = (string) ($item->lot_serial ?? '');
                                $itw = $item->total_weight == 0 ? '' : number_format((float) $item->total_weight, 3);
                                $ikg = $item->kyauk_weight == 0 ? '' : number_format((float) $item->kyauk_weight, 3);
                                $ided =
                                    $item->goldsmith_deduction == 0
                                        ? ''
                                        : number_format((float) $item->goldsmith_deduction, 3);
                                $ilabor = $item->goldsmith_labor_fee == 0 ? '' : (int) $item->goldsmith_labor_fee;
                                $istone = '';
                                if (!empty($item->stone_price)) {
                                    $stoneHalf = ((float) $item->stone_price) / 2;
                                    $istone =
                                        fmod($stoneHalf, 1.0) == 0.0
                                            ? (string) ((int) $stoneHalf)
                                            : rtrim(rtrim(number_format($stoneHalf, 1, '.', ''), '0'), '.');
                                }
                                $ipl =
                                    ($item->profit_loss ?? 0) == 0 ? '' : number_format((float) $item->profit_loss, 2);
                                $iprofitLabor = empty($item->profit_labor_fee) ? '' : (int) $item->profit_labor_fee;
                            @endphp
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                <span>{{ $itw }}</span>
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                <span>{{ $ikg }}</span>
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                <span>{{ $ided }}</span>
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                <span>{{ $ilabor }}</span>
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                @if ($istone !== '')
                                    <button type="button"
                                        @click="copyText(@js($istone)); markCopied('copy-{{ $iKey }}-stone')"
                                        class="inline-flex items-center justify-end gap-1 rounded-md border px-2 py-1 tabular-nums dark:border-slate-600"
                                        :class="isCopied('copy-{{ $iKey }}-stone') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span>{{ $istone }}</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5">
                                            <span x-show="!isCopied('copy-{{ $iKey }}-stone')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $iKey }}-stone')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                @endif
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                @if ($ipl !== '')
                                    <button type="button"
                                        @click="copyText(@js($ipl)); markCopied('copy-{{ $iKey }}-pl')"
                                        class="inline-flex items-center justify-end gap-1 rounded-md border px-2 py-1 tabular-nums dark:border-slate-600"
                                        :class="isCopied('copy-{{ $iKey }}-pl') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span>{{ $ipl }}</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5">
                                            <span x-show="!isCopied('copy-{{ $iKey }}-pl')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $iKey }}-pl')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                @endif
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                @if ($iprofitLabor !== '')
                                    <button type="button"
                                        @click="copyText(@js($iprofitLabor)); markCopied('copy-{{ $iKey }}-plf')"
                                        class="inline-flex items-center justify-end gap-1 rounded-md border px-2 py-1 tabular-nums dark:border-slate-600"
                                        :class="isCopied('copy-{{ $iKey }}-plf') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span>{{ $iprofitLabor }}</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5">
                                            <span x-show="!isCopied('copy-{{ $iKey }}-plf')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $iKey }}-plf')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                @endif
                            </td>
                            <td
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right border-r border-slate-200 dark:border-slate-700">
                                @if ($ibc !== '')
                                    <button type="button"
                                        @click="copyText(@js($ibc)); markCopied('copy-{{ $iKey }}-bc'); $wire.registerItem({{ (int) $item->id }})"
                                        class="inline-flex items-center justify-end gap-1 rounded-md border px-2 py-1 tabular-nums dark:border-slate-600"
                                        :class="isCopied('copy-{{ $iKey }}-bc') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span>{{ $ibc }}</span>
                                        <span class="inline-flex items-center justify-center w-5 h-5">
                                            <span x-show="!isCopied('copy-{{ $iKey }}-bc')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $iKey }}-bc')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </span>
                                    </button>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center border-r border-slate-200 dark:border-slate-700">
                                <input type="checkbox" class="rounded border-slate-300" @checked($item->is_register)
                                    wire:click="toggleRegister({{ $item->id }})" />
                            </td>

                            <td x-show="extraCols" x-cloak
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 border-r border-slate-200 dark:border-slate-700">
                                <span class="whitespace-nowrap">{{ $iexternalId }}</span>
                            </td>
                            <td x-show="extraCols" x-cloak
                                class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                <span class="whitespace-nowrap">{{ $ilotSerial }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div
            class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-slate-700 dark:text-slate-200">
                Total items: <span class="font-semibold">{{ (int) ($footer['item_count'] ?? 0) }}</span>
            </div>
            <div class="text-sm text-slate-700 dark:text-slate-200">
                Total gram: <span class="font-semibold">{{ number_format((float) $displayTotalGram, 3) }}</span>
            </div>
        </div>
    </div>
</div>
