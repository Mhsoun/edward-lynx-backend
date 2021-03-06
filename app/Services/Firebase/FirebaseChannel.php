<?php

namespace App\Services\Firebase;

use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;

/**
 * Allows notifications to send a Firebase Notification to
 * registered mobile devices.
 */
class FirebaseChannel
{

    const FCM_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

    /**
     * Send the given notification.
     * 
     * @param   mixed                                    $notifiable
     * @param   Illuminate\Notifications\Notification    $notification
     * @return  void
     */
    public function send($notifiable, Notification $notification)
    {
        $notif = $notification->toFirebase($notifiable);
        $this->sendToFcm($notif);
    }

    /**
     * Sends a notification through Firebase Cloud Messaging.
     * 
     * @param   App\Services\Firebase\FirebaseNotification  $notif
     * @return  bool
     */
    protected function sendToFcm(FirebaseNotification $notif)
    {
        if (count($notif->tokens) == 0) {
            return false;
        }

        $key = sprintf('key=%s', config('services.firebase.api_key'));
        $payload = [
            'registration_ids'  => $notif->tokens,
            'notification'      => [
                'title' => $notif->title,
                'body'  => $notif->body
            ],
            'data'              => $notif->data
        ];

        // If we have a badge count in data, move it under the notification key.
        if (isset($payload['data']['badge'])) {
            $payload['notification']['badge'] = $payload['data']['badge'];
        }

        $client = new Client;
        $req = $client->request('POST', self::FCM_ENDPOINT, [
            'headers'   => [
                'Authorization' => $key
            ],
            'json'      => $payload
        ]);

        return true;
    }

}