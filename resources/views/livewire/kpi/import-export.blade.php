<div class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white px-6 py-7 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <p class="text-sm uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Import / Export</p>
        <h2 class="mt-2 text-3xl font-semibold text-slate-900 dark:text-slate-100">Employee KPI workbook import.</h2>
        <p class="mt-3 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
            Use one workbook per employee. The file contains a <span class="font-medium">tasks</span> sheet for group, template, and assignment rows, a <span class="font-medium">holidays</span> sheet for employee holidays, and a <span class="font-medium">table_fields</span> sheet for custom evidence columns.
        </p>
    </section>

    @if (session()->has('message'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300">
            <p>{{ session('error') }}</p>
            @if ($errorReportUrl)
                <a href="{{ $errorReportUrl }}" class="mt-2 inline-flex text-sm font-medium underline underline-offset-2">
                    Download failed rows CSV
                </a>
            @endif
        </div>
    @endif

    <section class="grid gap-6 xl:grid-cols-[1fr_1fr]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Download Standard Template</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Download a blank workbook with instructions, sample rows, and the required sheet structure before preparing your employee import file.
            </p>

            <div class="mt-5">
                <a
                    href="{{ route('kpi.import-export.template') }}"
                    class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
                >
                    Download Blank Workbook
                </a>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Export Existing Employee Data</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Choose one employee to export the current KPI group, template, assignment, holiday, and table-field setup into the same workbook format used for import.
            </p>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="text-sm font-medium text-slate-700 dark:text-slate-200">Employee</label>
                    <div class="mt-1">
                        <x-select
                            label=""
                            placeholder="Search employee"
                            wire:model="selectedEmployeeId"
                            :async-data="$employeeAsyncData"
                            option-label="name"
                            option-value="id"
                        />
                    </div>
                </div>

                @if ($selectedEmployeeExportUrl)
                    <a
                        href="{{ $selectedEmployeeExportUrl }}"
                        class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                    >
                        Export Employee Workbook
                    </a>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                        Select an employee first.
                    </div>
                @endif
            </div>
        </article>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Import Workbook</h3>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
            Import stops for the whole file if any row fails validation. When that happens, a CSV report is generated with the row number and error message.
        </p>

        <form action="{{ route('kpi.import-export.import') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf

            <div>
                <label for="kpi-workbook" class="text-sm font-medium text-slate-700 dark:text-slate-200">Workbook File</label>
                <input
                    id="kpi-workbook"
                    name="workbook"
                    type="file"
                    accept=".xlsx"
                    class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:file:bg-slate-100 dark:file:text-slate-900 dark:hover:file:bg-slate-200"
                >
                @error('workbook')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                <p class="font-medium text-slate-900 dark:text-slate-100">Import rules</p>
                <ul class="mt-2 space-y-1">
                    <li>One workbook must contain one employee only.</li>
                    <li>Employee is identified by <span class="font-medium">employee_email</span>.</li>
                    <li>Approvers are matched by email address.</li>
                    <li>Manager scope is limited to employees and KPI groups in the manager's department.</li>
                    <li>Assignments generate current-period task instances immediately after import.</li>
                    <li>The blank workbook includes sample rows. Replace them before import.</li>
                </ul>
            </div>

            <button
                type="submit"
                class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
            >
                Import Workbook
            </button>
        </form>
    </section>
</div>
