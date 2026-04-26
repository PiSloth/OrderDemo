<?php

namespace App\Events;

use App\Models\NoteMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public NoteMessage $noteMessage)
    {
        $this->noteMessage->loadMissing(['user', 'dailyNote.title']);
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('note.' . $this->noteMessage->note_id);
    }

    public function broadcastAs(): string
    {
        return 'NoteMessageSent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->noteMessage->id,
            'note_id' => $this->noteMessage->note_id,
            'message' => $this->noteMessage->message,
            'image' => $this->noteMessage->image_url,
            'user_id' => $this->noteMessage->user_id,
            'user_name' => $this->noteMessage->user?->name,
            'timestamp' => $this->noteMessage->created_at?->toISOString(),
            'time_label' => $this->noteMessage->created_at?->format('H:i'),
        ];
    }
}
