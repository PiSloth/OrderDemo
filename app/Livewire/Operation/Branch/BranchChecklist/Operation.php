<?php

namespace App\Livewire\Operation\Branch\BranchChecklist;

use App\Models\BranchChecklistHistory;
use App\Services\ChecklistService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use WireUi\Traits\Actions;

#[Layout('components.layouts.operation')]
#[Title('Branch Checklist Operation')]
class Operation extends Component
{
    use Actions;

    public bool $showModal = false;
    public ?int $activeHistoryId = null;
    public string $remark = '';

    public function generate(ChecklistService $service): void
    {
        $count = $service->generateForToday(Auth::user());

        if ($count === 0) {
            $this->notification([
                'title' => 'Already generated',
                'description' => 'Checklist was already generated today or no active checklist found for your branch.',
                'icon' => 'warning',
            ]);

            return;
        }

        $this->notification([
            'title' => 'Checklist generated',
            'description' => "{$count} checklist item(s) generated for today.",
            'icon' => 'success',
        ]);
    }

    public function openCard(int $historyId): void
    {
        $history = BranchChecklistHistory::query()
            ->where('id', $historyId)
            ->where('user_id', Auth::id())
            ->where('is_done', false)
            ->firstOrFail();

        $this->activeHistoryId = $history->id;
        $this->remark = (string) ($history->remark ?? '');
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->reset('showModal', 'activeHistoryId', 'remark');
    }

    public function markDone(ChecklistService $service): void
    {
        $this->submitStatus(true, $service);
    }

    public function markNotDone(ChecklistService $service): void
    {
        $this->submitStatus(false, $service);
    }

    protected function submitStatus(bool $isDone, ChecklistService $service): void
    {
        if (!$this->activeHistoryId) {
            return;
        }

        $history = BranchChecklistHistory::query()
            ->where('id', $this->activeHistoryId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $service->mark($history, $isDone, trim($this->remark) !== '' ? trim($this->remark) : null);

        $this->closeModal();

        $this->notification([
            'title' => 'Saved',
            'description' => $isDone ? 'Marked as done.' : 'Marked as not done and hidden from list.',
            'icon' => 'success',
        ]);
    }

    public function getItemsProperty()
    {
        return app(ChecklistService::class)->pendingForToday(Auth::user());
    }

    public function render()
    {
        return view('livewire.operation.branch.branch-checklist.operation', [
            'items' => $this->items,
        ]);
    }
}
