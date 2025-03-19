<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsDropdown extends Component
{
    public $notificationCount = 0;
    
    public function mount()
    {
        $this->updateNotificationCount();
    }
    
    public function render()
    {
        $notifications = Auth::user()?->notifications()->latest()->limit(5)->get() ?? collect();
        $unreadCount = Auth::user()?->unreadNotifications()->count() ?? 0;
        
        return view('livewire.notifications-dropdown', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }
    
    public function updateNotificationCount()
    {
        $this->notificationCount = Auth::user()?->unreadNotifications()->count() ?? 0;
    }
    
    public function markAsRead($id)
    {
        $notification = DatabaseNotification::find($id);
        if ($notification && $notification->notifiable_id === Auth::id()) {
            $notification->markAsRead();
            $this->updateNotificationCount();
        }
    }
    
    public function markAllAsRead()
    {
        if (Auth::check()) {
            Auth::user()->unreadNotifications->markAsRead();
            $this->updateNotificationCount();
        }
    }
    
    /**
     * Poll for new notifications
     */
    public function pollNotifications()
    {
        $this->updateNotificationCount();
    }
}
