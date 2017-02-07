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
        $data = $notification->toFirebase($notifiable);
        $this->sendToFcm(
            $notification->tokens,
            $notification->title,
            $notification->body,
            $notification->data
        );
    }

    /**
     * Sends a notification through Firebase Cloud Messaging.
     * 
     * @param   string   $tokens
     * @param   string   $title
     * @param   string   $body
     * @param   array    $data
     * @return  void
     */
    protected function sendToFcm($tokens, $title, $body, array $data = [])
    {
        $client = new Client;
        $req = $client->request('POST', self::FCM_ENDPOINT, [
            'headers'   => [
                'Authorization' => sprintf('key=%s', config('firebase.api_key'))
            ],
            'json'      => [
                'registration_ids'  => $tokens,
                'notification'      => [
                    'title' => $title,
                    'body'  => $body
                ],
                'data'              => $data
            ]
        ]);
    }

}