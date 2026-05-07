<div class="mx-auto max-w-7xl px-4 py-6" x-data="{ showSeverityModal: @entangle('showSeverityModal'), showMessageModal: @entangle('showMessageModal') }">
    @if (session('message'))
        <div class="mb-3 rounded-xl bg-emerald-100 px-4 py-3 text-emerald-800">{{ session('message') }}</div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Issues</h1>
        <a href="{{ route('operation.it.issues.create') }}" wire:navigate
            class="rounded-xl bg-slate-900 px-4 py-2 text-white">New Issue</a>
    </div>
    <section class="mb-4 rounded-2xl border bg-white p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div wire:ignore x-data="{ range: @entangle('issueDateRange').live }" x-init="setTimeout(() => {
                if (!window.flatpickr) return;
                flatpickr($refs.issueRange, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    allowInput: true,
                    defaultDate: range ? range.split(' to ') : null,
                    onChange: (selectedDates, dateStr) => {
                        range = dateStr || null;
                    },
                });
            }, 0)">
                <label class="mb-1 block text-xs font-medium text-slate-600">Issue Date Range</label>
                <input x-ref="issueRange" type="text" class="w-full rounded-xl border px-3 py-2 text-sm"
                    placeholder="Select date range">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                <select wire:model.live="statusFilter" class="w-full rounded-xl border px-3 py-2 text-sm">
                    <option value="">All Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->code }}">{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Branch</label>
                <select wire:model.live="branchFilter" class="w-full rounded-xl border px-3 py-2 text-sm">
                    <option value="">All Branches</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>
    {{-- filter section --}}
    <!-- <div x-data="{ state: $wire.entangle('is_erp').live }" class="p-4"> -->
        <!-- Button -->
        <!-- <button @click="state = !state" @swipe:left="state = false" @swipe:right="state = true"
            class="relative inline-flex h-8 w-14 items-center rounded-full transition-colors duration-200"
            :class="state ? 'bg-green-500' : 'bg-gray-300'"> -->
            <!-- Sliding Circle -->
            <!-- <span class="inline-block h-6 w-6 transform rounded-full bg-white transition duration-200"
                :class="state ? 'translate-x-7' : 'translate-x-1'"></span>
        </button> -->
        <!-- For debugging -->
        <!-- <p>Livewire value: <span x-text="state"></span> {{ $is_erp }}</p> -->
    <!-- </div> -->

    {{-- <div>
        <div x-data="{ state: $wire.entangle('is_erp') }" class="p-4">
            <!-- Button -->
            <button @click="state = !state" class="px-6 py-2 rounded-full transition-colors duration-300"
                :class="state ? 'bg-green-500' : 'bg-red-500'" <!-- Swipe gestures -->
                @swipe:left="state = false"
                @swipe:right="state = true"
                >
                <span x-text="state ? 'True' : 'False'"></span>
            </button>
        </div>
        {{ $is_erp }}
    </div> --}}


    <section class="mb-6 rounded-2xl border bg-white p-4"
        x-data="{ tab: 'must', prevTab: 'must', setTab(next) { if (this.tab === next) return; this.prevTab = this.tab; this.tab = next; }, swipeStartX: 0 }"
        @touchstart="swipeStartX = $event.changedTouches[0].screenX"
        @touchend="
            const endX = $event.changedTouches[0].screenX;
            const delta = endX - swipeStartX;
            if (Math.abs(delta) > 40) {
                if (delta < 0) setTab('third');
                if (delta > 0) setTab('must');
            }
        ">
        <div class="mb-4 flex items-center gap-2">
            <button @click="setTab('must')" class="rounded-xl px-3 py-2 text-sm"
                :class="tab === 'must' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'">
                Priority & Important
            </button>
            <button @click="setTab('third')" class="rounded-xl px-3 py-2 text-sm"
                :class="tab === 'third' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'">
                Third Party Resolver
            </button>
        </div>

        <div class="relative overflow-hidden">
            <div x-show="tab === 'must'" x-cloak
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 -translate-x-4">
                <h2 class="text-lg font-semibold">Priority & Important Report (Must Do List)</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="p-2 text-left"></th>
                            <th class="p-2 text-left">Issue</th>
                            <th class="p-2 text-left">Reported By</th>
                            <th class="p-2 text-left">Priority / Important</th>
                            <th class="p-2 text-left">Due Date</th>
                            <th class="p-2 text-left">Assign to Developer</th>
                            <th class="p-2 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mustDoIssues as $issue)
                            <tr class="border-t" wire:key="must-issue-{{ $issue->id }}">
                                <td class="p-2">
                                    @php
                                        $seq = $issue->resolution_sequence;
                                        $seqColor = match (true) {
                                            $seq === null => 'bg-slate-300 text-slate-700',
                                            $seq === 1 => 'bg-rose-500 text-white',
                                            $seq === 2 => 'bg-orange-500 text-white',
                                            $seq === 3 => 'bg-amber-500 text-white',
                                            $seq === 4 => 'bg-emerald-500 text-white',
                                            default => 'bg-sky-500 text-white',
                                        };
                                    @endphp
                                    <div x-data="{ editing: false, value: '' }" class="inline-block">
                                        <button type="button" x-show="!editing"
                                            @click="{{ $seq === null ? '$wire.addIssueSequenceToEnd(' . $issue->id . ')' : 'value = \'\'; editing = true; $nextTick(() => $refs.seqInput.focus())' }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold {{ $seqColor }}">
                                            {{ $seq ?? '+' }}
                                        </button>
                                        <input x-ref="seqInput" x-show="editing" x-cloak x-model="value" type="number"
                                            min="1"
                                            @keydown.enter.prevent="editing = false; $wire.setIssueSequence({{ $issue->id }}, value === '' ? null : parseInt(value))"
                                            @blur="editing = false; $wire.setIssueSequence({{ $issue->id }}, value === '' ? null : parseInt(value))"
                                            class="w-16 rounded-lg border px-2 py-1 text-xs" />
                                    </div>
                                </td>
                                <td class="p-2">{{ $issue->title }}</td>
                                <td class="p-2">
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $issue->creator?->profile_photo_url }}" alt="profile"
                                            class="h-8 w-8 rounded-full object-cover">
                                        <span class="text-xs font-medium text-slate-700">
                                            {{ $issue->creator?->name ?? '-' }}
                                            <span class="text-slate-500">({{ $issue->creator?->branch?->name ?? '-' }})</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="p-2">
                                    <span class="inline-flex overflow-hidden rounded-full border text-xs font-medium">
                                        <span class="bg-rose-100 px-2 py-1 text-rose-700">
                                           {{ $issue->priority?->name ?? '-' }}
                                        </span>
                                        <span class="bg-blue-100 px-2 py-1 text-blue-700">
                                            {{ $issue->importance?->name ?? '-' }}
                                        </span>
                                    </span>
                                </td>
                                <td class="p-2">{{ $issue->due_date?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="p-2">
                                    <button
                                        wire:click="toggleThirdPartyResolver({{ $issue->id }}, {{ $issue->is_third_party_resolver ? 'false' : 'true' }})"
                                        class="relative inline-flex h-7 w-14 items-center rounded-full transition-colors duration-200 {{ $issue->is_third_party_resolver ? 'bg-green-500' : 'bg-slate-300' }}">
                                        <span
                                            class="inline-block h-5 w-5 transform rounded-full bg-white transition duration-200 {{ $issue->is_third_party_resolver ? 'translate-x-8' : 'translate-x-1' }}"></span>
                                    </button>
                                </td>
                                <td class="p-2">
                                    <div class="flex gap-2">
                                        <x-button.circle icon="sparkles" wire:click="selectIssue({{ $issue->id }})" positive flat />
                                        <x-button.circle icon="flag" wire:click="openSeverityForIssue({{ $issue->id }})" info flat />
                                        <x-button.circle negative wire:confirm="Are you sure?"
                                            wire:click="deleteIssue({{ $issue->id }})" rounded icon="trash" flat />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-2 text-slate-500">No must-do issues.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    </table>
                </div>
            </div>

            <div x-show="tab === 'third'" x-cloak
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-4">
                <h2 class="text-lg font-semibold">Third Party Resolver Report</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="p-2 text-left"></th>
                            <th class="p-2 text-left">Issue</th>
                            <th class="p-2 text-left">Priority / Important</th>
                            <th class="p-2 text-left">Followed Up Today</th>
                            <th class="p-2 text-left">Last Follow Up</th>
                            <th class="p-2 text-left">Follow Up By</th>
                            <th class="p-2 text-left">Finish Time</th>
                            <th class="p-2 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($thirdPartyIssues as $issue)
                            <tr class="border-t" wire:key="third-issue-{{ $issue->id }}">
                                <td class="p-2">
                                    @php
                                        $seq = $issue->resolution_sequence;
                                        $seqColor = match (true) {
                                            $seq === null => 'bg-slate-300 text-slate-700',
                                            $seq === 1 => 'bg-rose-500 text-white',
                                            $seq === 2 => 'bg-orange-500 text-white',
                                            $seq === 3 => 'bg-amber-500 text-white',
                                            $seq === 4 => 'bg-emerald-500 text-white',
                                            default => 'bg-sky-500 text-white',
                                        };
                                    @endphp
                                    <div x-data="{ editing: false, value: '' }" class="inline-block">
                                        <button type="button" x-show="!editing"
                                            @click="{{ $seq === null ? '$wire.addIssueSequenceToEnd(' . $issue->id . ')' : 'value = \'\'; editing = true; $nextTick(() => $refs.seqInput.focus())' }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold {{ $seqColor }}">
                                            {{ $seq ?? '+' }}
                                        </button>
                                        <input x-ref="seqInput" x-show="editing" x-cloak x-model="value" type="number"
                                            min="1"
                                            @keydown.enter.prevent="editing = false; $wire.setIssueSequence({{ $issue->id }}, value === '' ? null : parseInt(value))"
                                            @blur="editing = false; $wire.setIssueSequence({{ $issue->id }}, value === '' ? null : parseInt(value))"
                                            class="w-16 rounded-lg border px-2 py-1 text-xs" />
                                    </div>
                                </td>
                                <td class="p-2">{{ $issue->title }}</td>
                                <td class="p-2">
                                    <span class="inline-flex overflow-hidden rounded-full border text-xs font-medium">
                                        <span class="bg-rose-100 px-2 py-1 text-rose-700">
                                           {{ $issue->priority?->name ?? '-' }} 
                                        </span>
                                        <span class="bg-blue-100 px-2 py-1 text-blue-700">
                                            {{ $issue->importance?->name ?? '-' }}
                                        </span>
                                    </span>
                                </td>
                                <td class="p-2">{{ $issue->follow_up_date?->isToday() ? 'Yes' : 'No' }}</td>
                                <td class="p-2">{{ $issue->follow_up_date?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td class="p-2">{{ $issue->followUpUpdater?->name ?? '-' }}</td>
                                <td class="p-2">{{ $issue->resolution_days }} day(s)</td>
                                <td class="p-2">
                                    <div class="flex gap-2">
                                        <x-button.circle icon="sparkles" wire:click="selectIssue({{ $issue->id }})" positive flat />
                                        <x-button.circle icon="flag" wire:click="openSeverityForIssue({{ $issue->id }})" info flat />
                                        <x-button.circle negative wire:confirm="Are you sure?"
                                            wire:click="deleteIssue({{ $issue->id }})" rounded icon="trash" flat />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="p-2 text-slate-500">No third-party resolver issues.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <x-modal.card title="{{ $selectedIssue ? 'Manage Issue #' . $selectedIssue->id : 'Manage Issue' }}"
        wire:model.defer="showManageModal" blur>
        @if ($selectedIssue)
            <section class="space-y-5">

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <input wire:model.defer="title" class="rounded-xl border px-3 py-2" placeholder="Issue title">
                <select wire:model.defer="resolution_department_id" class="rounded-xl border px-3 py-2">
                    <option value="">Resolution Department</option>
                    @foreach ($departments as $dep)
                        <option value="{{ $dep->id }}">{{ $dep->name }}</option>
                    @endforeach
                </select>
                <textarea wire:model.defer="description" rows="3" class="rounded-xl border px-3 py-2 md:col-span-2"
                    placeholder="Description"></textarea>
                <textarea wire:model.defer="proposed_solution" rows="2"
                    class="rounded-xl border px-3 py-2 md:col-span-2" placeholder="Proposed solution"></textarea>
                <select wire:model.defer="assigned_user_id" class="rounded-xl border px-3 py-2">
                    <option value="">Assigned User (optional)</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                <input type="datetime-local" wire:model.defer="due_date" class="rounded-xl border px-3 py-2"
                    placeholder="Due date">
                <input type="number" min="1" wire:model.defer="resolution_sequence" class="rounded-xl border px-3 py-2"
                    placeholder="Fix sequence (1,2,3...)">
                <label class="flex items-center gap-2 rounded-xl border px-3 py-2">
                    <input type="checkbox" wire:model.defer="is_third_party_resolver">
                    <span>Third-party resolver</span>
                </label>
                <input type="datetime-local" wire:model.defer="follow_up_date" class="rounded-xl border px-3 py-2"
                    placeholder="Follow up date">
                <button wire:click="saveIssue" class="rounded-xl bg-slate-900 px-4 py-2 text-white text-sm">Save
                    Issue</button>
            </div>

            @php
                $currentCode = $selectedIssue->status?->code ?? 'OPEN';
                $currentIndex = array_search($currentCode, $statusSteps, true);
                $nextCodes = $transitionMap[$currentCode] ?? [];
            @endphp

            <div class="space-y-3 rounded-xl border p-3">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($statusSteps as $idx => $code)
                        @php
                            $isCurrent = $code === $currentCode;
                            $isPassed = $currentIndex !== false && $idx < $currentIndex;
                        @endphp
                        <span
                            class="rounded-full px-3 py-1 text-xs {{ $isCurrent ? 'bg-sky-600 text-white' : ($isPassed ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700') }}">
                            {{ str_replace('_', ' ', $code) }}
                        </span>
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-2">
                    @forelse($nextCodes as $toCode)
                        <button wire:click="transitionTo('{{ $toCode }}')"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-xs hover:bg-slate-100">
                            {{ str_replace('_', ' ', $currentCode) }} -> {{ str_replace('_', ' ', $toCode) }}
                        </button>
                    @empty
                        <span class="text-xs text-slate-500">No further transitions available.</span>
                    @endforelse
                </div>
            </div>

            {{-- <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr,auto]">
                <select wire:model="status_code" class="rounded-xl border px-3 py-2">
                    <option value="">Change Status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->code }}">{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
                <button wire:click="changeStatus" class="rounded-xl border px-4 py-2 text-sm">Apply Status</button>
            </div> --}}

            <div>
                {{-- <textarea wire:model.defer="message" rows="2" class="w-full rounded-xl border px-3 py-2"
                    placeholder="Add discussion message"></textarea> --}}
                {{-- <button wire:click="addMessage" class="rounded-xl border px-4 py-2 text-sm">Add Message</button> --}}
                <x-button icon="chat-alt-2" label="Add Message" class="px-4 py-2 text-sm"
                    wire:click="openMessageModal" />
                {{-- <x-icon name="chat-alt-2" class="inline h-5 w-5 text-slate-500" wire:click="openMessageModal" /> --}}
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <h3 class="font-medium">Messages</h3>
                    <div class="mt-2 max-h-52 space-y-2 overflow-auto">
                        @forelse($selectedIssue->messages as $m)
                            @if ($m->is_log_note)
                                <blockquote
                                    class="rounded border-l-4 border-yellow-500 bg-yellow-100 p-2 text-xs text-yellow-700">
                                    <span class="font-semibold">{{ $m->creator?->name ?? 'Unknown' }}:</span>
                                    {{ $m->message }}
                                </blockquote>
                            @else
                                <div class="rounded border p-2 text-xs"><span
                                        class="font-semibold">{{ $m->creator?->name ?? 'Unknown' }}:</span>
                                    {{ $m->message }}</div>
                            @endif
                        @empty
                            <p class="text-xs text-slate-500">No messages yet.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h3 class="font-medium">Activity Log</h3>
                    <div class="mt-2 max-h-52 space-y-2 overflow-auto">
                        @forelse($selectedIssue->activityLogs as $log)
                            <div class="rounded border p-2 text-xs"><span
                                    class="font-semibold">{{ $log->action }}</span> - {{ $log->description }}
                                ({{ $log->performer?->name ?? 'Unknown' }})
                            </div>
                        @empty
                            <p class="text-xs text-slate-500">No activities yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            </section>
        @endif
    </x-modal.card>


    {{-- Old Codes --}}
    {{-- <h2 class="text-lg font-semibold">Manage Issue #{{ $selectedIssue->id }}</h2>
    <div class="flex items-center gap-2">
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs">Priority:
            {{ $selectedIssue->priority?->name ?? '-' }}</span>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs">Importance:
            {{ $selectedIssue->importance?->name ?? '-' }}</span>
        <button wire:click="openSeverityModal" class="rounded-xl border px-3 py-2 text-xs">Priority &
            Importance</button>
    </div> --}}

    @if ($showSeverityModal)
        <div class="fixed inset-0 z-50 bg-black/40 p-4" wire:click="closeSeverityModal">
            <div class="mx-auto mt-16 max-w-3xl rounded-2xl bg-white p-5" wire:click.stop>
                <h3 class="text-lg font-semibold">Priority & Importance</h3>
                <p class="mt-1 text-xs text-slate-500">Select a radio option. Value auto-saves immediately.</p>
                <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <h4 class="mb-2 text-sm font-semibold">Priority</h4>
                        <div class="space-y-2">
                            @foreach ($priorities as $p)
                                @php
                                    $pColor =
                                        $p->level <= 1
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : ($p->level == 2
                                                ? 'bg-amber-100 text-amber-700'
                                                : 'bg-rose-100 text-rose-700');
                                @endphp
                                <label class="flex items-center justify-between rounded-xl border px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" wire:model.live="issue_priority_id"
                                            value="{{ $p->id }}">
                                        <span>{{ $p->name }}</span>
                                    </div>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs {{ $pColor }}">L{{ $p->level }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-2 text-sm font-semibold">Importance</h4>
                        <div class="space-y-2">
                            @foreach ($importanceLevels as $i)
                                @php
                                    $iColor =
                                        $i->level <= 1
                                            ? 'bg-blue-100 text-blue-700'
                                            : ($i->level == 2
                                                ? 'bg-orange-100 text-orange-700'
                                                : 'bg-fuchsia-100 text-fuchsia-700');
                                @endphp
                                <label class="flex items-center justify-between rounded-xl border px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" wire:model.live="issue_importance_id"
                                            value="{{ $i->id }}">
                                        <span>{{ $i->name }}</span>
                                    </div>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs {{ $iColor }}">L{{ $i->level }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button wire:click="closeSeverityModal" class="rounded border px-3 py-2 text-sm">Close</button>
                </div>
            </div>
        </div>
    @endif


    {{-- Message Modal --}}
    @if ($showMessageModal)
        <div class="fixed inset-0 z-50 bg-black/40 p-4" wire:click="closeMessageModal">
            <div class="mx-auto mt-16 max-w-xl rounded-2xl bg-white p-5" wire:click.stop>
                <h3 class="text-lg font-semibold"> {{ $isDiscussionMode ? 'Add Discussion' : 'Add Log Note' }}</h3>
                <div class="mt-4 space-y-3">

                    <div class="flex items-center justify-between  p-2">
                        {{-- <div>
                           
                            <p class="text-md  {{ $isDiscussionMode ? 'text-yellow-600' : 'text-slate-600' }}">
                               </p>
                        </div> --}}
                        <button type="button" wire:click="$toggle('isDiscussionMode')"
                            class="relative inline-flex h-7 w-14 items-center rounded-full transition {{ $isDiscussionMode ? 'bg-yellow-400' : 'bg-slate-300' }}">
                            <span
                                class="inline-block h-5 w-5 transform rounded-full bg-white transition {{ $isDiscussionMode ? 'translate-x-8' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                    <textarea wire:model.defer="message" rows="3" class="w-full rounded-xl border px-3 py-2"
                        placeholder="Write your message"></textarea>
                    @error('message')
                        <p class="text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="closeMessageModal" class="rounded border px-3 py-2 text-sm">Cancel</button>
                    <button wire:click="addMessage" class="rounded bg-slate-900 px-3 py-2 text-sm text-white">Save
                        Message</button>
                </div>
            </div>
        </div>
    @endif

    <x-modal.card title="Close Issue - Root Cause" wire:model.defer="showCloseModal" blur>
        <div class="space-y-4">
            <p class="text-sm text-slate-600">Please select root cause(s) before changing status to Closed.</p>

            <div class="space-y-2">
                @foreach ($rootCauses as $rootCause)
                    <label class="flex items-center gap-2 rounded-lg border px-3 py-2">
                        <input type="checkbox" wire:model="selectedRootCauseIds" value="{{ $rootCause->id }}">
                        <span class="text-sm">{{ $rootCause->name }}</span>
                    </label>
                @endforeach
            </div>

            <label class="flex items-center gap-2 rounded-lg border px-3 py-2">
                <input type="checkbox" wire:model.live="useOtherRootCause">
                <span class="text-sm font-medium">Other</span>
            </label>

            @if ($useOtherRootCause)
                <input type="text" wire:model.defer="otherRootCauseName" class="w-full rounded-lg border px-3 py-2 text-sm"
                    placeholder="Type other root cause">
                @error('otherRootCauseName')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror
            @endif

            @error('selectedRootCauseIds')
                <p class="text-xs text-rose-600">{{ $message }}</p>
            @enderror

            <div class="flex justify-end gap-2">
                <button wire:click="closeCloseModal" class="rounded border px-3 py-2 text-sm">Cancel</button>
                <button wire:click="closeIssue" class="rounded bg-slate-900 px-3 py-2 text-sm text-white">Close Issue</button>
            </div>
        </div>
    </x-modal.card>
</div>
