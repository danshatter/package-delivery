<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Bus;
use App\Models\SuperNotification;
use App\Jobs\MarkAsDelivered;

class SuperNotificationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get all notifications
     */
    public function index()
    {
        $perPage = request()->query('per_page') ?? config('handova.per_page');

        $notifications = SuperNotification::latest()->paginate($perPage);

        return $this->sendSuccess('Request successful', $notifications);
    }

        /**
     * Get a notification
     */
    public function show($notificationId)
    {
        $notification = SuperNotification::find($notificationId);

        if (is_null($notification)) {
            return $this->sendErrorMessage('Notification not found', 404);
        }

        return $this->sendSuccess('Request successful', $notification);
    }

    /**
     * Get all unread notifications
     */
    public function unread()
    {
        $notifications = SuperNotification::unread()->get();

        /**
         * Mark notifications as delivered
         */
        $batch = Bus::batch([])->dispatch();

        $notifications->each(fn($notification) => $batch->add(new MarkAsDelivered($notification)));

        return $this->sendSuccess('Request successful', $notifications);
    }

    /**
     * Get all undelivered notifications
     */
    public function undelivered()
    {
        $notifications = SuperNotification::undelivered()->get();

        return $this->sendSuccess('Request successful', $notifications);
    }

    /**
     * Mark a notification as read
     */
    public function read($notificationId)
    {
        $notification = SuperNotification::find($notificationId);

        if (is_null($notification)) {
            return $this->sendErrorMessage('Notification not found', 404);
        }

        // Check if a notification has been read
        if ($notification->isRead()) {
            return $this->sendSuccess('Notification has already been read');
        }

        $notification->markAsRead();

        // Put the ID of the user the notification was read by
        $notification->markReadBy(auth()->id());

        return $this->sendSuccess('Notification successfully marked as read');
    }
    
    /**
     * Mark a notification as delivered
     */
    public function delivered($notificationId)
    {
        $notification = SuperNotification::find($notificationId);

        if (is_null($notification)) {
            return $this->sendErrorMessage('Notification not found', 404);
        }

        // Check if a notification has been delivered
        if ($notification->isDelivered()) {
            return $this->sendSuccess('Notification has already been delivered');
        }

        $notification->markAsDelivered();

        return $this->sendSuccess('Notification successfully marked as delivered');
    }

}
