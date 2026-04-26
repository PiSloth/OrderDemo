<?php

namespace App\Livewire\Operation;

use App\Models\DailyNoteAcknowledgement;
use App\Models\DailyNote;
use App\Models\NoteTitle;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use WireUi\Traits\Actions;

#[Layout('components.layouts.operation')]
#[Title('Daily Operation Notes')]
class DailyNotesList extends Component
{
    use Actions;
    public string $activeTab = 'opened';
    public string $viewMode = 'card';
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

    #[On('note-read-updated')]
    public function refreshReadState(): void
    {
        // Event hook to trigger component re-render when chat read status changes.
    }

    public function openTitle(int $titleId): void
    {
        $title = NoteTitle::query()->where('is_active', true)->findOrFail($titleId);
        $note = $this->getOrCreateDailyNote($title);

        // dd($note->toArray());

        $this->activeTitleId = $title->id;
        $this->activeNoteId = $note->id;
        $this->note = (string) ($note->note ?? '');
        $this->quickInputMode = null;
        $this->quickNumber = '';
        $this->quickDateTime = '';
        $this->is_number = (bool) $note->is_number;
        $this->created_date = $note->created_at;
        $this->updated_date = $note->updated_at;
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

    public function openMessageModal(int $titleId): void
    {
        $title = NoteTitle::query()->findOrFail($titleId);
        $note = $this->getOrCreateDailyNote($title);

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
        $this->persistNote();
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
        $this->persistNote();

        $nextTitle = $this->openedTitleCards()
            ->pluck('title')
            ->first(fn(NoteTitle $title) => $title->id !== $currentTitleId);

        if ($nextTitle) {
            $this->openTitle($nextTitle->id);
            return;
        }

        $this->notification([
            'title' => 'Success',
            'description' => 'Daily note saved. No more open titles.',
            'icon' => 'success',
        ]);
        $this->closeModal();
    }

    public function markFinished(): void
    {
        $note = $this->currentNote();

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

    protected function persistNote(): void
    {
        $this->validate([
            'note' => ['nullable', 'string', 'max:255'],
            'is_number' => ['boolean'],
        ]);

        $note = $this->currentNote();

        $note->update([
            'note' => $this->note !== '' ? $this->note : null,
            'is_number' => $this->is_number,
        ]);
    }

    protected function currentNote(): DailyNote
    {
        return DailyNote::query()
            ->forUser(Auth::user())
            ->with(['title', 'location', 'department', 'branch'])
            ->findOrFail($this->activeNoteId);
    }

    protected function getOrCreateDailyNote(NoteTitle $title): DailyNote
    {
        $user = Auth::user();

        return DailyNote::query()->firstOrCreate(
            [
                'title_id' => $title->id,
                'location_id' => $user->location_id,
                'department_id' => $user->department_id,
                'branch_id' => $user->branch_id,
                'date' => $this->effectiveDate(),
            ],
            [
                'created_by' => $user->id,
            ],
        );
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

        return DailyNote::query()
            ->forUser(Auth::user())
            ->forDate($this->effectiveDate())
            ->with(['title', 'location', 'department', 'branch', 'creator', 'messages.user', 'acknowledgements.user'])
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
            ->orderBy('name')
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

    public function render()
    {
        $userId = (int) Auth::id();
        $openedCards = $this->openedTitleCards();
        $finishedNotes = $this->finishedNotes();
        $recentNotes = $this->recentNotes();
        $tableGroups = collect();
        $activeNote = $this->activeNoteId ? $this->currentNote() : null;
        $user = Auth::user();

        if ($this->activeTab === 'opened') {
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
            'openedBadgeCount' => $openedCards->where('has_no_messages', true)->count(),
            'finishedNotes' => $finishedNotes,
            'recentNotes' => $recentNotes,
            'tableGroups' => $tableGroups,
            'showRecentTab' => $recentNotes->isNotEmpty(),
            'activeNote' => $activeNote,
            'userLocationName' => $user->location?->name ?? 'Unknown location',
            'todayLabel' => Carbon::parse($this->effectiveDate())->format('Y-m-d'),
            'isSelectedToday' => Carbon::parse($this->effectiveDate())->isToday(),
        ]);
    }
}
