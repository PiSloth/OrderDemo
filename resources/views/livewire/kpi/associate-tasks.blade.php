<div class="space-y-6">
    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('message') }}
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Associate Tasks (Phase 2)</h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            Submit once, associate accept, then approver approve/reject. Rejected runs can be resubmitted.
        </p>
    </section>

    @can('kpiManageAssignments')
        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Create Associate Group</h3>
                <div class="mt-3 space-y-3">
                    <input type="text" wire:model.defer="groupName"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                        placeholder="Group name">
                    <select wire:model.defer="groupTemplateId"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">Select template</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->title }}</option>
                        @endforeach
                    </select>
                    <div class="grid gap-3 md:grid-cols-2">
                        <select wire:model.defer="groupFrequency"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                            <option value="daily">daily</option>
                            <option value="weekly">weekly</option>
                            <option value="monthly">monthly</option>
                        </select>
                        <input type="time" wire:model.defer="groupCutoffTime"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <x-select label="" placeholder="First approver" wire:model.defer="groupFirstApproverUserId"
                            :async-data="route('users.index')" option-label="name" option-value="id" clearable />
                        <x-select label="" placeholder="Final approver" wire:model.defer="groupFinalApproverUserId"
                            :async-data="route('users.index')" option-label="name" option-value="id" clearable />
                    </div>
                    <button type="button" wire:click="createGroup"
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                        Create Group
                    </button>
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Manage Group Members</h3>
                <div class="mt-3 space-y-3">
                    <select wire:model.live="manageGroupId"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">Select group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>

                    @if ($managedGroup)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $managedGroup->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Template: {{ $managedGroup->template?->title ?? '-' }}</p>
                        </div>
                    @endif

                    <select wire:model.defer="groupMemberAssignmentId"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                        <option value="">Select assignment to add</option>
                        @foreach ($assignments as $assignment)
                            <option value="{{ $assignment->id }}">
                                {{ $assignment->user?->name ?? '-' }} - {{ $assignment->template?->title ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                        <input type="checkbox" wire:model.defer="groupMemberRequired"
                            class="rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                        Required member
                    </label>
                    <button type="button" wire:click="addGroupMember"
                        class="rounded-xl border border-sky-300 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-50">
                        Add Member
                    </button>

                    @if ($managedGroup && $managedGroup->members->count() > 0)
                        <div class="space-y-2">
                            @foreach ($managedGroup->members as $member)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-slate-100">
                                            {{ $member->assignment?->user?->name ?? '-' }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ $member->assignment?->template?->title ?? '-' }} |
                                            {{ $member->is_required ? 'Required' : 'Optional' }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removeGroupMember({{ $managedGroup->id }}, {{ $member->task_assignment_id }})"
                                        class="rounded-lg border border-rose-300 px-2 py-1 text-xs text-rose-700 hover:bg-rose-50">
                                        Remove
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        </section>
    @endcan

    <section class="grid gap-6 lg:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Create Run</h3>
            <div class="mt-3">
                <select wire:model.defer="selectedGroupId"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                    <option value="">Select Associate Group</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}">
                            {{ $group->name }} ({{ strtoupper($group->frequency) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="button" wire:click="createRun"
                class="mt-3 rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Create Today Run
            </button>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">My Pending Accepts</h3>
            <div class="mt-3 space-y-2">
                @forelse ($myPendingAccepts as $member)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                            {{ $member->run?->group?->name ?? '-' }}
                        </p>
                        <div class="mt-2 flex gap-2">
                            <button type="button" wire:click="acceptAsAssociate({{ $member->id }})"
                                class="rounded-lg border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50">
                                Accept
                            </button>
                            <button type="button" wire:click="rejectAsAssociate({{ $member->id }})"
                                class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50">
                                Reject
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">No pending associate acceptance.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Approver Queue (Associate Runs)</h3>
        <div class="mt-3 grid gap-3">
            @forelse ($myAssociatePendingApprovals as $step)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">
                        {{ $step->run?->group?->name ?? '-' }} | Step {{ $step->step_order }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Status: {{ $step->run?->status ?? '-' }} | Photos: {{ $step->run?->submission?->images?->count() ?? 0 }}
                    </p>
                    <div class="mt-2 flex gap-2">
                        <button type="button" wire:click="openAssociateApprovalStep({{ $step->id }})"
                            class="rounded-lg border border-sky-300 px-3 py-1.5 text-xs font-medium text-sky-700 hover:bg-sky-50">
                            Select
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 dark:text-slate-400">No pending associate approvals.</p>
            @endforelse
        </div>

        @if ($selectedAssociateApprovalStepId)
            <div class="mt-4 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Approver Remark</label>
                <textarea wire:model.defer="associateApprovalRemark" rows="3"
                    class="mt-2 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    placeholder="Required on reject"></textarea>
                @error('associateApprovalRemark')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
                <div class="mt-3 flex gap-2">
                    <button type="button" wire:click="rejectAssociateStep"
                        class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-medium text-white hover:bg-rose-700">
                        Reject and Reopen
                    </button>
                    <button type="button" wire:click="approveAssociateStep"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-medium text-white hover:bg-slate-800">
                        Approve
                    </button>
                </div>
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">My Created Runs</h3>
        <div class="mt-3 space-y-3">
            @forelse ($myRuns as $run)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $run->group?->name ?? '-' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ strtoupper($run->period_type) }} | {{ $run->run_date?->format('Y-m-d') }} | status: {{ $run->status }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                Accepted required: {{ $run->confirmed_member_count }}/{{ $run->required_member_count }}
                            </p>
                        </div>
                        <button type="button" wire:click="selectRun({{ $run->id }})"
                            class="rounded-lg border border-sky-300 px-3 py-1.5 text-xs font-medium text-sky-700 hover:bg-sky-50">
                            {{ $selectedRunId === $run->id ? 'Selected' : ($run->status === 'reopened_by_associate' || $run->status === 'reopened_by_approver' ? 'Resubmit' : 'Submit Photos') }}
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 dark:text-slate-400">No runs created by you yet.</p>
            @endforelse
        </div>
    </section>

    @if ($selectedRunId)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Upload Once and Submit / Resubmit</h3>
            <form wire:submit.prevent="submitRun" class="mt-3 space-y-3">
                <input type="file" wire:model="submissionPhotos" multiple accept="image/*"
                    class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                @error('submissionPhotos')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror

                @if (count($submissionPhotos) > 0)
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($submissionPhotos as $index => $photo)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                                @if (method_exists($photo, 'temporaryUrl'))
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Submission {{ $index + 1 }}"
                                        class="h-32 w-full rounded-lg object-cover">
                                @endif
                                <input type="text" wire:model.defer="submissionPhotoTitles.{{ $index }}"
                                    class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    placeholder="Photo title">
                                <textarea wire:model.defer="submissionPhotoRemarks.{{ $index }}" rows="2"
                                    class="mt-2 block w-full rounded-lg border border-slate-300 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    placeholder="Photo remark"></textarea>
                            </div>
                        @endforeach
                    </div>
                @endif

                <textarea wire:model.defer="submissionRemark" rows="3"
                    class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                    placeholder="General remark (optional)"></textarea>

                <button type="submit"
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Submit For Associate Acceptance
                </button>
            </form>
        </section>
    @endif
</div>
