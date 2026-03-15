<?php

namespace App\Livewire\Whiteboard;

use App\Models\WhiteboardContent;
use App\Models\WhiteboardDecision;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.whiteboard')]
#[Title('Whiteboard Detail')]
class Show extends Component
{
    public WhiteboardContent $content;

    public string $decision = '';
    public string $appointment_at = '';
    public string $invited_person = '';

    public function mount(WhiteboardContent $content): void
    {
        $this->content = WhiteboardContent::query()
            ->boardFeed(Auth::user())
            ->findOrFail($content->id);
    }

    public function markAsRead(): void
    {
        $this->content->markReadFor(Auth::user());
        $this->refreshContent();

        session()->flash('success', 'Marked as read.');
    }

    public function submitDecision(): void
    {
        $validated = Validator::make([
            'decision' => $this->decision,
            'appointment_at' => $this->appointment_at,
            'invited_person' => $this->invited_person,
        ], [
            'decision' => ['required', 'string'],
            'appointment_at' => ['nullable', 'date'],
            'invited_person' => ['nullable', 'string', 'max:255'],
        ])->after(function ($validator) {
            if ($this->richTextLooksEmpty($this->decision)) {
                $validator->errors()->add('decision', 'The decision field is required.');
            }
        })->validate();

        WhiteboardDecision::query()->create([
            'content_id' => $this->content->id,
            'created_by' => Auth::id(),
            'decision' => $validated['decision'],
            'appointment_at' => $validated['appointment_at'] ?: null,
            'invited_person' => $validated['invited_person'] ?: null,
        ]);

        $this->content->markReadFor(Auth::user());

        $this->reset(['decision', 'appointment_at', 'invited_person']);
        $this->dispatch('whiteboard-decision-reset');
        $this->refreshContent();

        session()->flash('success', 'Decision saved.');
    }

    public function isContentRead(): bool
    {
        $user = Auth::user();

        return $this->content->reports->contains(function ($report) use ($user) {
            $matchesUser = $report->emailList?->email === $user?->email;
            $matchesDepartment = $user?->department_id
                && $report->emailList?->department_id === $user->department_id;

            return ($matchesUser || $matchesDepartment) && $report->is_read;
        });
    }

    private function refreshContent(): void
    {
        $this->content = WhiteboardContent::query()
            ->boardFeed(Auth::user())
            ->findOrFail($this->content->id);
    }

    private function richTextLooksEmpty(?string $html): bool
    {
        $text = trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5));

        return $text === '';
    }

    public function render()
    {
        return view('livewire.whiteboard.show');
    }
}
