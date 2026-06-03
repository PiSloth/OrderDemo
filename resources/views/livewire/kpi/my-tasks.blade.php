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
            Use your camera or local gallery, add the required photo titles and remarks, then send the task into
            approval.
        </p>
        <div class="mt-5 max-w-xs">
            <label for="selected-kpi-month"
                class="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-slate-300">
                Task Month
            </label>
            <select id="selected-kpi-month" wire:model.live="selectedMonth"
                class="block w-full rounded-2xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-sm text-white">
                @foreach ($this->monthOptions() as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-slate-300">
                Only current month and previous month are available.
            </p>
        </div>

        @if ($isSuperAdmin)
            <div class="mt-5 max-w-xl">
                <label for="selected-kpi-user"
                    class="mb-2 block text-xs font-semibold uppercase tracking-[0.15em] text-slate-300">
                    Employee
                </label>
                <select id="selected-kpi-user" wire:model.live="selectedUserId"
                    class="block w-full rounded-2xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-sm text-white">
                    @foreach ($employeeOptions as $employee)
                        <option value="{{ $employee['value'] }}">{{ $employee['label'] }}</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-slate-300">
                    Super Admin can switch employee task list. Submission is read-only when viewing another employee.
                </p>
            </div>
        @endif
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {{-- @dd($summaryCards) --}}
        @foreach ($summaryCards as $card)
            <article @click="$openModal('{{ $card['modalTarget'] }}')"
                style="cursor: {{ $card['modalTarget'] ? 'pointer' : 'default' }}"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $card['value'] }}</p>
                <span>{{ $card['modalTarget'] }}</span>
            </article>
        @endforeach
    </section>

    @if ($selectedTaskInstance)
        <section class="rounded-3xl border border-sky-200 bg-white p-5 shadow-sm dark:border-sky-900 dark:bg-slate-900">
            @if (!$this->canModifyViewedTasks())
                <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    @if ($isSuperAdmin && $isResubmissionMode)
                        Super Admin resubmission mode is active for this overdue task.
                    @else
                        You are viewing another employee's task list in read-only mode.
                    @endif
                </div>
            @endif

            <div id="top" class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                            {{ $selectedTaskInstance->template?->title }}</h3>
                        <span
                            class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium uppercase tracking-[0.15em] text-sky-700">
                            {{ $selectedTaskInstance->template?->group?->name ?? 'No KPI Group' }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        {{ $selectedTaskInstance->template?->description ?: 'No description' }}</p>
                    <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                        <p>Cutoff:
                            {{ $selectedTaskInstance->due_at ? $selectedTaskInstance->due_at->format('Y-m-d H:i') : 'No cutoff' }}
                        </p>
                        <p>Submit Window Ends: {{ $this->submissionWindowLabel($selectedTaskInstance) }}</p>
                        <p>First Approver: {{ $selectedTaskInstance->assignment?->firstApprover?->name ?? '-' }}</p>
                        <p>Second Approver:
                            {{ $selectedTaskInstance->assignment?->finalApprover?->name ?? 'Not required' }}</p>
                    </div>
                </div>

                <button type="button" wire:click="cancelSubmission"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:border-slate-400 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-slate-100">
                    Cancel
                </button>
            </div>

            @if ($selectedTaskInstance->template?->guideline)
                <div class="mt-5 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                        Guideline</p>
                    <p class="mt-2 whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">
                        {{ $selectedTaskInstance->template->guideline }}</p>
                </div>
            @endif

            <form wire:submit="{{ $isResubmissionMode ? 'resubmitTask' : 'submitTask' }}" class="mt-5 space-y-5">
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Photos</label>
                    <div x-data="{
                        uploading: false,
                        progress: 0,
                        uploadErrorMessage: '',
                        previews: [],
                        addFiles(files) {
                            Array.from(files || []).forEach((file) => {
                                if (!file || !file.type || !file.type.startsWith('image/')) return;
                                this.previews.push({
                                    name: file.name,
                                    url: URL.createObjectURL(file),
                                });
                            });
                        },
                        removePreviewAt(index) {
                            const removed = this.previews.splice(index, 1);
                            if (removed.length && removed[0].url) {
                                URL.revokeObjectURL(removed[0].url);
                            }
                        },
                        clearPreviews() {
                            this.previews.forEach((p) => {
                                if (p.url) URL.revokeObjectURL(p.url);
                            });
                            this.previews = [];
                        },
                    }"
                        x-on:livewire-upload-start="uploading = true; uploadErrorMessage = ''"
                        x-on:livewire-upload-finish="uploading = false; progress = 0"
                        x-on:livewire-upload-error="
                            uploading = false;
                            progress = 0;
                            const detail = $event.detail || {};
                            const message = detail.message || '';
                            const status = detail.status || '';

                            if (status === 401 || message.includes('401')) {
                                uploadErrorMessage = 'Upload failed: 401 Unauthorized. Signed upload URL is invalid or expired. Please refresh and try again.';
                            } else if (message) {
                                uploadErrorMessage = `Upload failed: ${message}`;
                            } else {
                                uploadErrorMessage = 'Upload failed. Possible reasons: session expired, file too large, unsupported file type, or server rejected temporary upload request.';
                            }
                        "
                        x-on:livewire-upload-cancel="uploading = false; progress = 0"
                        x-on:livewire-upload-progress="progress = $event.detail.progress"
                        class="mt-2 rounded-3xl border border-slate-300 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800 sm:p-5">
                        <input id="kpi-camera-photo" type="file" wire:model="cameraPhoto" accept="image/*"
                            x-on:change="addFiles($event.target.files)"
                            capture="environment" class="hidden">
                        <input id="kpi-gallery-photos" type="file" wire:model="galleryPhotos" accept="image/*"
                            x-on:change="addFiles($event.target.files)"
                            multiple class="hidden">

                        <div class="flex flex-wrap items-center gap-3">
                            <label for="kpi-camera-photo"
                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-800"
                                :class="{ 'pointer-events-none opacity-60': uploading }">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 8.5A2.5 2.5 0 0 1 6.5 6H8l1.2-1.6A2 2 0 0 1 10.8 3.6h2.4a2 2 0 0 1 1.6.8L16 6h1.5A2.5 2.5 0 0 1 20 8.5v8A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-8Z" />
                                    <circle cx="12" cy="12.5" r="3.5" />
                                </svg>
                                <span>Use Camera</span>
                            </label>

                            <label for="kpi-gallery-photos"
                                class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-sm font-medium text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700"
                                :class="{ 'pointer-events-none opacity-60': uploading }">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m7 9 5-5 5 5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 20h14" />
                                </svg>
                                <span>Upload</span>
                            </label>
                        </div>

                        <div x-cloak x-show="uploading"
                            class="mt-4 rounded-2xl border border-sky-200 bg-sky-50 p-3 dark:border-sky-900 dark:bg-sky-950/20">
                            <div class="flex items-center justify-between gap-3">
                                <p
                                    class="text-xs font-semibold uppercase tracking-[0.15em] text-sky-700 dark:text-sky-300">
                                    Uploading Photos
                                </p>
                                <p class="text-xs font-semibold text-sky-700 dark:text-sky-300" x-text="`${progress}%`">
                                </p>
                            </div>
                            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-sky-100 dark:bg-sky-900/40">
                                <div class="h-full rounded-full bg-sky-600 transition-all duration-200"
                                    :style="`width: ${progress}%`"></div>
                            </div>
                        </div>

                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            Choose camera or upload. Each selected photo will appear below in this same section with
                            preview, title, and remark.
                        </p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            Supported formats: JPG, PNG, WEBP.
                        </p>
                        <p x-cloak x-show="uploadErrorMessage" x-text="uploadErrorMessage"
                            class="mt-3 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                        </p>

                        @if (count($submissionPhotos) > 0)
                            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                @foreach ($submissionPhotos as $index => $photo)
                                    <article x-data="{ imageLoaded: false }"
                                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
                                        <div class="relative h-48 w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                                            <div x-cloak x-show="!imageLoaded"
                                                class="absolute inset-0 flex flex-col items-center justify-center gap-2 px-4">
                                                <p
                                                    class="text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-600 dark:text-slate-300">
                                                    Loading Preview
                                                </p>
                                                <div
                                                    class="h-2 w-full max-w-[220px] overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                                    <div
                                                        class="h-full w-1/2 animate-pulse rounded-full bg-slate-500 dark:bg-slate-300">
                                                    </div>
                                                </div>
                                            </div>

                                            <img x-ref="previewImage"
                                                x-init="$nextTick(() => { if ($refs.previewImage?.complete) imageLoaded = true; })"
                                                x-on:load="imageLoaded = true" x-show="imageLoaded"
                                                x-transition.opacity :src="previews[{{ $index }}]?.url || ''"
                                                alt="Submission preview {{ $index + 1 }}"
                                                class="h-48 w-full object-cover">
                                        </div>

                                        <div class="space-y-3 p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                                        Photo {{ $index + 1 }}</p>
                                                    <span
                                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] uppercase tracking-[0.15em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                        {{ ($submissionPhotoSources[$index] ?? 'gallery') === 'camera' ? 'Camera' : 'Upload' }}
                                                    </span>
                                                </div>

                                                <button type="button"
                                                    wire:click="removeSubmissionPhoto({{ $index }})"
                                                    x-on:click="removePreviewAt({{ $index }})"
                                                    class="text-xs font-medium text-rose-600 transition hover:text-rose-700">
                                                    Remove
                                                </button>
                                            </div>

                                            <div>
                                                <label
                                                    class="text-sm font-medium text-slate-700 dark:text-slate-200">Action
                                                    Title</label>
                                                <input type="text"
                                                    wire:model.defer="submissionPhotoTitles.{{ $index }}"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                                    placeholder="Example: CCTV front gate checked">
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
                                                <textarea wire:model.defer="submissionPhotoRemarks.{{ $index }}" rows="2"
                                                    class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                                                    placeholder="Add detail for this photo"></textarea>
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
                        Camera opens directly on supported phones. Every uploaded image is resized to 300px before
                        storage.
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
                    <textarea wire:model.defer="submissionEmployeeRemark" rows="3"
                        class="mt-2 block w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        placeholder="Optional note for approvers"></textarea>
                    @error('submissionEmployeeRemark')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs text-slate-500 dark:text-slate-400">
                        @if ($selectedTaskInstance->template?->requires_images)
                            @if ($selectedTaskInstance->required_image_count !== null)
                                <p>Required photos: {{ $selectedTaskInstance->required_image_count }}</p>
                            @else
                                <p>Minimum photos: {{ $selectedTaskInstance->template?->min_images ?? 0 }}</p>
                                <p>Maximum photos: {{ $selectedTaskInstance->template?->max_images ?? 'No limit' }}</p>
                            @endif
                        @else
                            <p>No evidence required for this task.</p>
                        @endif
                        @if ($isSuperAdmin && $isResubmissionMode)
                            <p class="mt-1 text-rose-600">Submitting as Super Admin after the due date.</p>
                        @endif
                    </div>

                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        wire:loading.attr="disabled" wire:target="cameraPhoto,galleryPhotos,submitTask,resubmitTask">
                        <span wire:loading.remove wire:target="submitTask,resubmitTask">
                            {{ $isResubmissionMode ? 'Resubmit For Approval' : 'Submit For Approval' }}
                        </span>
                        <span wire:loading wire:target="submitTask,resubmitTask">
                            {{ $isResubmissionMode ? 'Resubmitting...' : 'Submitting...' }}
                        </span>
                    </button>
                </div>
            </form>
        </section>
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Daily Tasks</h3>
            <span class="text-sm text-slate-500 dark:text-slate-400">Mobile-first daily list</span>
        </div>

        <x-modal wire:model="dailyTaskModal">
            <x-card title="Daily Task">
                <div>
                    @forelse ($todayTasks as $task)
                        <article
                            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $task->template?->title }}</p>
                                        <span
                                            class="rounded-full bg-slate-100 px-2 py-0.5 text-xs uppercase tracking-[0.15em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $task->template?->group?->name ?? 'No KPI Group' }}
                                        </span>
                                        @if ($selectedTaskInstance?->id === $task->id)
                                            <span
                                                class="rounded-full bg-sky-100 px-2 py-0.5 text-xs uppercase tracking-[0.15em] text-sky-700">
                                                Selected
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-slate-600 dark:text-slate-300">
                                        {{ $task->template?->description ?: 'No description' }}</p>
                                    <div class="grid gap-2 text-sm text-slate-500 dark:text-slate-400 md:grid-cols-2">
                                        <p>Cutoff: {{ $task->due_at ? $task->due_at->format('H:i') : 'No cutoff' }}</p>
                                        <p>Status: {{ str_replace('_', ' ', $task->status) }}</p>
                                        <p>Submissions: {{ $task->submissions_count }}</p>
                                        <p>First Approver: {{ $task->assignment?->firstApprover?->name ?? '-' }}</p>
                                    </div>

                                    @if ($task->submissions->isNotEmpty() && $task->status === 'rejected')
                                        <div
                                            class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                            Last rejection:
                                            {{ $task->submissions->first()?->rejection_reason ?: 'No reason provided.' }}
                                        </div>
                                    @endif

                                    @if ($task->template?->requires_table)
                                        <div
                                            class="rounded-2xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                                            This task needs custom table evidence. Table submission is not built yet.
                                        </div>
                                    @endif

                                    @if (!$task->template?->requires_images && !$task->template?->requires_table)
                                        <div
                                            class="rounded-2xl border border-sky-200 bg-sky-50 px-3 py-2 text-sm text-sky-700">
                                            No evidence is required. You can submit directly.
                                        </div>
                                    @endif

                                    @if (
                                        !$this->canSubmit($task) &&
                                            !$this->isFinalized($task) &&
                                            !$task->template?->requires_table &&
                                            $task->template?->requires_images)
                                        <div
                                            class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            Submission window ends at {{ $this->submissionWindowLabel($task) }}.
                                        </div>
                                    @endif
                                </div>

                                <div class="flex w-full flex-col gap-3 lg:w-56">
                                    @if ($this->canDirectSubmitNoEvidence($task))
                                        <button type="button" wire:click="submitNoEvidence({{ $task->id }})"
                                            x-on:click="close"
                                            class="inline-flex items-center justify-center rounded-2xl bg-emerald-700 px-4 py-3 text-sm font-medium text-white transition hover:bg-emerald-800">
                                            Submit (No Evidence)
                                        </button>
                                    @elseif ($this->canSubmit($task))
                                        <button type="button" wire:click="openSubmission({{ $task->id }})"
                                            x-on:click="close"
                                            class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-800">
                                            {{ $selectedTaskInstance?->id === $task->id ? 'Editing Submission' : 'Submit Task' }}
                                            <a href="#top"></a>
                                        </button>
                                    @else
                                        <div
                                            class="rounded-2xl bg-slate-100 px-4 py-3 text-center text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ str_replace('_', ' ', $task->status) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div
                            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                            No daily task instance for selected month.
                        </div>
                    @endforelse
                </div>

                {{-- <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='updateSale' green />
                </div>
            </x-slot> --}}
            </x-card>
        </x-modal>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <x-modal wire:model="weeklyTaskModal">
            <x-card title="Weekly Task">
                <div>
                    <article
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Open Weekly Tasks</h3>
                        <div class="mt-4 space-y-3">
                            @forelse ($weeklyTasks as $task)
                                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="font-medium text-slate-900 dark:text-slate-100">
                                                {{ $task->template?->title }}</p>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                                Due {{ $task->due_at ? $task->due_at->format('Y-m-d H:i') : 'N/A' }}
                                            </p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Status:
                                                {{ str_replace('_', ' ', $task->status) }}</p>
                                        </div>

                                        @if ($this->canSubmit($task))
                                            <button type="button" wire:click="openSubmission({{ $task->id }})"
                                                x:on:click="close"
                                                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
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
                </div>

                {{-- <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='updateSale' green />
                </div>
            </x-slot> --}}
            </x-card>
        </x-modal>


        <x-modal wire:model="monthlyTaskModal">
            <x-card title="Monthly Task">
                <div>
                    <article
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Open Monthly Tasks</h3>
                        <div class="mt-4 space-y-3">
                            @forelse ($monthlyTasks as $task)
                                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="font-medium text-slate-900 dark:text-slate-100">
                                                {{ $task->template?->title }} @if ($task->period_index > 1)
                                                    Slot {{ $task->period_index }}
                                                @endif
                                            </p>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                                Due {{ $task->due_at ? $task->due_at->format('Y-m-d H:i') : 'N/A' }}
                                            </p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Status:
                                                {{ str_replace('_', ' ', $task->status) }}</p>
                                        </div>

                                        @if ($this->canSubmit($task))
                                            <button type="button" wire:click="openSubmission({{ $task->id }})"
                                                x-on:click="close"
                                                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
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
                </div>

                {{-- <x-slot name="footer">
                <div class="flex justify-end gap-x-4">
                    <x-button flat label="Cancel" x-on:click="close" />
                    <x-button primary label="save" right-icon="save" wire:click='updateSale' green />
                </div>
            </x-slot> --}}
            </x-card>
        </x-modal>
    </section>

    @if ($overdueTasks->isNotEmpty())
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
            <h3 class="text-lg font-semibold text-rose-800">Overdue</h3>
            <div class="mt-3 space-y-3">
                @foreach ($overdueTasks as $task)
                    <article class="rounded-2xl border border-rose-200 bg-white px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-rose-800">
                                    {{ $task->template?->title }}
                                </p>
                                <p class="text-xs text-rose-600">
                                    Employee: {{ $task->user?->name ?? 'Unknown' }}
                                </p>
                                <p class="text-xs text-rose-600">
                                    Due {{ $task->due_at?->format('Y-m-d H:i') }}
                                </p>
                            </div>

                            @if ($isSuperAdmin)
                                <div class="flex flex-col gap-2 sm:items-end">
                                    @if ($this->canSuperAdminResubmitWithoutEvidence($task))
                                        <button type="button" wire:click="resubmitNoEvidence({{ $task->id }})"
                                            class="inline-flex items-center justify-center rounded-2xl bg-rose-700 px-4 py-2 text-xs font-medium text-white transition hover:bg-rose-800">
                                            Resubmit No Evidence
                                        </button>
                                    @elseif ($this->canSuperAdminResubmitWithEvidence($task))
                                        <button type="button" wire:click="openResubmitSubmission({{ $task->id }})"
                                            class="inline-flex items-center justify-center rounded-2xl bg-rose-700 px-4 py-2 text-xs font-medium text-white transition hover:bg-rose-800">
                                            Open Resubmit Form
                                        </button>
                                    @else
                                        <span class="rounded-2xl bg-rose-100 px-3 py-2 text-xs text-rose-700">
                                            Resubmit unavailable
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($isSuperAdmin && !$this->canResubmitOverdueTask($task))
                            <p class="mt-2 text-xs text-rose-600">
                                {{ $this->overdueResubmitUnavailableReason($task) }}
                            </p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
