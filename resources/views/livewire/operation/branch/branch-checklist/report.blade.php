<div class="mx-auto max-w-7xl space-y-5 px-4 py-5">
    <section class="rounded-3xl bg-white p-5 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2">
            <x-select label="Branch" placeholder="All branches" multiselect searchable :options="$branches"
                option-label="name" option-value="id" wire:model.live="selectedBranchIds" />

            <div wire:ignore x-data="{ val: @entangle('dateRange').live }" x-init="setTimeout(() => {
                if (!window.flatpickr) return;
                flatpickr($refs.range, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    onChange: (selectedDates, dateStr) => { val = dateStr; }
                });
            }, 0)">
                <label class="mb-1 block text-sm font-medium text-slate-700">Date Range Select</label>
                <input x-ref="range" type="text" placeholder="YYYY-MM-DD to YYYY-MM-DD"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" />
            </div>
        </div>

        <button wire:click="export" type="button"
            class="mt-4 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-md">
            Export checklist-report.xlsx
        </button>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Branch</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Department</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Checklist Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">User</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Remark</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-4 py-3 text-sm">{{ optional($row->checked_at)->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->branch?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->department?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->location?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->checklist?->title ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $row->user?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span
                                    class="rounded-full px-2 py-1 text-xs {{ $row->is_done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' }}">
                                    {{ $row->is_done ? 'Done' : 'Not Done' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $row->remark ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-sm text-slate-500">No report records.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
    </section>
</div>
