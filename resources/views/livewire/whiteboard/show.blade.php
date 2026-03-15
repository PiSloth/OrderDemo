<div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('whiteboard.board', ['item' => $content->id]) }}" wire:navigate
                    class="text-sm font-medium text-slate-500 hover:text-slate-700">Whiteboard Board</a>
                <span class="text-slate-300">/</span>
                <span class="text-sm font-medium text-slate-800">Detail</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $content->title }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $content->contentType?->name ?? 'Uncategorized' }}</span>
                @if ($content->flag)
                    <span
                        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold text-slate-700"
                        style="border-color: {{ $content->flag->color }}; background-color: {{ $content->flag->color }}20;">
                        {{ $content->flag->name }}
                    </span>
                @endif
                @if ($content->requiresDecision())
                    <span
                        class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Decision
                        Required</span>
                @else
                    <span
                        class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Decision
                        Optional</span>
                @endif
                @if ($isContentRead())
                    <span
                        class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Read</span>
                @endif
            </div>
        </div>

        <button type="button" wire:click="markAsRead"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Mark as Read
        </button>
    </div>

    @if (session()->has('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="space-y-6 xl:col-span-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</p>
                    <p class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ $content->description }}</p>
                </div>

                @if ($content->propose_solution)
                    <div class="mt-6 rounded-xl border border-sky-100 bg-sky-50 px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Proposed Solution</p>
                        <div class="prose prose-sm mt-3 max-w-none text-sky-900">
                            {!! $content->propose_solution !!}
                        </div>
                    </div>
                @endif
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Decision</h2>
                        <p class="text-sm text-slate-500">Use rich text for paragraph decisions, follow-up notes, and
                            meeting outcomes.</p>
                    </div>
                    @if ($content->requiresDecision() && !$content->latestDecision)
                        <span
                            class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Pending
                            required decision</span>
                    @endif
                </div>

                <form wire:submit.prevent="submitDecision" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Decision</label>
                        <div class="mt-1" wire:ignore>
                            <script type="application/json" data-quill-initial>@json($decision)</script>
                            <div data-quill-editor data-whiteboard-decision-editor data-model="decision"
                                data-upload-url="{{ route('document.library.upload-image') }}"
                                data-csrf="{{ csrf_token() }}"
                                class="min-h-[14rem] rounded-lg border border-slate-300 bg-white"></div>
                        </div>
                        @error('decision')
                            <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Appointment At</label>
                            <input type="datetime-local" wire:model.defer="appointment_at"
                                class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('appointment_at')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Invited Person</label>
                            <input type="text" wire:model.defer="invited_person"
                                class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Meeting owner or attendees summary">
                            @error('invited_person')
                                <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Save Decision
                        </button>
                    </div>
                </form>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Decision History</h2>
                <div class="mt-4 space-y-4">
                    @forelse ($content->decisions as $decisionRow)
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $decisionRow->creator?->name ?? 'Unknown user' }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $decisionRow->created_at->format('Y-m-d H:i') }}</p>
                                </div>
                                @if ($decisionRow->appointment_at)
                                    <span
                                        class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                                        Appointment: {{ $decisionRow->appointment_at->format('Y-m-d H:i') }}
                                    </span>
                                @endif
                            </div>
                            <div class="prose prose-sm mt-4 max-w-none text-slate-700">
                                {!! $decisionRow->decision !!}
                            </div>
                            @if ($decisionRow->invited_person)
                                <p class="mt-3 text-xs text-slate-500">Invited Person:
                                    {{ $decisionRow->invited_person }}</p>
                            @endif
                        </div>
                    @empty
                        <div
                            class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No decisions have been recorded yet.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>

        <aside class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Summary</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Reported By</dt>
                        <dd class="mt-1 text-slate-800">{{ $content->reporter?->user_name ?? 'Unknown reporter' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Created By</dt>
                        <dd class="mt-1 text-slate-800">{{ $content->creator?->name ?? 'Unknown creator' }}</dd>
                    </div>
                    @if ($content->propose_decision_due_at)
                        <div>
                            <dt class="font-semibold text-slate-500">Decision Due</dt>
                            <dd class="mt-1 text-slate-800">
                                {{ $content->propose_decision_due_at->format('Y-m-d H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Recipients</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($content->reports as $report)
                        <div class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <p class="font-medium text-slate-800">
                                {{ $report->emailList?->user_name ?? 'Recipient removed' }}</p>
                            <p class="text-xs text-slate-500">{{ $report->emailList?->email }}</p>
                            <p class="mt-1 text-xs {{ $report->is_read ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $report->is_read ? 'Read' : 'Unread' }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('whiteboard-decision-reset', () => {
                document.querySelectorAll('[data-whiteboard-decision-editor]').forEach((editorEl) => {
                    if (!editorEl.__quill) {
                        return;
                    }

                    editorEl.__quill.setContents([]);
                });
            });
        });
    </script>
@endpush
