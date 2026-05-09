<?php

namespace App\Livewire\Operation;

use App\Models\Branch;
use App\Models\DailyNoteAcknowledgement;
use App\Models\DailyNote;
use App\Models\NoteTitle;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\SimpleExcel\SimpleExcelWriter;
use WireUi\Traits\Actions;

#[Layout('components.layouts.operation')]
#[Title('Daily Operation Notes')]
class DailyNotesList extends Component
{
    use Actions;
    public string $activeTab = 'opened';
    public string $viewMode = 'list';
    public string $selectedDate = '';
    public bool $showNoteModal = false;
    public bool $showMessageModal = false;
    public ?int $activeTitleId = null;
    public ?int $activeNoteId = null;
    public ?int $messageNoteId = null;
    public string $note = '';
    public ?string $quickInputMode = null;
    public string $quickNumber = '';
    public string $quickDateTime = '';
    public bool $is_number = false;
    public $created_date = null;
    public $updated_date = null;
    public $edit_mode = false;
    public string $search = '';
    public array $selectedBranchIds = [];
    public string $listStatusFilter = 'all';
    public array $exportBranchIds = [];
    public array $exportTitleIds = [];
    public string $exportDateRange = '';

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function updatedSelectedDate(): void
    {
        $this->validate([
            'selectedDate' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $this->closeModal();
        $this->closeMessageModal();
    }

    public function updatedSelectedBranchIds($value): void
    {
        $branchIds = collect(is_array($value) ? $value : [])
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($branchIds !== $this->selectedBranchIds) {
            $this->selectedBranchIds = $branchIds;
        }

        $this->closeModal();
        $this->closeMessageModal();
    }

    public function exportDailyNotesReport()
    {
        $titleIds = collect($this->exportTitleIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        if ($titleIds->isEmpty()) {
            $this->notification([
                'title' => 'Title filter required',
                'description' => 'Please choose at least one title before export.',
                'icon' => 'warning',
            ]);
            return;
        }

        [$fromDate, $toDate] = $this->resolveExportDateRange();

        $branchIds = collect($this->exportBranchIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        $notes = DailyNote::query()
            ->forUser(Auth::user())
            ->with(['title', 'branch', 'department', 'creator', 'messages.user', 'acknowledgements.user'])
            ->whereIn('title_id', $titleIds->all())
            ->when($branchIds->isNotEmpty(), fn($query) => $query->whereIn('branch_id', $branchIds->all()))
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->orderBy('date')
            ->orderBy('branch_id')
            ->orderBy('department_id')
            ->orderBy('created_by')
            ->get();

        if ($notes->isEmpty()) {
            $this->notification([
                'title' => 'No record found',
                'description' => 'No notes matched your export filters.',
                'icon' => 'warning',
            ]);
            return;
        }

        $selectedTitles = NoteTitle::query()
            ->whereIn('id', $titleIds->all())
            ->orderBy('id')
            ->get();

        $headers = ['Date', 'Branch Name', 'Department Name', 'Report By Name'];
        foreach ($selectedTitles as $title) {
            $headers[] = $title->name . ' - Note';
            $headers[] = $title->name . ' - Contact Note Message With User';
            $headers[] = $title->name . ' - Acknowledged By';
            $headers[] = $title->name . ' - Late Status';
            $headers[] = $title->name . ' - Late Ack';
        }

        $grouped = $notes->groupBy(function (DailyNote $note) {
            return implode('|', [
                optional($note->date)->format('Y-m-d'),
                (string) $note->branch_id,
                (string) $note->department_id,
                (string) $note->created_by,
            ]);
        });

        $tempFilePath = tempnam(sys_get_temp_dir(), 'daily_notes_export_') . '.xlsx';
        $writer = SimpleExcelWriter::create($tempFilePath)->addHeader($headers);

        foreach ($grouped as $items) {
            /** @var DailyNote $base */
            $base = $items->first();
            $row = [
                optional($base->date)->format('Y-m-d'),
                $base->branch?->name ?? '-',
                $base->department?->name ?? '-',
                $base->creator?->name ?? '-',
            ];

            foreach ($selectedTitles as $title) {
                /** @var DailyNote|null $titleNote */
                $titleNote = $items->first(fn(DailyNote $n) => (int) $n->title_id === (int) $title->id);

                if (!$titleNote) {
                    $row[] = '-';
                    $row[] = '-';
                    $row[] = '-';
                    $row[] = '-';
                    $row[] = '-';
                    continue;
                }

                $messageText = $titleNote->messages
                    ->map(function ($message) {
                        $author = $message->user?->name ?? 'Unknown';
                        return $author . ': ' . trim((string) $message->message);
                    })
                    ->filter()
                    ->implode(' | ');

                $ackText = $titleNote->acknowledgements
                    ->map(fn($ack) => $ack->user?->name)
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $lateAckText = $titleNote->acknowledgements
                    ->map(function ($ack) use ($titleNote) {
                        $name = $ack->user?->name;

                        if (!$name) {
                            return null;
                        }

                        return $this->isLateAcknowledgement($titleNote, $ack)
                            ? $name . ' (Late Ack)'
                            : $name . ' (On Time)';
                    })
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $row[] = $titleNote->note ?: '-';
                $row[] = $messageText !== '' ? $messageText : '-';
                $row[] = $ackText !== '' ? $ackText : '-';
                $row[] = $this->isBackDateNote($titleNote) ? 'Back Date Note' : 'On Time';
                $row[] = $lateAckText !== '' ? $lateAckText : '-';
            }

            $writer->addRow($row);
        }

        $writer->close();

        return Response::download($tempFilePath, 'daily-notes-report.xlsx')->deleteFileAfterSend(true);
    }

    protected function resolveExportDateRange(): array
    {
        if (trim($this->exportDateRange) === '') {
            $date = Carbon::parse($this->effectiveDate());
            return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
        }

        if (!str_contains($this->exportDateRange, ' to ')) {
            $date = Carbon::parse(trim($this->exportDateRange));
            return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
        }

        [$from, $to] = explode(' to ', $this->exportDateRange);
        $fromDate = Carbon::parse(trim($from))->startOfDay();
        $toDate = Carbon::parse(trim($to))->endOfDay();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }

    #[On('note-read-updated')]
    public function refreshReadState(): void
    {
        // Event hook to trigger component re-render when chat read status changes.
    }

    public function openTitle(int $titleId): void
    {
        $title = NoteTitle::query()->where('is_active', true)->findOrFail($titleId);
        $note = $this->findDailyNote($title);

        $this->activeTitleId = $title->id;
        $this->activeNoteId = $note?->id;
        $this->note = (string) ($note->note ?? '');
        $this->quickInputMode = null;
        $this->quickNumber = '';
        $this->quickDateTime = '';
        $this->is_number = (bool) ($note->is_number ?? false);
        $this->created_date = $note?->created_at;
        $this->updated_date = $note?->updated_at;
        $this->edit_mode = false;
        $this->showNoteModal = true;
    }

    public function editNote(): void
    {
        $this->edit_mode = true;
    }

    public function closeModal(): void
    {
        $this->reset('showNoteModal', 'activeTitleId', 'activeNoteId', 'note', 'is_number', 'quickInputMode', 'quickNumber', 'quickDateTime');
    }

    public function openMessageModal(int $noteId): void
    {
        $note = DailyNote::query()
            ->forUser(Auth::user())
            ->find($noteId);

        if (!$note) {
            $this->notification([
                'title' => 'Note not available',
                'description' => 'This note cannot be opened for message view.',
                'icon' => 'warning',
            ]);
            return;
        }

        $this->messageNoteId = $note->id;
        $this->showMessageModal = true;
    }

    public function closeMessageModal(): void
    {
        $this->reset('showMessageModal', 'messageNoteId');
    }

    public function acknowledgeTitle(int $titleId): void
    {
        $userId = (int) Auth::id();
        $now = now();

        $noteIds = $this->baseTodayNotes()
            ->where('title_id', $titleId)
            ->where('created_by', '!=', $userId)
            ->pluck('id');

        if ($noteIds->isEmpty()) {
            $this->notification([
                'title' => 'No notes to acknowledge',
                'description' => 'There are no posted notes from others in this group.',
                'icon' => 'warning',
            ]);
            return;
        }

        $alreadyAcknowledged = DailyNoteAcknowledgement::query()
            ->where('user_id', $userId)
            ->whereIn('note_id', $noteIds)
            ->pluck('note_id');

        $pendingNoteIds = $noteIds->diff($alreadyAcknowledged)->values();

        if ($pendingNoteIds->isEmpty()) {
            $this->notification([
                'title' => 'Already acknowledged',
                'description' => 'You already checked all notes in this group.',
                'icon' => 'success',
            ]);
            return;
        }

        DailyNoteAcknowledgement::query()->insert(
            $pendingNoteIds->map(function ($noteId) use ($userId, $now) {
                return [
                    'note_id' => $noteId,
                    'user_id' => $userId,
                    'acknowledged_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all()
        );

        $this->notification([
            'title' => 'Acknowledged',
            'description' => $pendingNoteIds->count() . ' note(s) marked as checked.',
            'icon' => 'success',
        ]);
    }

    public function saveNote(): void
    {
        if (!$this->persistNote()) {
            $this->notification([
                'title' => 'Nothing to save',
                'description' => 'Write a note first, then save.',
                'icon' => 'warning',
            ]);
            return;
        }

        $this->notification([
            'title' => 'Success',
            'description' => 'Daily note saved successfully.',
            'icon' => 'success',
        ]);

        // session()->flash('message', 'Daily note saved.');
    }

    public function openQuickInput(string $mode): void
    {
        if (!in_array($mode, ['number', 'datetime'], true)) {
            return;
        }

        $this->quickInputMode = $mode;
    }

    public function appendQuickNumber(): void
    {
        $validated = $this->validate([
            'quickNumber' => ['required', 'numeric'],
        ]);

        $this->edit_mode = true;

        $this->appendToNote((string) $validated['quickNumber']);
        $this->quickNumber = '';
    }

    public function appendQuickDateTime(): void
    {
        $validated = $this->validate([
            'quickDateTime' => ['required', 'date'],
        ]);

        $formatted = Carbon::parse($validated['quickDateTime'])->format('Y-m-d H:i');
        $this->appendToNote($formatted);
        $this->quickDateTime = '';
    }

    protected function appendToNote(string $value): void
    {
        $value = trim($value);

        if ($value === '') {
            return;
        }

        $this->note = trim($this->note) === ''
            ? $value
            : rtrim($this->note) . PHP_EOL . $value;
    }

    public function saveAndNext(): void
    {
        $currentTitleId = $this->activeTitleId;
        $saved = $this->persistNote();

        $nextTitle = $this->openedTitleCards()
            ->pluck('title')
            ->first(fn(NoteTitle $title) => $title->id !== $currentTitleId);

        if ($nextTitle) {
            $this->openTitle($nextTitle->id);
            return;
        }

        $this->notification($saved
            ? [
                'title' => 'Success',
                'description' => 'Daily note saved. No more open titles.',
                'icon' => 'success',
            ]
            : [
                'title' => 'Nothing to save',
                'description' => 'Write a note first, then save.',
                'icon' => 'warning',
            ]);
        $this->closeModal();
    }

    public function markFinished(): void
    {
        $note = $this->currentNote();
        if (!$note) {
            $this->notification([
                'title' => 'Nothing to finish',
                'description' => 'Write and save a note first.',
                'icon' => 'warning',
            ]);
            return;
        }

        $note->update([
            'note' => $this->note !== '' ? $this->note : null,
            'is_number' => $this->is_number,
            'completed_at' => now(),
            'completed_by' => Auth::id(),
        ]);

        $this->notification([
            'title' => 'Success',
            'description' => 'Daily note marked as finished.',
            'icon' => 'success',
        ]);
        $this->closeModal();
    }

    protected function persistNote(): bool
    {
        $this->validate([
            'note' => ['nullable', 'string', 'max:255'],
            'is_number' => ['boolean'],
        ]);

        $note = $this->findOrCreateCurrentNoteForSave();
        if (!$note) {
            return false;
        }

        $note->update([
            'note' => $this->note !== '' ? $this->note : null,
            'is_number' => $this->is_number,
        ]);

        return true;
    }

    protected function currentNote(): ?DailyNote
    {
        if (!$this->activeNoteId) {
            return null;
        }

        return DailyNote::query()
            ->forUser(Auth::user())
            ->with(['title', 'location', 'department', 'branch'])
            ->findOrFail($this->activeNoteId);
    }

    protected function findDailyNote(NoteTitle $title): ?DailyNote
    {
        $user = Auth::user();

        return DailyNote::query()
            ->where('title_id', $title->id)
            ->where('location_id', $user->location_id)
            ->where('department_id', $user->department_id)
            ->where('branch_id', $user->branch_id)
            ->whereDate('date', $this->effectiveDate())
            ->first();
    }

    protected function findOrCreateCurrentNoteForSave(): ?DailyNote
    {
        $existingNote = $this->currentNote();
        if ($existingNote) {
            return $existingNote;
        }

        $noteText = trim($this->note);
        if ($noteText === '' || !$this->activeTitleId) {
            return null;
        }

        $user = Auth::user();

        $note = DailyNote::query()->create([
            'title_id' => $this->activeTitleId,
            'location_id' => $user->location_id,
            'department_id' => $user->department_id,
            'branch_id' => $user->branch_id,
            'date' => $this->effectiveDate(),
            'created_by' => $user->id,
            'note' => $noteText,
            'is_number' => $this->is_number,
        ]);

        $this->activeNoteId = $note->id;

        return $note;
    }

    protected function effectiveDate(): string
    {
        return $this->selectedDate !== '' ? $this->selectedDate : now()->toDateString();
    }

    protected function notedUsers(?DailyNote $note): Collection
    {
        if (!$note) {
            return collect();
        }

        $users = collect();

        if ($note->creator) {
            $users->push($note->creator);
        }

        if ($note->relationLoaded('messages')) {
            $messageUsers = $note->messages
                ->pluck('user')
                ->filter(fn($user) => $user instanceof User);

            $users = $users->merge($messageUsers);
        }

        return $users
            ->unique('id')
            ->take(5)
            ->map(fn(User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'photo' => $user->profile_photo_url,
            ])
            ->values();
    }

    protected function baseTodayNotes()
    {
        $userId = (int) Auth::id();
        $search = trim($this->search);
        $branchIds = collect($this->selectedBranchIds)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values();

        return DailyNote::query()
            ->forUser(Auth::user())
            ->forDate($this->effectiveDate())
            ->with(['title', 'location', 'department', 'branch', 'creator', 'messages.user', 'acknowledgements.user'])
            ->when($branchIds->isNotEmpty(), function ($query) use ($branchIds) {
                $query->whereIn('branch_id', $branchIds->all());
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('title', function ($titleQuery) use ($search) {
                    $titleQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('remark', 'like', '%' . $search . '%');
                });
            })
            ->withCount([
                'messages',
                'messages as unread_messages_count' => function ($query) use ($userId) {
                    $query->where('user_id', '!=', $userId)
                        ->whereDoesntHave('readReceipts', function ($readQuery) use ($userId) {
                            $readQuery->where('user_id', $userId);
                        });
                },
            ]);
    }

    protected function openedTitleCards(): Collection
    {
        $userId = (int) Auth::id();
        $search = trim($this->search);
        $notes = $this->baseTodayNotes()->open()->get()->keyBy('title_id');

        return NoteTitle::query()
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($titleQuery) use ($search) {
                    $titleQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('remark', 'like', '%' . $search . '%');
                });
            })
            ->whereHas('creator', function ($query) {
                $user = Auth::user();
                $query->where('department_id', $user->department_id);
            })
            ->orderBy('id')
            ->get()
            ->filter(function (NoteTitle $title) use ($notes, $userId) {
                $note = $notes->get($title->id);

                return !$note || ((int) $note->created_by !== $userId && $note->completed_at === null);
            })
            ->map(function (NoteTitle $title) use ($notes) {
                $note = $notes->get($title->id);

                return [
                    'title' => $title,
                    'note' => $note,
                    'message_count' => $note?->messages_count ?? 0,
                    'unread_message_count' => $note?->unread_messages_count ?? 0,
                    'has_no_messages' => $note ? $note->messages_count === 0 : true,
                    'noted_users' => $this->notedUsers($note),
                ];
            })
            ->values();
    }

    protected function filteredOpenedCards(Collection $openedCards): Collection
    {
        if ($this->listStatusFilter === 'empty') {
            return $openedCards->filter(function (array $card) {
                $noteText = trim((string) ($card['note']?->note ?? ''));
                return $noteText === '';
            })->values();
        }

        if ($this->listStatusFilter === 'noted') {
            return $openedCards->filter(function (array $card) {
                $noteText = trim((string) ($card['note']?->note ?? ''));
                return $noteText !== '';
            })->values();
        }

        return $openedCards;
    }

    protected function listTitleCards(): Collection
    {
        $search = trim($this->search);
        $notes = $this->baseTodayNotes()->get()->keyBy('title_id');

        return NoteTitle::query()
            ->where('is_active', true)
            //notes which Auth:user() has access based on his departement must same with the note title creator department.
            //Title table has createdBy field which is the user id of the creator of the title, and user table has department_id field which is the department id of the user, so we can use whereHas to filter the titles based on the creator's department.
            ->whereHas('creator', function ($query) {
                $user = Auth::user();
                $query->where('department_id', $user->department_id);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($titleQuery) use ($search) {
                    $titleQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('remark', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('id')
            ->get()
            ->map(function (NoteTitle $title) use ($notes) {
                $note = $notes->get($title->id);

                return [
                    'title' => $title,
                    'note' => $note,
                    'message_count' => $note?->messages_count ?? 0,
                    'unread_message_count' => $note?->unread_messages_count ?? 0,
                    'noted_users' => $this->notedUsers($note),
                ];
            })
            ->values();
    }

    protected function filteredListCards(Collection $listCards): Collection
    {
        if ($this->listStatusFilter === 'empty') {
            return $listCards->filter(function (array $card) {
                $noteText = trim((string) ($card['note']?->note ?? ''));
                return $noteText === '';
            })->values();
        }

        if ($this->listStatusFilter === 'noted') {
            return $listCards->filter(function (array $card) {
                $noteText = trim((string) ($card['note']?->note ?? ''));
                return $noteText !== '';
            })->values();
        }

        return $listCards;
    }

    protected function finishedNotes(): Collection
    {
        $userId = (int) Auth::id();

        return $this->baseTodayNotes()
            ->where(function ($query) use ($userId) {
                $query->whereNotNull('completed_at')
                    ->orWhere('created_by', $userId);
            })
            ->latest('updated_at')
            ->get();
    }

    protected function recentNotes(): Collection
    {
        return $this->baseTodayNotes()
            ->where('updated_at', '>=', now()->subHour())
            ->latest('updated_at')
            ->limit(3)
            ->get();
    }

    protected function isBackDateNote(DailyNote $note): bool
    {
        if (!$note->created_at || !$note->date) {
            return false;
        }

        return Carbon::parse($note->created_at)->toDateString() > Carbon::parse($note->date)->toDateString();
    }

    protected function isLateAcknowledgement(DailyNote $note, DailyNoteAcknowledgement $ack): bool
    {
        if (!$ack->acknowledged_at || !$note->date) {
            return false;
        }

        $deadlineDate = Carbon::parse($note->date)->addDay()->toDateString();
        $ackDate = Carbon::parse($ack->acknowledged_at)->toDateString();

        return $ackDate > $deadlineDate;
    }

    protected function tableViewGroups(): Collection
    {
        $userId = (int) Auth::id();
        $user = Auth::user();
        $notesByTitle = DailyNote::query()
            ->forUser($user)
            ->forDate($this->effectiveDate())
            ->with(['title', 'location', 'department', 'branch', 'creator', 'messages.user', 'acknowledgements.user'])
            ->withCount([
                'messages',
                'messages as unread_messages_count' => function ($query) use ($userId) {
                    $query->where('user_id', '!=', $userId)
                        ->whereDoesntHave('readReceipts', function ($readQuery) use ($userId) {
                            $readQuery->where('user_id', $userId);
                        });
                },
            ])
            ->get()
            ->groupBy('title_id');

        return NoteTitle::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->map(function (NoteTitle $title) use ($notesByTitle, $userId, $user) {
                $items = $notesByTitle->get($title->id, collect());
                $rows = $items->map(function (DailyNote $note) use ($userId) {
                    $ackUsers = $note->acknowledgements
                        ->pluck('user.name')
                        ->filter()
                        ->unique()
                        ->values();
                    $isAcknowledgedByMe = $note->acknowledgements
                        ->contains(fn($ack) => (int) $ack->user_id === $userId);
                    $myAcknowledgement = $note->acknowledgements
                        ->first(fn($ack) => (int) $ack->user_id === $userId);

                    return [
                        'title_id' => $note->title_id,
                        'note_id' => $note->id,
                        'note' => $note->note,
                        'created_by' => $note->creator?->name,
                        'created_by_photo' => $note->creator?->profile_photo_url,
                        'created_by_id' => $note->created_by,
                        'branch_name' => $note->branch?->name ?? '-',
                        'ack_users' => $ackUsers,
                        'is_acknowledged_by_me' => $isAcknowledgedByMe,
                        'late_status' => $this->isBackDateNote($note) ? 'Back Date Note' : 'On Time',
                        'late_ack_status' => !$myAcknowledgement
                            ? 'Pending'
                            : ($this->isLateAcknowledgement($note, $myAcknowledgement) ? 'Late Ack' : 'On Time'),
                        'unread_message_count' => $note->unread_messages_count ?? 0,
                    ];
                });

                $notedUsers = $items
                    ->flatMap(fn(DailyNote $note) => $this->notedUsers($note))
                    ->unique('id')
                    ->values();

                if ($rows->isEmpty()) {
                    $rows = collect([[
                        'title_id' => $title->id,
                        'note_id' => null,
                        'note' => null,
                        'created_by' => null,
                        'created_by_photo' => null,
                        'created_by_id' => null,
                        'branch_name' => $user->branch?->name ?? '-',
                        'ack_users' => collect(),
                        'is_acknowledged_by_me' => false,
                        'late_status' => '-',
                        'late_ack_status' => '-',
                        'unread_message_count' => 0,
                    ]]);
                }

                return [
                    'title' => $title,
                    'title_id' => $title->id,
                    'remark' => $title->remark,
                    'rows' => $rows,
                    'noted_users' => $notedUsers,
                    'has_unacknowledged' => $rows->contains(function (array $row) use ($userId) {
                        return $row['note_id']
                            && (int) $row['created_by_id'] !== $userId
                            && !$row['is_acknowledged_by_me'];
                    }),
                ];
            })
            ->values();
    }

    public function render()
    {
        $userId = (int) Auth::id();
        $openedCards = $this->openedTitleCards();
        $filteredOpenedCards = $this->filteredOpenedCards($openedCards);
        $listCards = $this->listTitleCards();
        $filteredListCards = $this->filteredListCards($listCards);
        $finishedNotes = $this->finishedNotes();
        $recentNotes = $this->recentNotes();
        $tableGroups = collect();
        $activeNote = $this->activeNoteId ? $this->currentNote() : null;
        $activeTitle = $this->activeTitleId ? NoteTitle::query()->find($this->activeTitleId) : null;
        $user = Auth::user();

        if ($this->viewMode === 'table') {
            $tableGroups = $this->tableViewGroups();
        } elseif ($this->activeTab === 'opened') {
            $tableGroups = $openedCards->map(function (array $card) use ($user, $userId) {
                $note = $card['note'];
                $title = $card['title'];
                $ackUsers = $note
                    ? $note->acknowledgements
                    ->pluck('user.name')
                    ->filter()
                    ->unique()
                    ->values()
                    : collect();
                $isAcknowledgedByMe = $note
                    ? $note->acknowledgements->contains(fn($ack) => (int) $ack->user_id === $userId)
                    : false;

                return [
                    'title' => $title,
                    'title_id' => $title->id,
                    'remark' => $title->remark,
                    'rows' => collect([[
                        'title_id' => $title->id,
                        'note_id' => $note?->id,
                        'note' => $note?->note,
                        'created_by' => $note?->creator?->name,
                        'created_by_photo' => $note?->creator?->profile_photo_url,
                        'created_by_id' => $note?->created_by,
                        'branch_name' => $note?->branch?->name ?? ($user->branch?->name ?? '-'),
                        'ack_users' => $ackUsers,
                        'is_acknowledged_by_me' => $isAcknowledgedByMe,
                        'late_status' => $note ? ($this->isBackDateNote($note) ? 'Back Date Note' : 'On Time') : '-',
                        'late_ack_status' => $note && $isAcknowledgedByMe
                            ? (($myAck = $note->acknowledgements->first(fn($ack) => (int) $ack->user_id === $userId)) && $this->isLateAcknowledgement($note, $myAck) ? 'Late Ack' : 'On Time')
                            : ($note ? 'Pending' : '-'),
                        'unread_message_count' => $note?->unread_messages_count ?? 0,
                    ]]),
                    'has_unacknowledged' => $note
                        ? ((int) $note->created_by !== $userId && !$isAcknowledgedByMe)
                        : false,
                ];
            });
        } elseif ($this->activeTab === 'finished') {
            $tableGroups = $finishedNotes
                ->groupBy('title_id')
                ->map(function (Collection $items) use ($userId) {
                    $first = $items->first();
                    $rows = $items->map(function (DailyNote $note) use ($userId) {
                        $ackUsers = $note->acknowledgements
                            ->pluck('user.name')
                            ->filter()
                            ->unique()
                            ->values();
                        $isAcknowledgedByMe = $note->acknowledgements
                            ->contains(fn($ack) => (int) $ack->user_id === $userId);

                        return [
                            'title_id' => $note->title_id,
                            'note_id' => $note->id,
                            'note' => $note->note,
                            'created_by' => $note->creator?->name,
                            'created_by_photo' => $note->creator?->profile_photo_url,
                            'created_by_id' => $note->created_by,
                            'branch_name' => $note->branch?->name ?? '-',
                            'ack_users' => $ackUsers,
                            'is_acknowledged_by_me' => $isAcknowledgedByMe,
                            'late_status' => $this->isBackDateNote($note) ? 'Back Date Note' : 'On Time',
                            'late_ack_status' => !$isAcknowledgedByMe
                                ? 'Pending'
                                : (($myAck = $note->acknowledgements->first(fn($ack) => (int) $ack->user_id === $userId)) && $this->isLateAcknowledgement($note, $myAck) ? 'Late Ack' : 'On Time'),
                            'unread_message_count' => $note->unread_messages_count ?? 0,
                        ];
                    });

                    return [
                        'title' => $first->title,
                        'title_id' => $first->title_id,
                        'remark' => $first->title?->remark,
                        'rows' => $rows,
                        'has_unacknowledged' => $rows->contains(function (array $row) use ($userId) {
                            return $row['note_id']
                                && (int) $row['created_by_id'] !== $userId
                                && !$row['is_acknowledged_by_me'];
                        }),
                    ];
                })
                ->values();
        } elseif ($this->activeTab === 'recent') {
            $tableGroups = $recentNotes
                ->groupBy('title_id')
                ->map(function (Collection $items) use ($userId) {
                    $first = $items->first();
                    $rows = $items->map(function (DailyNote $note) use ($userId) {
                        $ackUsers = $note->acknowledgements
                            ->pluck('user.name')
                            ->filter()
                            ->unique()
                            ->values();
                        $isAcknowledgedByMe = $note->acknowledgements
                            ->contains(fn($ack) => (int) $ack->user_id === $userId);

                        return [
                            'title_id' => $note->title_id,
                            'note_id' => $note->id,
                            'note' => $note->note,
                            'created_by' => $note->creator?->name,
                            'created_by_photo' => $note->creator?->profile_photo_url,
                            'created_by_id' => $note->created_by,
                            'branch_name' => $note->branch?->name ?? '-',
                            'ack_users' => $ackUsers,
                            'is_acknowledged_by_me' => $isAcknowledgedByMe,
                            'late_status' => $this->isBackDateNote($note) ? 'Back Date Note' : 'On Time',
                            'late_ack_status' => !$isAcknowledgedByMe
                                ? 'Pending'
                                : (($myAck = $note->acknowledgements->first(fn($ack) => (int) $ack->user_id === $userId)) && $this->isLateAcknowledgement($note, $myAck) ? 'Late Ack' : 'On Time'),
                            'unread_message_count' => $note->unread_messages_count ?? 0,
                        ];
                    });

                    return [
                        'title' => $first->title,
                        'title_id' => $first->title_id,
                        'remark' => $first->title?->remark,
                        'rows' => $rows,
                        'has_unacknowledged' => $rows->contains(function (array $row) use ($userId) {
                            return $row['note_id']
                                && (int) $row['created_by_id'] !== $userId
                                && !$row['is_acknowledged_by_me'];
                        }),
                    ];
                })
                ->values();
        }

        return view('livewire.operation.daily-notes-list', [
            'openedCards' => $openedCards,
            'filteredOpenedCards' => $filteredOpenedCards,
            'listCards' => $listCards,
            'filteredListCards' => $filteredListCards,
            'openedBadgeCount' => $openedCards->where('has_no_messages', true)->count(),
            'finishedNotes' => $finishedNotes,
            'recentNotes' => $recentNotes,
            'tableGroups' => $tableGroups,
            'showRecentTab' => $recentNotes->isNotEmpty(),
            'activeNote' => $activeNote,
            'activeTitle' => $activeTitle,
            'userLocationName' => $user->location?->name ?? 'Unknown location',
            'todayLabel' => Carbon::parse($this->effectiveDate())->format('Y-m-d'),
            'isSelectedToday' => Carbon::parse($this->effectiveDate())->isToday(),
            'branchOptions' => Branch::select('id', 'name')
                ->orderBy('name')
                ->get()
                ->map(function ($b) {
                    $b->name = ucfirst($b->name);
                    return $b;
                }),
            'titleOptions' => NoteTitle::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($title) {
                    $title->name = ucfirst($title->name);
                    return $title;
                }),
        ]);
    }
}
