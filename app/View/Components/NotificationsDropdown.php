<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsDropdown extends Component
{
    /**
     * The notifications.
     */
    public $notifications;
    
    /**
     * The unread count.
     */
    public $unreadCount;
    
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->notifications = Auth::user()?->notifications()->latest()->limit(5)->get() ?? collect();
        $this->unreadCount = Auth::user()?->unreadNotifications()->count() ?? 0;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.notifications-dropdown');
    }
    
    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $notification = DatabaseNotification::find($id);
        if ($notification && $notification->notifiable_id === Auth::id()) {
            $notification->markAsRead();
        }
    }
    
    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        if (Auth::check()) {
            Auth::user()->unreadNotifications->markAsRead();
        }
    }
}
