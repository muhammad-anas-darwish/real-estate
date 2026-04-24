<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    /**
     * Get paginated notifications list.
     *
     * Returns notifications ordered by unread first, then by creation date.
     *
     * @response 200 {"success": true, "data": [...], "pagination": {...}}
     */
    public function index()
    {
        $notifications = Auth::user()
            ->notifications()
            ->orderByDesc('created_at')
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
            ->paginate(15);

        return $this->paginatedResponse(
            NotificationResource::collection($notifications)
        );
    }

    public function unreadCount()
    {
        $count = Auth::user()
            ->unreadNotifications()
            ->count();

        return $this->successResponse(['unread_count' => $count]);
    }

    public function markAsRead(string $id)
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return $this->successResponse([], __('messages.notification_marked_read'));
    }

    public function markAllAsRead()
    {
        Auth::user()
            ->unreadNotifications()
            ->markAsRead();

        return $this->successResponse([], __('messages.all_notifications_marked_read'));
    }

    public function destroy(string $id)
    {
        $notification = Auth::user()
            ->notifications()
            ->findOrFail($id);

        $notification->delete();

        return $this->successResponse()->deleted('notification');
    }
}
