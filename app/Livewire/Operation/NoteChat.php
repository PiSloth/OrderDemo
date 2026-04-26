<?php

namespace App\Livewire\Operation;

use App\Events\NoteMessageSent;
use App\Models\DailyNote;
use App\Models\NoteMessage;
use App\Models\NoteMessageRead;
use App\Services\Operation\OperationNoteImageResizer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class NoteChat extends Component
{
    use WithFileUploads;

    public int $noteId;
    public string $message = '';
    public $cameraPhoto = null;
    public array $galleryPhotos = [];
    public array $queuedImages = [];
    public array $messages = [];

    public function mount(int $noteId): void
    {
        $this->noteId = $noteId;
        $this->loadMessages();
    }

    public function updatedCameraPhoto(): void
    {
        $this->validate([
            'cameraPhoto' => ['nullable', 'image', 'max:10240'],
        ]);

        if ($this->cameraPhoto instanceof TemporaryUploadedFile) {
            $this->queuedImages[] = $this->cameraPhoto;
        }

        $this->cameraPhoto = null;
    }

    public function updatedGalleryPhotos(): void
    {
        $this->validate([
            'galleryPhotos' => ['array', 'max:5'],
            'galleryPhotos.*' => ['image', 'max:10240'],
        ]);

        foreach ($this->galleryPhotos as $photo) {
            if ($photo instanceof TemporaryUploadedFile) {
                $this->queuedImages[] = $photo;
            }
        }

        $this->galleryPhotos = [];
    }

    public function removeQueuedImage(int $index): void
    {
        if (!array_key_exists($index, $this->queuedImages)) {
            return;
        }

        unset($this->queuedImages[$index]);
        $this->queuedImages = array_values($this->queuedImages);
    }

    public function sendMessage(OperationNoteImageResizer $resizer): void
    {
        $this->validate([
            'message' => ['nullable', 'string'],
            'queuedImages' => ['array', 'max:5'],
            'queuedImages.*' => ['image', 'max:10240'],
        ]);

        $message = trim($this->message);

        if ($message === '' && $this->queuedImages === []) {
            $this->addError('message', 'Message or image is required.');
            return;
        }

        $note = $this->currentNote();

        $storedPaths = [];
        $createdMessages = [];

        try {
            if ($this->queuedImages === []) {
                $createdMessages[] = NoteMessage::create([
                    'note_id' => $note->id,
                    'user_id' => Auth::id(),
                    'message' => $message !== '' ? $message : null,
                ]);
            } else {
                foreach ($this->queuedImages as $index => $image) {
                    $path = $resizer->store($image, 600);
                    $storedPaths[] = $path;

                    $createdMessages[] = NoteMessage::create([
                        'note_id' => $note->id,
                        'user_id' => Auth::id(),
                        'message' => $index === 0 && $message !== '' ? $message : null,
                        'image_path' => $path,
                    ]);
                }
            }
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        $note->touch();

        foreach ($createdMessages as $createdMessage) {
            broadcast(new NoteMessageSent($createdMessage))->toOthers();
        }

        $this->message = '';
        $this->queuedImages = [];
        $this->loadMessages();
        $this->dispatch('note-chat-scroll');
    }

    public function handleBroadcast(array $payload): void
    {
        if ((int) ($payload['note_id'] ?? 0) !== $this->noteId) {
            return;
        }

        $this->loadMessages();
    }

    #[On('refresh-note-chat')]
    public function loadMessages(): void
    {
        $newlyReadCount = $this->markUnreadMessagesAsRead();

        $this->messages = $this->currentNote()
            ->messages()
            ->with(['user', 'readers'])
            ->oldest()
            ->get()
            ->map(function (NoteMessage $message) {
                $seenBy = $message->readers
                    ->where('id', '!=', $message->user_id)
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => $message->id,
                    'user_id' => $message->user_id,
                    'user_name' => $message->user?->name,
                    'message' => $message->message,
                    'image' => $message->image_url,
                    'timestamp' => $message->created_at?->toISOString(),
                    'time_label' => $message->created_at?->format('H:i'),
                    'is_mine' => $message->user_id === Auth::id(),
                    'seen_by' => $seenBy,
                ];
            })
            ->all();

        if ($newlyReadCount > 0) {
            $this->dispatch('note-read-updated');
        }

        $this->dispatch('note-chat-scroll');
    }

    protected function currentNote(): DailyNote
    {
        return DailyNote::query()
            ->forUser(Auth::user())
            ->with(['title', 'location', 'department', 'branch'])
            ->findOrFail($this->noteId);
    }

    protected function markUnreadMessagesAsRead(): int
    {
        $userId = (int) Auth::id();
        $now = now();

        $messageIds = NoteMessage::query()
            ->where('note_id', $this->noteId)
            ->where('user_id', '!=', $userId)
            ->whereDoesntHave('readReceipts', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->pluck('id');

        if ($messageIds->isEmpty()) {
            return 0;
        }

        NoteMessageRead::query()->insert(
            $messageIds->map(function ($messageId) use ($userId, $now) {
                return [
                    'note_message_id' => $messageId,
                    'user_id' => $userId,
                    'read_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all()
        );

        return $messageIds->count();
    }

    public function render()
    {
        return view('livewire.operation.note-chat', [
            'dailyNote' => $this->currentNote(),
        ]);
    }
}
