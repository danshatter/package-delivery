<?php

namespace App\Traits\Messaging;

trait HandlesMessaging
{
    
    /**
     * Mark a message or notification as delivered
     */
    public function markAsDelivered()
    {
        $this->forceFill([
            'delivered_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark a message or notification as read
     */
    public function markAsRead()
    {
        $this->forceFill([
            'read_at' => $this->freshTimestamp()
        ])->save();
    }

    /**
     * Mark a message or notification as undelivered
     */
    public function markAsUndelivered()
    {
        $this->forceFill([
            'delivered_at' => null
        ])->save();
    }

    /**
     * Mark a message or notification as unread
     */
    public function markAsUnread()
    {
        $this->forceFill([
            'read_at' => null
        ])->save();
    }

    /**
     * Check if a message or notification has been delivered
     */
    public function isDelivered()
    {
        return !is_null($this->delivered_at);
    }

    /**
     * Check if a message or notification has been read
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * Get delivered messages or notifications
     */
    public function scopeDelivered($query)
    {
        return $query->whereNotNull('delivered_at');
    }

    /**
     * Get undelivered messages or notifications
     */
    public function scopeUndelivered($query)
    {
        return $query->whereNull('delivered_at');
    }

    /**
     * Get read messages
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Get unread messages
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

}
