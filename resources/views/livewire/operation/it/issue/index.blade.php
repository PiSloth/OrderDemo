<div class="mx-auto max-w-7xl px-4 py-6">
    @if (session('message'))
        <div class="mb-3 rounded-xl bg-emerald-100 px-4 py-3 text-emerald-800">{{ session('message') }}</div>
    @endif

    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Issues</h1>
        <a href="{{ route('operation.it.issues.create') }}" wire:navigate
            class="rounded-xl bg-slate-900 px-4 py-2 text-white">New Issue</a>
    </div>

    <div class="overflow-x-auto rounded-2xl border bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100">
                <tr>
                    <th class="p-3 text-left">Title</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Assigned</th>
                    <th class="p-3 text-left">Urgent</th>
                    <th class="p-3 text-left">Overdue</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($issues as $issue)
                    <tr class="border-t">
                        <td class="p-3">{{ $issue->title }}</td>
                        <td class="p-3">{{ $issue->category?->is_erp ? 'ERP' : 'IT' }}</td>
                        <td class="p-3">{{ $issue->status?->name }}</td>
                        <td class="p-3">{{ $issue->assignedUser?->name ?? '-' }}</td>
                        <td class="p-3">{{ $issue->is_urgent ? 'Yes' : 'No' }}</td>
                        <td class="p-3">{{ $issue->is_overdue ? 'Yes' : 'No' }}</td>
                        <td class="p-3">
                            <div class="flex gap-2">
                                <button wire:click="selectIssue({{ $issue->id }})"
                                    class="rounded border px-2 py-1 text-xs">Manage</button>
                                <button wire:confirm="Are you sure?" wire:click="deleteIssue({{ $issue->id }})"
                                    class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700">Delete</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $issues->links() }}</div>

    @if ($selectedIssue)
        <section class="mt-6 rounded-2xl border bg-white p-5 space-y-5">
            <h2 class="text-lg font-semibold">Manage Issue #{{ $selectedIssue->id }}</h2>

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
                <select wire:model.defer="assigned_user_id" class="rounded-xl border px-3 py-2">
                    <option value="">Assigned User (optional)</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                <button wire:click="saveIssue" class="rounded-xl bg-slate-900 px-4 py-2 text-white text-sm">Save
                    Issue</button>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr,auto]">
                <select wire:model="status_code" class="rounded-xl border px-3 py-2">
                    <option value="">Change Status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->code }}">{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
                <button wire:click="changeStatus" class="rounded-xl border px-4 py-2 text-sm">Apply Status</button>
            </div>

            <div class="space-y-2">
                <textarea wire:model.defer="message" rows="2" class="w-full rounded-xl border px-3 py-2"
                    placeholder="Add discussion message"></textarea>
                <button wire:click="addMessage" class="rounded-xl border px-4 py-2 text-sm">Add Message</button>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <h3 class="font-medium">Messages</h3>
                    <div class="mt-2 max-h-52 space-y-2 overflow-auto">
                        @forelse($selectedIssue->messages as $m)
                            <div class="rounded border p-2 text-xs"><span
                                    class="font-semibold">{{ $m->creator?->name ?? 'Unknown' }}:</span>
                                {{ $m->message }}</div>
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
</div>
