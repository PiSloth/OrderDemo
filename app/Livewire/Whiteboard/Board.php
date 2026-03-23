<?php

namespace App\Livewire\Whiteboard;

use App\Models\EmailList;
use App\Models\WhiteboardContent;
use App\Models\WhiteboardContentType;
use App\Models\WhiteboardDecision;
use App\Models\WhiteboardFlag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.whiteboard')]
#[Title('Whiteboard Board')]
class Board extends Component
{
    public string $editingContentId = '';
    public string $title = '';
    public string $description = '';
    public string $propose_solution = '';
    public string $report_by = '';
    public string $content_type_id = '';
    public string $propose_decision_due_at = '';
    public string $flag_id = '';
    public string $received_mail_at = '';
    public array $recipient_ids = [];

    #[Url(as: 'item')]
    public string $selectedContentId = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $contentTypeFilter = 'all';

    #[Url(as: 'flag')]
    public string $flagFilter = 'all';
    #[Url(as: 'archive')]
    public string $archiveFilter = 'active';
    #[Url(as: 'received')]
    public string $receivedMailDateFilter = '';

    #[Url(as: 'sort')]
    public string $sortBy = 'unread_first';

    public bool $unreadOnly = false;

    public string $savedSortBy = '';

    public string $decision = '';
    public string $appointment_at = '';
    public string $invited_person = '';

    public function mount(): void
    {
        $this->report_by = $this->defaultReporterId();

        $this->syncSelection();
    }

    public function startCreatingContent(): void
    {
        $this->resetComposeForm();
        $this->dispatch('whiteboard-propose-solution-reset');
        $this->dispatch('whiteboard-compose-open');
    }

    public function startEditingSelectedContent(): void
    {
        $content = $this->selectedContentModel();

        if (! $content || ! $this->ownsContent($content)) {
            session()->flash('success', 'Only the creator can edit this content.');

            return;
        }

        $this->editingContentId = (string) $content->id;
        $this->title = $content->title;
        $this->description = $content->description;
        $this->propose_solution = $content->propose_solution ?? '';
        $this->report_by = (string) ($content->report_by ?? '');
        $this->content_type_id = (string) $content->content_type_id;
        $this->propose_decision_due_at = $content->propose_decision_due_at?->format('Y-m-d\TH:i') ?? '';
        $this->flag_id = (string) ($content->flag_id ?? '');
        $this->received_mail_at = $content->received_mail_at?->format('Y-m-d\TH:i') ?? '';
        $this->recipient_ids = $content->reports()
            ->pluck('email_list_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $this->dispatch('whiteboard-propose-solution-fill', html: $this->propose_solution);
        $this->dispatch('whiteboard-compose-open');
    }

    public function saveContent(): void
    {
        $validated = $this->validateContent();

        if ($this->editingContentId !== '') {
            $content = WhiteboardContent::query()->find($this->editingContentId);

            if (! $content || ! $this->ownsContent($content)) {
                session()->flash('success', 'Only the creator can edit this content.');

                return;
            }

            $content->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'propose_solution' => $validated['propose_solution'] ?: null,
                'report_by' => $validated['report_by'] ?: null,
                'content_type_id' => $validated['content_type_id'],
                'propose_decision_due_at' => $validated['propose_decision_due_at'] ?: null,
                'flag_id' => $validated['flag_id'] ?: null,
                'received_mail_at' => $validated['received_mail_at'] ?? null,
            ]);

            $this->syncRecipients($content, $validated['recipient_ids']);
            $message = 'Whiteboard content updated.';
        } else {
            $content = WhiteboardContent::query()->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'propose_solution' => $validated['propose_solution'] ?: null,
                'report_by' => $validated['report_by'] ?: null,
                'created_by' => Auth::id(),
                'content_type_id' => $validated['content_type_id'],
                'propose_decision_due_at' => $validated['propose_decision_due_at'] ?: null,
                'flag_id' => $validated['flag_id'] ?: null,
                'received_mail_at' => $validated['received_mail_at'] ?? null,
            ]);

            $this->syncRecipients($content, $validated['recipient_ids']);
            $message = 'Whiteboard content posted.';
        }

        $this->selectedContentId = (string) $content->id;

        $this->resetComposeForm();

        $this->dispatch('whiteboard-propose-solution-reset');
        $this->dispatch('whiteboard-compose-close');
        $this->resetDecisionForm();
        $this->syncSelection();

        session()->flash('success', $message);
    }

    public function archiveSelectedContent(): void
    {
        $content = $this->selectedContentModel();

        if (! $content || ! $this->ownsContent($content)) {
            session()->flash('success', 'Only the creator can archive this content.');

            return;
        }

        $content->delete();
        $this->resetComposeForm();
        $this->dispatch('whiteboard-propose-solution-reset');
        $this->syncSelection();

        session()->flash('success', 'Whiteboard content archived.');
    }

    public function updatedSearch(): void
    {
        $this->syncSelection();
    }

    public function updatedContentTypeFilter(): void
    {
        $this->syncSelection();
    }

    public function updatedFlagFilter(): void
    {
        $this->syncSelection();
    }
    public function updatedArchiveFilter(): void
    {
        if (! in_array($this->archiveFilter, $this->availableArchiveFilters(), true)) {
            $this->archiveFilter = 'active';
        }

        $this->syncSelection();
    }

    public function updatedReceivedMailDateFilter(): void
    {
        $this->receivedMailDateFilter = trim($this->receivedMailDateFilter);

        $this->syncSelection();
    }

    public function updatedSortBy(): void
    {
        $this->syncSelection();
    }

    public function toggleSort(string $sortKey): void
    {
        if (! in_array($sortKey, $this->availableSortKeys(), true)) {
            return;
        }

        $sorts = $this->activeSorts();

        if (in_array($sortKey, $sorts, true)) {
            $sorts = array_values(array_filter($sorts, fn(string $key) => $key !== $sortKey));
        } else {
            $sorts[] = $sortKey;
        }

        $this->sortBy = implode(',', $sorts);
        $this->syncSelection();
    }

    public function clearSort(string $sortKey): void
    {
        $sorts = array_values(array_filter($this->activeSorts(), fn(string $key) => $key !== $sortKey));

        $this->sortBy = implode(',', $sorts);
        $this->syncSelection();
    }

    public function clearAllSorts(): void
    {
        $this->sortBy = '';
        $this->syncSelection();
    }

    public function toggleUnreadFocus(): void
    {
        if ($this->unreadOnly) {
            $this->unreadOnly = false;
            $this->sortBy = $this->savedSortBy;
            $this->savedSortBy = '';
            $this->syncSelection();

            return;
        }

        $this->savedSortBy = $this->sortBy;
        $this->unreadOnly = true;
        $this->sortBy = 'decision_required,unread_first,newest';
        $this->syncSelection();
    }

    public function selectContent(int $contentId): void
    {
        $this->selectedContentId = (string) $contentId;

        if ($content = $this->selectedContentModel()) {
            $content->markReadFor(Auth::user());
        }

        $this->resetDecisionForm();
        $this->dispatch('whiteboard-decision-reset');
        $this->dispatch('whiteboard-content-selected');
        $this->syncSelection();
    }

    public function updateSelectedFlag(string $flagId = ''): void
    {
        $content = $this->selectedContentModel();

        if (! $content) {
            return;
        }

        $validated = Validator::make([
            'flag_id' => $flagId,
        ], [
            'flag_id' => ['nullable', 'integer', 'exists:whiteboard_flags,id'],
        ])->validate();

        $content->update([
            'flag_id' => ($validated['flag_id'] ?? '') !== '' ? (int) $validated['flag_id'] : null,
        ]);

        $this->syncSelection();

        session()->flash('success', 'Flag updated.');
    }

    public function submitDecision(): void
    {
        $content = $this->selectedContentModel();

        if (! $content) {
            session()->flash('success', 'No whiteboard item is selected.');

            return;
        }

        $validated = Validator::make([
            'decision' => $this->decision,
            'appointment_at' => $this->appointment_at,
            'invited_person' => $this->invited_person,
        ], [
            'decision' => ['required', 'string'],
            'received_mail_at' => ['required'],
            'appointment_at' => ['nullable', 'date'],
            'invited_person' => ['nullable', 'string', 'max:255'],
        ])->after(function ($validator) {
            if ($this->richTextLooksEmpty($this->decision)) {
                $validator->errors()->add('decision', 'The decision field is required.');
            }
        })->validate();

        WhiteboardDecision::query()->create([
            'content_id' => $content->id,
            'created_by' => Auth::id(),
            'decision' => $validated['decision'],
            'appointment_at' => $validated['appointment_at'] ?: null,
            'invited_person' => $validated['invited_person'] ?: null,
        ]);

        $content->markReadFor(Auth::user());
        $this->resetDecisionForm();
        $this->dispatch('whiteboard-decision-reset');
        $this->syncSelection();

        session()->flash('success', 'Decision saved.');
    }

    public function isContentRead(WhiteboardContent $content): bool
    {
        $user = Auth::user();

        return $content->reports->contains(function ($report) use ($user) {
            $matchesUser = $report->emailList?->email === $user?->email;
            $matchesDepartment = $user?->department_id
                && $report->emailList?->department_id === $user->department_id;

            return ($matchesUser || $matchesDepartment) && $report->is_read;
        });
    }

    public function selectedContentTypeRequiresDecision(): bool
    {
        if ($this->content_type_id === '') {
            return false;
        }

        return (bool) WhiteboardContentType::query()
            ->whereKey($this->content_type_id)
            ->value('requires_decision');
    }

    public function selectedContentRequiresDecision(): bool
    {
        return (bool) $this->selectedContentModel()?->requiresDecision();
    }

    public function canManageSelectedContent(): bool
    {
        $content = $this->selectedContentModel();

        return $content ? $this->ownsContent($content) : false;
    }

    public function restoreSelectedContent(): void
    {
        $content = $this->selectedContentModel();

        if (! $content || ! $content->trashed() || ! $this->ownsContent($content)) {
            session()->flash('success', 'Only the creator can unarchive this content.');

            return;
        }

        $content->restore();

        if ($this->archiveFilter === 'archived') {
            $this->selectedContentId = '';
        }

        $this->syncSelection();

        session()->flash('success', 'Whiteboard content restored.');
    }

    public function archiveFilterOptions(): array
    {
        return [
            'active' => 'Active Content',
            'archived' => 'Archived Content',
        ];
    }

    public function activeSorts(): array
    {
        $allowed = $this->availableSortKeys();

        return array_values(array_filter(
            array_unique(array_filter(array_map('trim', explode(',', $this->sortBy)))),
            fn(string $key) => in_array($key, $allowed, true),
        ));
    }

    public function sortLabel(string $sortKey): string
    {
        return $this->sortOptions()[$sortKey] ?? Str::headline(str_replace('_', ' ', $sortKey));
    }

    public function sortOptions(): array
    {
        return [
            'decision_required' => 'Decision Required',
            'newest' => 'Newest',
            'oldest' => 'Oldest',
            'decision_due' => 'Decision Due Date',
            'flag_priority' => 'Flag Priority',
            'unread_first' => 'Unread First',
        ];
    }

    private function syncSelection(): void
    {
        $contents = $this->filteredBoardContents();
        $selectedContent = $this->resolveSelectedContent($contents);
        $selectedContentId = $selectedContent ? (string) $selectedContent->id : '';

        if ($this->selectedContentId !== $selectedContentId) {
            $this->selectedContentId = $selectedContentId;
            $this->resetDecisionForm();
            $this->dispatch('whiteboard-decision-reset');
        }
    }

    private function filteredBoardQuery(): Builder
    {
        $user = Auth::user();

        $query = WhiteboardContent::query();

        if ($this->archiveFilter === 'archived') {
            $query->onlyTrashed();
        }

        return $query->boardFeed($user)
            ->when(trim($this->search) !== '', function (Builder $query) {
                $search = trim($this->search);

                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('propose_solution', 'like', "%{$search}%");
                });
            })
            ->when($this->contentTypeFilter !== 'all', function (Builder $query) {
                $query->where('content_type_id', $this->contentTypeFilter);
            })
            ->when($this->flagFilter !== 'all', function (Builder $query) {
                $query->where('flag_id', $this->flagFilter);
            })
            ->when(trim($this->receivedMailDateFilter) !== '', function (Builder $query) {
                $query->whereDate('received_mail_at', $this->receivedMailDateFilter);
            });
    }

    private function filteredBoardContents(): Collection
    {
        $contents = $this->filteredBoardQuery()->get();

        if ($this->unreadOnly) {
            $contents = $contents->filter(fn(WhiteboardContent $content) => ! $this->isContentRead($content))->values();
        }

        $activeSorts = $this->activeSorts();

        if ($activeSorts === []) {
            return $contents->values();
        }

        return $contents->sort(function (WhiteboardContent $left, WhiteboardContent $right) use ($activeSorts) {
            foreach ($activeSorts as $sortKey) {
                $comparison = match ($sortKey) {
                    'decision_required' => $this->compareDecisionRequired($left, $right),
                    'oldest' => $this->compareOldest($left, $right),
                    'decision_due' => $this->compareDecisionDue($left, $right),
                    'flag_priority' => $this->compareFlagPriority($left, $right),
                    'unread_first' => $this->compareUnreadFirst($left, $right),
                    default => $this->compareNewest($left, $right),
                };

                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return $this->compareNewest($left, $right);
        })->values();
    }

    private function availableSortKeys(): array
    {
        return array_keys($this->sortOptions());
    }

    private function availableArchiveFilters(): array
    {
        return array_keys($this->archiveFilterOptions());
    }

    private function selectedContentModel(): ?WhiteboardContent
    {
        return $this->resolveSelectedContent($this->filteredBoardContents());
    }

    private function resolveSelectedContent(Collection $contents): ?WhiteboardContent
    {
        if ($contents->isEmpty()) {
            return null;
        }

        if ($this->selectedContentId !== '') {
            $selected = $contents->firstWhere('id', (int) $this->selectedContentId);

            if ($selected) {
                return $selected;
            }
        }

        return $contents->first();
    }

    private function compareNewest(WhiteboardContent $left, WhiteboardContent $right): int
    {
        return $this->compareWithNewestTieBreaker(
            ($right->received_mail_at?->timestamp ?? 0) <=> ($left->received_mail_at?->timestamp ?? 0),
            $left,
            $right,
        );
    }

    private function compareOldest(WhiteboardContent $left, WhiteboardContent $right): int
    {
        return $this->compareWithNewestTieBreaker(
            ($left->received_mail_at?->timestamp ?? 0) <=> ($right->received_mail_at?->timestamp ?? 0),
            $left,
            $right,
        );
    }

    private function compareDecisionDue(WhiteboardContent $left, WhiteboardContent $right): int
    {
        $leftTimestamp = $left->propose_decision_due_at?->timestamp;
        $rightTimestamp = $right->propose_decision_due_at?->timestamp;

        if ($leftTimestamp === null && $rightTimestamp !== null) {
            return 1;
        }

        if ($leftTimestamp !== null && $rightTimestamp === null) {
            return -1;
        }

        return $this->compareWithNewestTieBreaker(
            ($leftTimestamp ?? 0) <=> ($rightTimestamp ?? 0),
            $left,
            $right,
        );
    }

    private function compareDecisionRequired(WhiteboardContent $left, WhiteboardContent $right): int
    {
        return $this->compareWithNewestTieBreaker(
            $this->decisionRequiredRank($left) <=> $this->decisionRequiredRank($right),
            $left,
            $right,
        );
    }

    private function compareFlagPriority(WhiteboardContent $left, WhiteboardContent $right): int
    {
        return $this->compareWithNewestTieBreaker(
            $this->flagPriority($left) <=> $this->flagPriority($right),
            $left,
            $right,
        );
    }

    private function compareUnreadFirst(WhiteboardContent $left, WhiteboardContent $right): int
    {
        return $this->compareWithNewestTieBreaker(
            $this->unreadRank($left) <=> $this->unreadRank($right),
            $left,
            $right,
        );
    }

    private function compareWithNewestTieBreaker(int $comparison, WhiteboardContent $left, WhiteboardContent $right): int
    {
        if ($comparison !== 0) {
            return $comparison;
        }

        return ($right->received_mail_at?->timestamp ?? 0) <=> ($left->received_mail_at?->timestamp ?? 0);
    }

    private function flagPriority(WhiteboardContent $content): int
    {
        $flagName = Str::lower($content->flag?->name ?? '');

        return match (true) {
            str_contains($flagName, 'urgent') => 0,
            str_contains($flagName, 'decision') => 1,
            str_contains($flagName, 'fyi') => 2,
            $flagName !== '' => 3,
            default => 4,
        };
    }

    private function unreadRank(WhiteboardContent $content): int
    {
        return $this->isContentRead($content) ? 1 : 0;
    }

    private function decisionRequiredRank(WhiteboardContent $content): int
    {
        if ($content->requiresDecision() && ! $content->latestDecision) {
            return 0;
        }

        if ($content->requiresDecision()) {
            return 1;
        }

        return 2;
    }

    private function resetDecisionForm(): void
    {
        $this->reset(['decision', 'appointment_at', 'invited_person']);
    }

    private function validateContent(): array
    {
        return $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'propose_solution' => ['nullable', 'string'],
            'report_by' => ['nullable', 'integer', 'exists:email_lists,id'],
            'content_type_id' => ['required', 'integer', 'exists:whiteboard_content_types,id'],
            'propose_decision_due_at' => [
                Rule::requiredIf(fn() => $this->selectedContentTypeRequiresDecision()),
                'nullable',
                'date',
            ],
            'flag_id' => ['nullable', 'integer', 'exists:whiteboard_flags,id'],
            'received_mail_at' => ['nullable', 'date'],
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['integer', 'exists:email_lists,id'],
        ]);
    }

    private function syncRecipients(WhiteboardContent $content, array $recipientIds): void
    {
        $recipientIds = collect($recipientIds)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $content->reports()
            ->whereNotIn('email_list_id', $recipientIds->all())
            ->delete();

        $existingIds = $content->reports()
            ->pluck('email_list_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $content->reports()->createMany(
            $recipientIds
                ->reject(fn(int $emailListId) => in_array($emailListId, $existingIds, true))
                ->map(fn(int $emailListId) => ['email_list_id' => $emailListId])
                ->all()
        );
    }

    private function resetComposeForm(): void
    {
        $this->reset([
            'editingContentId',
            'title',
            'description',
            'propose_solution',
            'content_type_id',
            'propose_decision_due_at',
            'flag_id',
            'received_mail_at',
            'recipient_ids',
        ]);

        $this->report_by = $this->defaultReporterId();
    }

    private function defaultReporterId(): string
    {
        $user = Auth::user();

        return (string) EmailList::query()
            ->where('email', $user?->email)
            ->value('id');
    }

    private function ownsContent(WhiteboardContent $content): bool
    {
        return (int) $content->created_by === (int) Auth::id();
    }

    private function richTextLooksEmpty(?string $html): bool
    {
        $text = trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5));

        return $text === '';
    }

    public function render()
    {
        $boardContents = $this->filteredBoardContents();
        $selectedContent = $this->resolveSelectedContent($boardContents);

        return view('livewire.whiteboard.board', [
            'boardContents' => $boardContents,
            'selectedContent' => $selectedContent,
            'contentTypes' => WhiteboardContentType::query()->orderBy('name')->get(),
            'flags' => WhiteboardFlag::query()->orderBy('name')->get(),
            'emailLists' => EmailList::query()->with('department')->orderBy('user_name')->get(),
            'unreadCount' => $boardContents->filter(fn(WhiteboardContent $content) => ! $this->isContentRead($content))->count(),
        ]);
    }
}
