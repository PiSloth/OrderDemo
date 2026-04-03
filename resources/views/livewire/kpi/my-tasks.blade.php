<div class="space-y-6">
    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('message') }}
        </div>
    @endif

    <section class="rounded-3xl bg-slate-900 px-6 py-7 text-white">
        <p class="text-sm uppercase tracking-[0.25em] text-slate-300">My Tasks</p>
        <h2 class="mt-2 text-3xl font-semibold">Submit daily KPI tasks from your phone.</h2>
        <p class="mt-3 max-w-3xl text-sm text-slate-200">
            Use your camera or local gallery, add the required photo titles and remarks, then send the task into approval.
        </p>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['value'] }}</p>
            </article>
        @endforeach
    </section>

    @if ($selectedTaskInstance)
        <section class="rounded-3xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-900 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $selectedTaskInstance->template?->title }}</h3>
                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.15em] text-sky-700">
                            {{ $selectedTaskInstance->template?->group?->name ?? 'No KPI Group' }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $selectedTaskInstance->template?->description ?: 'No description' }}</p>
                    <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                        <p>Cutoff: {{ $selectedTaskInstance->due_at ? $selectedTaskInstance->due_at->format('Y-m-d H:i') : 'No cutoff' }}</p>
                        <p>Submit Window Ends: {{ $this->submissionWindowLabel($selectedTaskInstance) }}</p>
                        <p>First Approver: {{ $selectedTaskInstance->assignment?->firstApprover?->name ?? '-' }}</p>
                        <p>Second Approver: {{ $selectedTaskInstance->assignment?->finalApprover?->name ?? 'Not required' }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    wire:click="cancelSubmission"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-400 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-slate-100"
                >
                    Cancel
                </button>
            </div>

            @if ($selectedTaskInstance->template?->guideline)
                <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Guideline</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ $selectedTaskInstance->template->guideline }}</p>
                </div>
            @endif

            <form wire:submit="submitTask" class="mt-5 space-y-5">
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Photos</label>
                    <div class="mt-2 rounded-3xl border border-slate-300 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800 sm:p-5">
                        <input
                            id="kpi-camera-photo"
                            type="file"
                            wire:model="cameraPhoto"
                            accept="image/*"
                            capture="environment"
                            class="hidden"
                        >
                        <input
                            id="kpi-gallery-photos"
                            type="file"
                            wire:model="galleryPhotos"
                            accept="image/*"
                            multiple
                            class="hidden"
                        >

                        <div class="flex flex-wrap items-center gap-3">
                            <label
                                for="kpi-camera-photo"
                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-800"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 8.5A2.5 2.5 0 0 1 6.5 6H8l1.2-1.6A2 2 0 0 1 10.8 3.6h2.4a2 2 0 0 1 1.6.8L16 6h1.5A2.5 2.5 0 0 1 20 8.5v8A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-8Z"/>
                                    <circle cx="12" cy="12.5" r="3.5"/>
                                </svg>
                                <span>Use Camera</span>
                            </label>

                            <label
                                for="kpi-gallery-photos"
                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-medium text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m7 9 5-5 5 5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 20h14"/>
                                </svg>
                                <span>Upload</span>
                            </label>
                        </div>

                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            Choose camera or upload. Each selected photo will appear below in this same section with preview, title, and remark.
                        </p>

                        @if (count($submissionPhotos) > 0)
                            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                @foreach ($submissionPhotos as $index => $photo)
                                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                                        @if (method_exists($photo, 'temporaryUrl'))
                                            <img
                                                src="{{ $photo->temporaryUrl() }}"
                                                alt="Submission preview {{ $index + 1 }}"
                                                class="h-48 w-full object-cover"
                                            >
                                        @endif

                                        <div class="space-y-3 p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Photo {{ $index + 1 }}</p>
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] uppercase tracking-[0.15em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                        {{ ($submissionPhotoSources[$index] ?? 'gallery') === 'camera' ? 'Camera' : 'Upload' }}
                                                    </span>
                                                </div>

                                                <button
                                                    type="button"
                                                    wire:click="removeSubmissionPhoto({{ $index }})"
                                                    class="text-xs font-medium text-rose-600 transition hover:text-rose-700"
                                                >
                                                    Remove
                                                </button>
                                            </div>

                                            <div>
                                                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Action Title</label>
                                                <input
                                                    type="text"
                                                    wire:model.defer="submissionPhotoTitles.{{ $index }}"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                                    placeholder="Example: CCTV front gate checked"
                                                >
                                                @error('submissionPhotoTitles.' . $index)
                                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                                    Photo Remark
                                                    @if ($selectedTaskInstance->template?->image_remark_required)
                                                        <span class="text-rose-600">*</span>
                                                    @endif
                                                </label>
                                                <textarea
                                                    wire:model.defer="submissionPhotoRemarks.{{ $index }}"
                                                    rows="2"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                                    placeholder="Add detail for this photo"
                                                ></textarea>
                                                @error('submissionPhotoRemarks.' . $index)
                                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Camera opens directly on supported phones. Every uploaded image is resized to 300px before storage.
                    </p>
                    @error('cameraPhoto')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('galleryPhotos')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('galleryPhotos.*')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('submissionPhotos')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                    @error('submissionPhotos.*')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">General Remark</label>
                    <textarea
                        wire:model.defer="submissionEmployeeRemark"
                        rows="3"
                        class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        placeholder="Optional note for approvers"
                    ></textarea>
                    @error('submissionEmployeeRemark')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        <p>Minimum photos: {{ $selectedTaskInstance->template?->min_images ?? 0 }}</p>
                        <p>Maximum photos: {{ $selectedTaskInstance->template?->max_images ?? 'No limit' }}</p>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        wire:loading.attr="disabled"
                        wire:target="submissionPhotos,submitTask"
                    >
                        <span wire:loading.remove wire:target="submitTask">Submit For Approval</span>
                        <span wire:loading wire:target="submitTask">Submitting...</span>
                    </button>
                </div>
            </form>
        </section>
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Today Must-Do Tasks</h3>
            <span class="text-sm text-slate-500 dark:text-slate-400">Mobile-first daily list</span>
        </div>

        @forelse ($todayTasks as $task)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $task->template?->title }}</p>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs uppercase tracking-[0.15em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ $task->template?->group?->name ?? 'No KPI Group' }}
                            </span>
                            @if ($selectedTaskInstance?->id === $task->id)
                                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs uppercase tracking-[0.15em] text-sky-700">
                                    Selected
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $task->template?->description ?: 'No description' }}</p>
                        <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                            <p>Cutoff: {{ $task->due_at ? $task->due_at->format('H:i') : 'No cutoff' }}</p>
                            <p>Status: {{ str_replace('_', ' ', $task->status) }}</p>
                            <p>Submissions: {{ $task->submissions_count }}</p>
                            <p>First Approver: {{ $task->assignment?->firstApprover?->name ?? '-' }}</p>
                        </div>

                        @if ($task->submissions->isNotEmpty() && $task->status === 'rejected')
                            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                Last rejection: {{ $task->submissions->first()?->rejection_reason ?: 'No reason provided.' }}
                            </div>
                        @endif

                        @if ($task->template?->requires_table)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                                This task needs custom table evidence. Table submission is not built yet.
                            </div>
                        @endif

                        @if (!$task->template?->requires_images)
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                                This task is not configured for photo evidence.
                            </div>
                        @endif

                        @if (!$this->canSubmit($task) && !$this->isFinalized($task) && !$task->template?->requires_table && $task->template?->requires_images)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                Submission window ends at {{ $this->submissionWindowLabel($task) }}.
                            </div>
                        @endif
                    </div>

                    <div class="flex w-full flex-col gap-3 lg:w-56">
                        @if ($this->canSubmit($task))
                            <button
                                type="button"
                                wire:click="openSubmission({{ $task->id }})"
                                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-800"
                            >
                                {{ $selectedTaskInstance?->id === $task->id ? 'Editing Submission' : 'Submit Task' }}
                            </button>
                        @else
                            <div class="rounded-2xl bg-slate-100 px-4 py-3 text-center text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ str_replace('_', ' ', $task->status) }}
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                No daily task instance for today.
            </div>
        @endforelse
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Open Weekly Tasks</h3>
            <div class="mt-4 space-y-3">
                @forelse ($weeklyTasks as $task)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-slate-100">{{ $task->template?->title }}</p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    Due {{ $task->due_at ? $task->due_at->format('Y-m-d H:i') : 'N/A' }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Status: {{ str_replace('_', ' ', $task->status) }}</p>
                            </div>

                            @if ($this->canSubmit($task))
                                <button
                                    type="button"
                                    wire:click="openSubmission({{ $task->id }})"
                                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                >
                                    {{ $selectedTaskInstance?->id === $task->id ? 'Editing Submission' : 'Submit Task' }}
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No open weekly tasks.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Open Monthly Tasks</h3>
            <div class="mt-4 space-y-3">
                @forelse ($monthlyTasks as $task)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-slate-100">
                                    {{ $task->template?->title }} @if ($task->period_index > 1) Slot {{ $task->period_index }} @endif
                                </p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                    Due {{ $task->due_at ? $task->due_at->format('Y-m-d H:i') : 'N/A' }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Status: {{ str_replace('_', ' ', $task->status) }}</p>
                            </div>

                            @if ($this->canSubmit($task))
                                <button
                                    type="button"
                                    wire:click="openSubmission({{ $task->id }})"
                                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                                >
                                    {{ $selectedTaskInstance?->id === $task->id ? 'Editing Submission' : 'Submit Task' }}
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No open monthly tasks.</p>
                @endforelse
            </div>
        </article>
    </section>

    @if ($overdueTasks->isNotEmpty())
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
            <h3 class="text-lg font-semibold text-rose-800">Overdue</h3>
            <div class="mt-3 space-y-2">
                @foreach ($overdueTasks as $task)
                    <p class="text-sm text-rose-700">
                        {{ $task->template?->title }} - due {{ $task->due_at?->format('Y-m-d H:i') }}
                    </p>
                @endforeach
            </div>
        </section>
    @endif
</div>
