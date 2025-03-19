<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('backpack.auth');
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Auth::user()->notifications();
        
        // Filter by read status
        if ($request->has('status')) {
            if ($request->status === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->status === 'read') {
                $query->whereNotNull('read_at');
            }
        }
        
        // Apply order
        $query->orderBy('created_at', 'desc');
        
        $notifications = $query->paginate(15)->withQueryString();
        
        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'title' => 'Notifikace',
            'status' => $request->status,
            'breadcrumbs' => [
                trans('backpack::crud.admin') => backpack_url('dashboard'),
                'Notifikace' => false,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $notification = DatabaseNotification::find($id);
        
        if (!$notification || $notification->notifiable_id !== Auth::id()) {
            abort(404);
        }
        
        // Mark as read if it's unread
        if ($notification->read_at === null) {
            $notification->markAsRead();
        }
        
        return view('admin.notifications.show', [
            'notification' => $notification,
            'title' => 'Detail notifikace',
            'breadcrumbs' => [
                trans('backpack::crud.admin') => backpack_url('dashboard'),
                'Notifikace' => backpack_url('notification'),
                'Detail' => false,
            ],
        ]);
    }
    
    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $id)
    {
        $notification = DatabaseNotification::find($id);
        
        if ($notification && $notification->notifiable_id === Auth::id()) {
            $notification->markAsRead();
            
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false], 404);
    }
    
    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $notification = DatabaseNotification::find($id);
        
        if ($notification && $notification->notifiable_id === Auth::id()) {
            $notification->delete();
            
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false], 404);
    }
}
