<div x-data="{
    importOpen: false,
    viewMode: @js((string) ($group->purchase_status ?? '') === 'done' || (bool) ($group->is_purchase ?? false) ? 'items' : 'batch'),
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

        <div class="flex items-center gap-2">
            {{-- <button type="button" @click="importOpen = true"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white rounded-md bg-primary-600 hover:bg-primary-700">
                <x-icon name="upload" class="w-4 h-4 mr-2" />
                Import Excel
            </button> --}}

            <button type="button" wire:click="start"
                class="px-3 py-2 text-sm font-medium border rounded-md border-slate-300 text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                Start
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
                        Required columns: <span class="font-medium">product_name</span>, <span
                            class="font-medium">quality</span>,
                        <span class="font-medium">total_weight</span>, <span class="font-medium">l_gram</span>, <span
                            class="font-medium">l_mmk</span>, <span class="font-medium">kyauk_gram</span>.
                        Optional: <span class="font-medium">barcode</span>, <span class="font-medium">Batch
                            Number</span>. If blank, batch IDs are auto-generated by matching the 6 fields above.
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-300">Limits: max 12 unique batch IDs and
                            120 total items per group.</div>
                    </div>

                    <div>
                        <input type="file" wire:model="importFile" accept=".xlsx,.csv,.ods"
                            class="block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:text-slate-200 dark:file:bg-slate-700 dark:file:text-slate-100" />
                        @error('importFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div wire:loading wire:target="importFile" class="mt-2 text-sm text-slate-500">Uploading…</div>
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
            $anyBatchPosted = false;
            $postedBatchIds = [];
            if ($showGrandTotal) {
                foreach ($batchSummaries ?? [] as $b) {
                    if (!empty($b['is_post'])) {
                        $anyBatchPosted = true;
                        $postedBatchIds[(int) ($b['batch_id'] ?? 0)] = true;
                        break;
                    }
                }
            } else {
                foreach ($batchSummaries ?? [] as $b) {
                    if (!empty($b['is_post'])) {
                        $postedBatchIds[(int) ($b['batch_id'] ?? 0)] = true;
                    }
                }
            }
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
                        <span
                            class="font-semibold">{{ number_format((float) ($footer['total_weight'] ?? 0), 3) }}</span>
                        total gram
                    </span>
                @endif
            </div>

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
        </div>

        <!-- Batch totals view -->
        <div @class([
            'overflow-x-auto' => true,
            'blur-sm pointer-events-none select-none' => empty($group->started_at),
        ]) x-show="viewMode === 'batch'" x-cloak>
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Batch</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Product</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Quality</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300">
                            Total Weight</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300">
                            L Gram</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300">
                            L MMK</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300">
                            Kyauk Gram</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-center text-slate-600 uppercase dark:text-slate-300">
                            Post</th>
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
                            'bg-green-100 dark:bg-green-900/20 text-green-900 dark:text-green-200 line-through' => $isPosted,
                        ])>
                            <td class="px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white">
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
                                    <span
                                        class="text-xs text-slate-500 dark:text-slate-300">({{ (int) $batch['count'] }}
                                        items)</span>
                                </div>
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
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
                                </div>
                            </td>

                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                <div class="flex items-center gap-2">
                                    <span>{{ $batch['quality'] }}</span>

                                </div>
                            </td>

                            @php
                                $bw =
                                    $batch['total_weight'] == 0 ? '' : number_format((float) $batch['total_weight'], 3);
                                $blg = $batch['l_gram'] == 0 ? '' : number_format((float) $batch['l_gram'], 3);
                                $bmmk = $batch['l_mmk'] == 0 ? '' : (int) $batch['l_mmk'];
                                $bkg = $batch['kyauk_gram'] == 0 ? '' : number_format((float) $batch['kyauk_gram'], 3);
                            @endphp

                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span>{{ $bw }}</span>
                                    <button type="button"
                                        @click="copyText(@js($bw)); markCopied('copy-{{ $bKey }}-tw')"
                                        class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                        :class="isCopied('copy-{{ $bKey }}-tw') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span x-show="!isCopied('copy-{{ $bKey }}-tw')"><x-icon
                                                name="duplicate" class="w-4 h-4" /></span>
                                        <span x-show="isCopied('copy-{{ $bKey }}-tw')"><x-icon name="share"
                                                class="w-4 h-4" /></span>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span>{{ $blg }}</span>
                                    @if ($blg !== '')
                                        <button type="button"
                                            @click="copyText(@js($blg)); markCopied('copy-{{ $bKey }}-lg')"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                            :class="isCopied('copy-{{ $bKey }}-lg') ?
                                                'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                                'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                            title="Copy">
                                            <span x-show="!isCopied('copy-{{ $bKey }}-lg')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $bKey }}-lg')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span>{{ $bmmk }}</span>
                                    @if ($bmmk !== '')
                                        <button type="button"
                                            @click="copyText(@js($bmmk)); markCopied('copy-{{ $bKey }}-mmk')"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                            :class="isCopied('copy-{{ $bKey }}-mmk') ?
                                                'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                                'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                            title="Copy">
                                            <span x-show="!isCopied('copy-{{ $bKey }}-mmk')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $bKey }}-mmk')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span>{{ $bkg }}</span>
                                    @if ($bkg !== '')
                                        <button type="button"
                                            @click="copyText(@js($bkg)); markCopied('copy-{{ $bKey }}-kg')"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                            :class="isCopied('copy-{{ $bKey }}-kg') ?
                                                'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                                'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                            title="Copy">
                                            <span x-show="!isCopied('copy-{{ $bKey }}-kg')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $bKey }}-kg')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </button>
                                    @endif
                                </div>
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
            'overflow-x-auto' => true,
            'blur-sm pointer-events-none select-none' => empty($group->started_at),
        ]) x-show="viewMode === 'items'" x-cloak>
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/40">
                    <tr>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Batch</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Product</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-slate-600 uppercase dark:text-slate-300">
                            Quality</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300">
                            Total Weight</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300">
                            L Gram</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300">
                            L MMK</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-slate-600 uppercase dark:text-slate-300">
                            Kyauk Gram</th>
                        <th
                            class="px-4 py-3 text-xs font-semibold tracking-wider text-center text-slate-600 uppercase dark:text-slate-300">
                            Register</th>
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
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                <div class="flex items-center gap-2">
                                    <span>#{{ $itemBatchId }}</span>

                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">
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
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                <div class="flex items-center gap-2">
                                    <span>{{ $item->quality }}</span>

                                </div>
                            </td>
                            @php
                                $itw = $item->total_weight == 0 ? '' : number_format((float) $item->total_weight, 3);
                                $ilg = $item->l_gram == 0 ? '' : number_format((float) $item->l_gram, 3);
                                $immk = $item->l_mmk == 0 ? '' : (int) $item->l_mmk;
                                $ikg = $item->kyauk_gram == 0 ? '' : number_format((float) $item->kyauk_gram, 3);
                            @endphp
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span>{{ $itw }}</span>
                                    <button type="button"
                                        @click="copyText(@js($itw)); markCopied('copy-{{ $iKey }}-tw')"
                                        class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                        :class="isCopied('copy-{{ $iKey }}-tw') ?
                                            'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                            'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                        title="Copy">
                                        <span x-show="!isCopied('copy-{{ $iKey }}-tw')"><x-icon
                                                name="duplicate" class="w-4 h-4" /></span>
                                        <span x-show="isCopied('copy-{{ $iKey }}-tw')"><x-icon name="share"
                                                class="w-4 h-4" /></span>
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span>{{ $ilg }}</span>
                                    @if ($ilg !== '')
                                        <button type="button"
                                            @click="copyText(@js($ilg)); markCopied('copy-{{ $iKey }}-lg')"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                            :class="isCopied('copy-{{ $iKey }}-lg') ?
                                                'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                                'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                            title="Copy">
                                            <span x-show="!isCopied('copy-{{ $iKey }}-lg')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $iKey }}-lg')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span>{{ $immk }}</span>
                                    @if ($immk !== '')
                                        <button type="button"
                                            @click="copyText(@js($immk)); markCopied('copy-{{ $iKey }}-mmk')"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                            :class="isCopied('copy-{{ $iKey }}-mmk') ?
                                                'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                                'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                            title="Copy">
                                            <span x-show="!isCopied('copy-{{ $iKey }}-mmk')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $iKey }}-mmk')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <span>{{ $ikg }}</span>
                                    @if ($ikg !== '')
                                        <button type="button"
                                            @click="copyText(@js($ikg)); markCopied('copy-{{ $iKey }}-kg')"
                                            class="inline-flex items-center justify-center w-8 h-8 border rounded-md dark:border-slate-600"
                                            :class="isCopied('copy-{{ $iKey }}-kg') ?
                                                'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' :
                                                'border-slate-300 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700'"
                                            title="Copy">
                                            <span x-show="!isCopied('copy-{{ $iKey }}-kg')"><x-icon
                                                    name="duplicate" class="w-4 h-4" /></span>
                                            <span x-show="isCopied('copy-{{ $iKey }}-kg')"><x-icon
                                                    name="share" class="w-4 h-4" /></span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" class="rounded border-slate-300" @checked($item->is_register)
                                    wire:click="toggleRegister({{ $item->id }})" />
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
                Total gram: <span
                    class="font-semibold">{{ number_format((float) ($footer['total_weight'] ?? 0), 3) }}</span>
            </div>
        </div>
    </div>
</div>
