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
     * @return  void
     */
    protected function sendToFcm(FirebaseNotification $notif)
    {
        $key = sprintf('key=%s', config('firebase.api_key'));

        $client = new Client;
        $req = $client->request('POST', self::FCM_ENDPOINT, [
            'headers'   => [
                'Authorization' => $key
            ],
            'json'      => [
                'registration_ids'  => $notif->tokens,
                'notification'      => [
                    'title' => $notif->title,
                    'body'  => $notif->body
                ],
                'data'              => $notif->data
            ]
        ]);
    }

}