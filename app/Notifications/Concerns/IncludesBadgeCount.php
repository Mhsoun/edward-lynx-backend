<?php

namespace App\Notifications\Concerns;

use App\Models\User;

trait IncludesBadgeCount
{

    /**
     * Appends a badge key that contains the number of unread
     * notifications of $notifiable to $data.
     * 
     * @param  mixed    $notifiable
     * @param  array    $data
     * @return array
     */
    protected function withBadgeCountOf($notifiable, array $data)
    {
        // We only store badge counts of registered users.
        if (!$notifiable instanceof User) {
            return 0;
        }

        return array_merge($data, [
            'badge' => $notifiable->unreadNotifications()->count()
        ]);
    }

}