<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\TestNotification;

class TestController extends Controller
{
    
    public function push(Request $request)
    {
        $user = $request->user();
        $title = $request->input('title', 'Sample Notification.');
        $body = $request->input('body', 'adsadadknsadnadnjadnjasd');

        $users = User::all();
        foreach ($users as $user) {
            $user->notify(new TestNotification($title, $body));
        }
        return response("<br>sent notifications.<pre>". var_dump($title) ."\n". var_dump($body) ."</pre>");
    }
    
}
