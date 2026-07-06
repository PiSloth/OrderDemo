<div class="space-y-6" x-data="{ firstStepQueueOpen: false, imageOpen: false, activeImage: null }">
    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('message') }}
        </div>
    @endif

    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white">
        <p class="text-sm uppercase tracking-[0.25em] text-slate-300">Approvals</p>
        <h2 class="mt-2 text-3xl font-semibold">Review KPI submissions in sequence.</h2>
        <p class="mt-3 max-w-3xl text-sm text-slate-200">
            The second approver only sees submissions after the first approver has approved them.
        </p>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <article
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    <!-- QUICK CAROUSEL APPROVALS -->
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between border-b border-slate-100 pb-5 dark:border-slate-800">
            <div>
                <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Quick Approval Carousel</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Filter and approve submissions slide-by-slide.</p>
            </div>
            
            <!-- Filters -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 md:w-auto">
                <!-- Employee Filter (No search) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-slate-400">Employee</label>
                    <x-native-select wire:model.live="filterEmployeeId" class="mt-1">
                        <option value="all">All Employees</option>
                        @foreach($filterEmployees as $emp)
                            <option value="{{ $emp['id'] }}">{{ $emp['name'] }}</option>
                        @endforeach
                    </x-native-select>
                </div>

                <!-- Month Filter (Flatpickr Month Select) -->
                <div class="relative" wire:ignore x-data="{
                    monthYear: @entangle('filterDate').live,
                    picker: null,
                    initPicker() {
                        if (!window.flatpickr || !window.monthSelectPlugin) {
                            setTimeout(() => this.initPicker(), 100);
                            return;
                        }
                        if (!this.$refs.monthPicker) {
                            setTimeout(() => this.initPicker(), 50);
                            return;
                        }
                        if (this.$refs.monthPicker._flatpickr) {
                            return;
                        }
                
                        const alpine = this;
                
                        this.picker = window.flatpickr(this.$refs.monthPicker, {
                            plugins: [new window.monthSelectPlugin({
                                shorthand: true,
                                dateFormat: 'Y-m',
                                altFormat: 'F Y',
                            })],
                            altInput: true,
                            altInputClass: 'mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-700 focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100',
                            allowInput: true,
                            defaultDate: (alpine.monthYear && String(alpine.monthYear).length === 7) ? alpine.monthYear : null,
                            appendTo: document.body,
                            onReady(selectedDates, dateStr, instance) {
                                try {
                                    if (instance && instance.calendarContainer) {
                                        instance.calendarContainer.style.zIndex = '9999';
                                    }
                                } catch (e) {}
                                if (instance?.altInput) {
                                    instance.altInput.placeholder = 'Select Month';
                                }
                            },
                            onChange(selectedDates, dateStr) {
                                alpine.monthYear = dateStr;
                            }
                        });
                
                        this.$watch('monthYear', (newVal) => {
                            if (this.picker) {
                                this.picker.setDate(newVal || null, false);
                            }
                        });
                    }
                }" x-init="initPicker()">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-slate-400">Month</label>
                    <input type="text" x-ref="monthPicker" class="hidden">
                </div>

                <!-- Template Filter (Searchable with WireUI Select) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider dark:text-slate-400">Template</label>
                    <x-select placeholder="Search template" wire:model.live="filterTemplateId"
                        :options="$filterTemplates" option-label="title" option-value="id" />
                </div>
            </div>
        </div>

        @if ($filteredSteps->isNotEmpty())
            <div x-data="{ carouselIndex: 0, count: {{ $filteredSteps->count() }}, remark: '' }" 
                 wire:ignore.self
                 x-effect="count = {{ $filteredSteps->count() }}; if (carouselIndex >= count) { carouselIndex = Math.max(0, count - 1); }"
                 class="relative mt-6 overflow-hidden">
                
                <div class="relative min-h-[400px]">
                    @foreach ($filteredSteps as $index => $step)
                        <div x-show="carouselIndex === {{ $index }}" 
                             x-transition:enter="transition ease-out duration-300 transform" 
                             x-transition:enter-start="opacity-0 translate-x-12" 
                             x-transition:enter-end="opacity-100 translate-x-0" 
                             x-transition:leave="transition ease-in duration-200 transform absolute top-0 left-0 w-full" 
                             x-transition:leave-start="opacity-100 translate-x-0" 
                             x-transition:leave-end="opacity-0 -translate-x-12" 
                             class="space-y-6"
                             wire:key="carousel-step-{{ $step->id }}">
                            
                            <!-- Card Content Wrapper -->
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 p-5 dark:border-slate-800 dark:bg-slate-800/40">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                                {{ $step->submission?->instance?->template?->title }}
                                            </h4>
                                            <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-medium uppercase tracking-[0.15em] text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                                                Step {{ $step->step_order }}
                                            </span>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium uppercase tracking-[0.15em] text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                {{ $step->submission?->instance?->template?->group?->name ?? 'No KPI Group' }}
                                            </span>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium uppercase tracking-[0.15em] text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                {{ $step->submission?->instance?->template?->frequency ?? '' }}
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                            Employee: <span class="text-slate-900 dark:text-white font-semibold">{{ $step->submission?->instance?->user?->name ?? '-' }}</span>
                                        </p>
                                        <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                                            <p>Submitted: {{ $step->submission?->submitted_at?->format('Y-m-d H:i') ?? '-' }}</p>
                                            <p>Due: {{ $step->submission?->instance?->due_at?->format('Y-m-d H:i') ?? 'No cutoff' }}</p>
                                            <p>On Time: <span class="{{ $step->submission?->is_late ? 'text-rose-600 font-semibold' : 'text-emerald-600 font-semibold' }}">{{ $step->submission?->is_late ? 'Late' : 'On time' }}</span></p>
                                            <p>Submitted By: {{ $step->submission?->submittedBy?->name ?? '-' }}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Info Badge -->
                                    <div class="text-right text-xs text-slate-400 dark:text-slate-500">
                                        Card {{ $index + 1 }} of {{ $filteredSteps->count() }}
                                    </div>
                                </div>

                                @if ($step->submission?->employee_remark)
                                    <div class="mt-4 rounded-xl bg-white p-3 shadow-sm dark:bg-slate-800">
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Employee Remark</p>
                                        <p class="mt-1 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">
                                            {{ $step->submission->employee_remark }}
                                        </p>
                                    </div>
                                @endif

                                @if ($step->submission?->instance?->template?->guideline)
                                    <div class="mt-4 rounded-xl bg-sky-50/50 p-4 border border-sky-100/50 dark:bg-sky-950/20 dark:border-sky-900/30">
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-600 dark:text-sky-400">Guideline</p>
                                        <p class="mt-1 text-sm text-slate-700 italic dark:text-slate-300">
                                            {{ $step->submission->instance->template->guideline }}
                                        </p>
                                    </div>
                                @endif

                                <!-- Photos -->
                                <div class="mt-4 space-y-2">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Submitted Photos</p>
                                    @if ($step->submission?->images?->isNotEmpty())
                                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach ($step->submission->images as $image)
                                                @php $fullImagePath = asset('storage/' . ltrim($image->image_path, '/')); @endphp
                                                <div @click="activeImage = '{{ $fullImagePath }}'; imageOpen = true"
                                                     class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm cursor-pointer dark:border-slate-700 dark:bg-slate-800">
                                                    <img src="{{ $fullImagePath }}" alt="{{ $image->title ?: 'Submission image' }}"
                                                         class="h-32 w-full object-cover transition duration-300 group-hover:scale-105">
                                                    <div class="p-2 bg-white/90 dark:bg-slate-800/90 text-xs">
                                                        <p class="font-medium text-slate-900 dark:text-slate-100 truncate">
                                                            {{ $image->title ?: 'No title' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-3 text-xs text-slate-400 dark:border-slate-700 dark:bg-slate-800">
                                            No images found on this submission.
                                        </div>
                                    @endif
                                </div>

                                <!-- Previous Approval Steps info -->
                                <div class="mt-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mb-2">Previous Steps Status</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($step->submission?->approvalSteps?->sortBy('step_order') ?? [] as $apStep)
                                            <span class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-medium border 
                                                {{ $apStep->status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50' : '' }}
                                                {{ $apStep->status === 'pending' ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50' : '' }}
                                                {{ $apStep->status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50' : '' }}
                                                {{ $apStep->status === 'cancelled' ? 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-800/80 dark:text-slate-400 dark:border-slate-700' : '' }}">
                                                Step {{ $apStep->step_order }}: {{ $apStep->approver?->name ?? 'Unassigned' }} 
                                                ({{ str_replace('_', ' ', $apStep->status) }})
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Slide Remark -->
                                <div class="mt-5">
                                    <label class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Approval / Rejection Remark</label>
                                    <textarea x-model="remark" rows="2"
                                              class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                              placeholder="Optional when approving, required when rejecting"></textarea>
                                </div>
                            </div>

                            <!-- Carousel Card Controls -->
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pt-2">
                                <!-- Navigation slide controls -->
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="carouselIndex = Math.max(0, carouselIndex - 1)" :disabled="carouselIndex === 0"
                                            class="inline-flex items-center justify-center h-10 w-10 rounded-xl border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    
                                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400">
                                        {{ $index + 1 }} / {{ $filteredSteps->count() }}
                                    </span>
                                    
                                    <button type="button" @click="carouselIndex = Math.min(count - 1, carouselIndex + 1)" :disabled="carouselIndex === count - 1"
                                            class="inline-flex items-center justify-center h-10 w-10 rounded-xl border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Action buttons -->
                                <div class="flex items-center gap-3">
                                    <!-- Reject -->
                                    <button type="button" 
                                            @click="if (remark.trim() === '') { alert('Remark is required to reject!'); return; } $wire.rejectStepDirectly({{ $step->id }}, remark); remark = '';"
                                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700">
                                        Reject
                                    </button>
                                    
                                    <!-- Approve -->
                                    <button type="button" 
                                            @click="$wire.approveStepDirectly({{ $step->id }}, remark); remark = '';"
                                            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700">
                                        Approve
                                    </button>

                                    <!-- Next Slide (Skip) -->
                                    @if ($index < $filteredSteps->count() - 1)
                                        <button type="button" @click="carouselIndex = carouselIndex + 1"
                                                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                            Next Slide
                                        </button>
                                    @endif
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50/50 p-8 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
                <h4 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">No pending approvals match filters</h4>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Adjust employee, month, or template filters above.</p>
            </div>
        @endif
    </section>

    @if ($selectedStep)
        <section class="rounded-3xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-900 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                            {{ $selectedStep->submission?->instance?->template?->title }}</h3>
                        <span
                            class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.15em] text-sky-700">
                            Step {{ $selectedStep->step_order }}
                        </span>
                        <span
                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.15em] text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ $selectedStep->submission?->instance?->template?->group?->name ?? 'No KPI Group' }}
                        </span>
                        <span
                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.15em] text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ $selectedStep->submission?->instance?->template?->frequency ?? '' }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Employee: {{ $selectedStep->submission?->instance?->user?->name ?? '-' }}
                    </p>
                    <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                        <p>Submitted: {{ $selectedStep->submission?->submitted_at?->format('Y-m-d H:i') ?? '-' }}</p>
                        <p>Due: {{ $selectedStep->submission?->instance?->due_at?->format('Y-m-d H:i') ?? 'No cutoff' }}
                        </p>
                        <p>On Time: {{ $selectedStep->submission?->is_late ? 'Late' : 'On time' }}</p>
                        <p>Submitted By: {{ $selectedStep->submission?->submittedBy?->name ?? '-' }}</p>
                    </div>
                </div>

                <button type="button" wire:click="cancelDecision"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-400 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-slate-100">
                    Close
                </button>
            </div>

            @if ($selectedStep->submission?->employee_remark)
                <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                        Employee Remark</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">
                        {{ $selectedStep->submission->employee_remark }}</p>
                </div>
            @endif

            <blockquote class="text-xl italic font-semibold text-heading tracking-tight">
                <svg class="w-9 h-9 text-heading mb-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 11V8a1 1 0 0 0-1-1H6a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1Zm0 0v2a4 4 0 0 1-4 4H5m14-6V8a1 1 0 0 0-1-1h-3a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1Zm0 0v2a4 4 0 0 1-4 4h-1" />
                </svg>
                <p>{{ $selectedStep->submission?->instance?->template?->guideline ?? 'No guideline available' }}</p>
            </blockquote>

            <div class="mt-5 space-y-3">
                <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Submitted Photos</p>
                @if ($selectedStep->submission?->images?->isNotEmpty())
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach ($selectedStep->submission->images as $image)
                            @php $fullImagePath = asset('storage/' . ltrim($image->image_path, '/')); @endphp
                                <article @click="activeImage = '{{ $fullImagePath }}'; imageOpen = true"
                                class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                <img src="{{ $fullImagePath }}" alt="{{ $image->title ?: 'Submission image' }}"
                                    class="h-56 w-full object-cover">
                                <div class="space-y-2 p-4">
                                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                                        {{ $image->title ?: 'No title' }}</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-300">
                                        {{ $image->remark ?: 'No remark' }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div
                        class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                        No images found on this submission.
                    </div>
                @endif
            </div>

            <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Approval
                    Steps</p>
                <div class="mt-3 space-y-2">
                    @foreach ($selectedStep->submission?->approvalSteps?->sortBy('step_order') ?? [] as $step)
                        <div
                            class="flex flex-col gap-1 rounded-2xl bg-white px-4 py-3 text-sm text-slate-600 dark:bg-slate-900 dark:text-slate-300 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-slate-100">Step {{ $step->step_order }}
                                    - {{ $step->role_label ?: 'Approver' }}</p>
                                <p>{{ $step->approver?->name ?? 'Unassigned' }}</p>
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">
                                <p>Status: {{ str_replace('_', ' ', $step->status) }}</p>
                                <p>{{ $step->acted_at?->format('Y-m-d H:i') ?? 'Pending' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-5">
                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Approval Remark</label>
                <textarea wire:model.defer="decisionRemark" rows="3"
                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    placeholder="Optional when approving, required when rejecting"></textarea>
                @error('decisionRemark')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button type="button" wire:click="rejectSelected"
                    class="inline-flex items-center justify-center rounded-2xl bg-rose-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-rose-700">
                    Reject
                </button>
                <button type="button" wire:click="approveSelected"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800">
                    Approve
                </button>
            </div>
        </section>
    @endif

    <section class="grid gap-6 xl:grid-cols-2">
        <article
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">First-Step Queue</h3>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Grouped by submitter, then requested date.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-500 dark:text-slate-400">{{ $pendingFirstSteps->count() }} item(s)</span>
                    <button type="button" @click="firstStepQueueOpen = true"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                        Open Queue
                    </button>
                </div>
            </div>
        </article>

        <article
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Final-Step Queue</h3>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ $pendingFinalSteps->count() }}
                    item(s)</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($pendingFinalSteps as $step)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-slate-100">
                                    {{ $step->submission?->instance?->template?->title }}</p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    {{ $step->submission?->instance?->user?->name ?? '-' }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    First approved
                                    {{ $step->submission?->first_approved_at?->format('Y-m-d H:i') ?? '-' }}
                                </p>
                            </div>

                            <button type="button" wire:click="openStep({{ $step->id }})"
                                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                                Review
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No pending final approvals.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Recent Decisions</h3>
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ $recentSteps->count() }} item(s)</span>
        </div>

        <div class="mt-4 space-y-3">
            @forelse ($recentSteps as $step)
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="font-medium text-slate-900 dark:text-slate-100">
                                {{ $step->submission?->instance?->template?->title }}</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {{ $step->submission?->instance?->user?->name ?? '-' }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ $step->acted_at?->format('Y-m-d H:i') ?? '-' }}</p>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-medium uppercase tracking-[0.15em] {{ $step->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                            {{ $step->status }}
                        </span>
                    </div>

                    @if ($step->remark)
                        <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">{{ $step->remark }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500 dark:text-slate-400">No recent approval actions yet.</p>
            @endforelse
        </div>
    </section>

    <div x-show="firstStepQueueOpen" x-transition.opacity @keydown.escape.window="firstStepQueueOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0" @click="firstStepQueueOpen = false"></div>

        <div class="relative max-h-[90vh] w-full max-w-6xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
            <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5 dark:border-slate-700">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">
                        First-Step Queue
                    </p>
                    <h3 class="mt-1 text-2xl font-semibold text-slate-900 dark:text-slate-100">
                        Review by submitter and requested date
                    </h3>
                </div>
                <button type="button" @click="firstStepQueueOpen = false"
                    class="text-3xl leading-none text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100">
                    &times;
                </button>
            </div>

            <div class="max-h-[calc(90vh-88px)] overflow-y-auto p-6">
                <div class="space-y-4">
                    @forelse ($pendingFirstStepGroups as $submitterGroup)
                        <details
                            class="group rounded-2xl border border-slate-200 bg-slate-50 p-4 open:bg-white dark:border-slate-700 dark:bg-slate-800/80 dark:open:bg-slate-900">
                            <summary
                                class="flex cursor-pointer list-none items-center justify-between gap-4">
                                <div>
                                    <p class="text-base font-semibold text-slate-900 dark:text-slate-100">
                                        Submitted by {{ $submitterGroup['submitter_name'] }}
                                    </p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">
                                        {{ $submitterGroup['items_count'] }} submission(s)
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-slate-200 px-3 py-1 text-xs font-medium uppercase tracking-[0.18em] text-slate-700 transition group-open:bg-slate-900 group-open:text-white dark:bg-slate-700 dark:text-slate-200">
                                    Toggle
                                </span>
                            </summary>

                            <div class="mt-4 space-y-3 pl-0 sm:pl-2">
                                @foreach ($submitterGroup['requested_dates'] as $requestedDateGroup)
                                    <details
                                        class="group rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                        <summary
                                            class="flex cursor-pointer list-none items-center justify-between gap-4">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                                    Requested date: {{ $requestedDateGroup['requested_date_label'] }}
                                                </p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                                    {{ count($requestedDateGroup['items']) }} item(s)
                                                </p>
                                            </div>
                                            <span
                                                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium uppercase tracking-[0.18em] text-slate-600 transition group-open:bg-sky-100 group-open:text-sky-700 dark:bg-slate-800 dark:text-slate-300 dark:group-open:bg-sky-950/50 dark:group-open:text-sky-300">
                                                Toggle
                                            </span>
                                        </summary>

                                        <div class="mt-4 grid gap-3">
                                            @foreach ($requestedDateGroup['items'] as $step)
                                                <div
                                                    class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                                                    wire:key="first-step-{{ $step->id }}">
                                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                        <div class="space-y-1">
                                                            <p class="font-medium text-slate-900 dark:text-slate-100">
                                                                {{ $step->submission?->instance?->template?->title }}
                                                            </p>
                                                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                                                Employee: {{ $step->submission?->instance?->user?->name ?? '-' }}
                                                            </p>
                                                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                                                Requested
                                                                {{ $step->submission?->submitted_at?->format('Y-m-d H:i') ?? $step->submission?->created_at?->format('Y-m-d H:i') ?? '-' }}
                                                            </p>
                                                        </div>

                                                        <button type="button"
                                                            @click="firstStepQueueOpen = false"
                                                            wire:click="openStep({{ $step->id }})"
                                                            class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                                                            Review
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endforeach
                            </div>
                        </details>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                            No pending first-step approvals.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- SINGLE MODAL (Shared by all images) -->
    <div x-show="imageOpen" x-transition.opacity @keydown.escape.window="imageOpen = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4" style="display: none;" x-cloak>

        <!-- Close Overlay Click -->
        <div class="absolute inset-0" @click="imageOpen = false"></div>

        <div class="relative max-w-5xl max-h-screen">
            <button @click="imageOpen = false" class="absolute -top-10 right-0 text-3xl text-white">&times;</button>

            <!-- This image tag updates automatically because it is bound to 'activeImage' -->
            <img :src="activeImage" class="max-w-full max-h-[90vh] rounded shadow-2xl">
        </div>
    </div>
</div>
