<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstantFeedbackRecipient;

class InstantFeedbackController extends Controller
{

    /**
     * Answer an instant feedback page.
     * 
     * @param  Illuminate\Http\Request  $request
     * @param  string                   $key
     * @return Illuminate\Http\Response
     */
    public function answer(Request $request, $key)
    {
        $ifRecipient = InstantFeedbackRecipient::where('key', $key)->firstOrFail();
        return response('Launching Edward Lynx mobile app...');
    }

}
