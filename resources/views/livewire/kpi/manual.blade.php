
    <div class="mx-auto max-w-6xl space-y-6 p-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">KPI Developer Manual</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Purpose: full technical guide for developers refactoring KPI module safely.
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                Route: <code>{{ route('kpi.manual') }}</code>
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">1) KPI Module Structure</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 text-sm text-slate-700 dark:text-slate-300">
                <div>
                    <p class="font-semibold">Livewire Pages</p>
                    <ul class="mt-1 list-disc pl-5 space-y-1">
                        <li>Dashboard: <code>App\Livewire\Kpi\Dashboard</code></li>
                        <li>My Tasks: <code>App\Livewire\Kpi\MyTasks</code></li>
                        <li>Approvals: <code>App\Livewire\Kpi\Approvals</code></li>
                        <li>Assignments: <code>App\Livewire\Kpi\Assignments</code></li>
                        <li>Templates: <code>App\Livewire\Kpi\Templates</code></li>
                        <li>Audit/Certificate/Leaderboard/Exclusions/Holidays/ImportExport</li>
                    </ul>
                </div>
                <div>
                    <p class="font-semibold">Core Services</p>
                    <ul class="mt-1 list-disc pl-5 space-y-1">
                        <li><code>KpiTaskInstanceGenerator</code> (instance generation)</li>
                        <li><code>KpiAvailabilityService</code> (daily availability/status sync)</li>
                        <li><code>KpiRuleEvaluationService</code> (scoring/evaluation)</li>
                        <li><code>KpiWorkbookService</code> (import/export)</li>
                        <li><code>KpiSubmissionImageResizer</code> (evidence image processing)</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">2) Database Design (Main Tables)</h2>
            <div class="mt-4 text-sm text-slate-700 dark:text-slate-300 space-y-2">
                <p><code>kpi_groups</code>: logical group + scoring rules.</p>
                <p><code>kpi_task_templates</code>: task definition (frequency, evidence type, cutoff, monthly count).</p>
                <p><code>kpi_task_assignments</code>: template assigned to employee + approvers + active date range.</p>
                <p><code>kpi_task_calendar_controls</code>: reminder settings.</p>
                <p><code>kpi_task_instances</code>: generated runtime tasks (daily/weekly/monthly, due_at, status).</p>
                <p><code>kpi_task_submissions</code>: employee submission history per instance.</p>
                <p><code>kpi_task_approval_steps</code>: first/final approval workflow state.</p>
                <p><code>kpi_exclusion_requests</code>, <code>employee_kpi_period_scores</code>, <code>kpi_holidays</code>: support tables.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">3) How KPI Works (Runtime Flow)</h2>
            <ol class="mt-4 list-decimal pl-5 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li>Manager creates template in <code>/kpi/templates</code>.</li>
                <li>Manager assigns template to employee in <code>/kpi/assignments</code>.</li>
                <li>Generator creates task instances by frequency:
                    daily = one per day, weekly = one per week, monthly = by <code>monthly_required_count</code>.
                </li>
                <li>Employee opens <code>/kpi/tasks</code>, uploads evidence, submits task.</li>
                <li>Approval moves: <code>waiting_first_approval</code> then <code>waiting_final_approval</code>.</li>
                <li>Final status becomes passed/failed/excluded and appears in audit/certificate/leaderboard.</li>
            </ol>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">4) Instance Generation Logic</h2>
            <div class="mt-4 text-sm text-slate-700 dark:text-slate-300 space-y-2">
                <p>Generator class: <code>App\Services\Kpi\KpiTaskInstanceGenerator</code>.</p>
                <p>Entry points:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><code>generateForUser(User $user)</code></li>
                    <li><code>generateForAll()</code></li>
                    <li><code>generateForAssignment(KpiTaskAssignment $assignment)</code></li>
                </ul>
                <p>CLI command: <code>php artisan kpi:generate-instances</code> (scheduled hourly in console kernel).</p>
                <p>Unique key prevents duplicate period instances:
                    <code>(task_assignment_id, period_type, period_start, period_end, period_index)</code>.
                </p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">5) Status and Workflow</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2 text-sm text-slate-700 dark:text-slate-300">
                <div>
                    <p class="font-semibold">Open / In Progress</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><code>pending</code></li>
                        <li><code>rejected</code></li>
                        <li><code>waiting_first_approval</code></li>
                        <li><code>waiting_final_approval</code></li>
                    </ul>
                </div>
                <div>
                    <p class="font-semibold">Finalized</p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li><code>passed</code></li>
                        <li><code>failed_late</code></li>
                        <li><code>failed_missed</code></li>
                        <li><code>excluded</code></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">6) Authorization Matrix</h2>
            <div class="mt-4 text-sm text-slate-700 dark:text-slate-300 space-y-2">
                <p>Defined in <code>App\Providers\AppServiceProvider</code> using Gates:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><code>kpiManageTemplates</code>, <code>kpiManageAssignments</code>, <code>kpiManageHolidays</code>, <code>kpiManageImports</code></li>
                    <li><code>kpiApproveTasks</code>, <code>kpiApproveExclusions</code>, <code>kpiViewCompanyLeaderboard</code></li>
                    <li><code>isSuperAdmin</code> for top-level overrides.</li>
                </ul>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">7) Developer Refactor Guide</h2>
            <ol class="mt-4 list-decimal pl-5 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li>Keep generation idempotent. Never remove unique period constraint.</li>
                <li>Keep status transitions explicit and centralized (avoid mixed side effects in views).</li>
                <li>Do not break approval chain ordering by <code>step_order</code>.</li>
                <li>Preserve assignment date-window checks (<code>starts_on</code>, <code>ends_on</code>).</li>
                <li>When changing frequency logic, update generator + audit + dashboard summaries together.</li>
                <li>Guard destructive actions with role checks and validation.</li>
                <li>If adding status values, update:
                    MyTasks, Approvals, Audit, Certificate, Leaderboard, any exports/imports.
                </li>
            </ol>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">8) Quick Start for New Developer</h2>
            <ol class="mt-4 list-decimal pl-5 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li>Create group and template in <code>/kpi/templates</code>.</li>
                <li>Create assignment in <code>/kpi/assignments</code>.</li>
                <li>Run command: <code>php artisan kpi:generate-instances --user_id=USER_ID</code>.</li>
                <li>Login as employee and submit in <code>/kpi/tasks</code>.</li>
                <li>Login as approver and approve in <code>/kpi/approvals</code>.</li>
                <li>Check reporting in <code>/kpi/audit</code>, <code>/kpi/certificate</code>, <code>/kpi/leaderboard</code>.</li>
            </ol>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">9) FAQ</h2>
            <div class="mt-4 space-y-4 text-sm text-slate-700 dark:text-slate-300">
                <div>
                    <p class="font-semibold">Q1: How are instances created?</p>
                    <p class="mt-1">
                        Instances are generated from active assignments and active templates by
                        <code>App\Services\Kpi\KpiTaskInstanceGenerator</code>. The scheduler runs
                        <code>php artisan kpi:generate-instances</code> hourly, and assignment create/update also triggers generation.
                    </p>
                </div>

                <div>
                    <p class="font-semibold">Q2: If template setting changes, do existing instances change automatically?</p>
                    <p class="mt-1">
                        No. Existing rows in <code>kpi_task_instances</code> are not auto-migrated. Template changes affect future generation only.
                    </p>
                </div>

                <div>
                    <p class="font-semibold">Q3: Example: template changed from daily to monthly on next day. What happens?</p>
                    <p class="mt-1">
                        Daily instances already created before the change remain daily. On the next generator run, new instances follow monthly logic.
                        Temporary mixed periods (old daily + new monthly) are expected behavior.
                    </p>
                    <p class="mt-1">
                        Count of existing daily instances = however many were already generated before the frequency edit
                        (for example yesterday daily, and possibly today daily if generation already ran before the change).
                    </p>
                    <p class="mt-1 font-medium">Recommended migration steps:</p>
                    <ol class="mt-1 list-decimal pl-5 space-y-1">
                        <li>Set old assignment inactive or set <code>ends_on</code>.</li>
                        <li>Update/create assignment for the monthly template behavior.</li>
                        <li>Use Super Admin instance management in <code>/kpi/assignments</code> to clean old pending daily instances if needed.</li>
                    </ol>
                </div>

                <div>
                    <p class="font-semibold">Q4: If user uploaded wrong photo, how can it be edited?</p>
                    <p class="mt-1">
                        Before submit: user can remove and re-upload in task form.
                        After submit: image is stored in submission history and direct edit is not supported.
                        Current flow is approver rejects, then employee resubmits with corrected photos (new submission sequence).
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">10) Associate Task Workflow (Phase 1 - Implemented)</h2>
            <ol class="mt-4 list-decimal pl-5 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li>Uploader creates a run in <code>/kpi/associate-tasks</code> from an active associate group.</li>
                <li>Uploader uploads shared photos once and submits for associate acceptance.</li>
                <li>Associated members accept/reject the run from their pending list.</li>
                <li>System tracks required acceptance count.</li>
                <li>When all required associates accept, status moves to <code>waiting_first_approval</code> and approval steps are created.</li>
            </ol>
            <p class="mt-3 text-sm text-slate-700 dark:text-slate-300">
                Phase 1 includes: group run tables, shared submission, associate accept/reject, and automatic handoff to first approver queue.
            </p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100">11) Phase 2 Plan</h2>
            <ol class="mt-4 list-decimal pl-5 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                <li>Implemented: Associate group configuration UI in <code>/kpi/associate-tasks</code> (create group, manage members, required flags).</li>
                <li>Implemented: Approver action UI for associate runs (first/final approve/reject with remarks).</li>
                <li>Implemented: Reopen and resubmit flow after associate/approver rejection.</li>
                <li>Pending: Controlled random associate assignment with fairness/rotation policy.</li>
                <li>Pending: Report integration in KPI audit, certificate, and leaderboard summaries.</li>
            </ol>
        </section>
    </div>
