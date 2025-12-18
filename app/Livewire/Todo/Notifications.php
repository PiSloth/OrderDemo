<?php

namespace App\Livewire\Todo;

use App\Models\TaskNotification;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.todo')]
class Notifications extends Component
{
    public $notifications;
    public $unreadCount = 0;

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $this->notifications = TaskNotification::forUser(Auth::id())
            ->with(['taskComment.user', 'todoList'])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->unreadCount = TaskNotification::forUser(Auth::id())
            ->unread()
            ->count();
    }

    public function markAsRead($notificationId)
    {
        $notification = TaskNotification::find($notificationId);
        if ($notification && $notification->user_id === Auth::id()) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAllAsRead()
    {
        TaskNotification::forUser(Auth::id())
            ->unread()
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);

        $this->loadNotifications();
    }

    public function deleteNotification($notificationId)
    {
        $notification = TaskNotification::find($notificationId);
        if ($notification && $notification->user_id === Auth::id()) {
            $notification->delete();
            $this->loadNotifications();
        }
    }

    public function render()
    {
        return view('livewire.todo.notifications');
    }
}
