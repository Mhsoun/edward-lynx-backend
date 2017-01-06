<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class AnswerController extends Controller
{
    
    /**
     * Show the form for creating a new resource.
     *
     * @param   Illuminate\Http\Request     $request
     * @return  Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'key'       => [
                'required',
                Rule::exists('survey_recipients', 'link')->where(function ($query) {
                    $query->where('hasAnswered', 0);
                })
            ],
            'answers'   =>  'required|valid_answers'
        ]);
            
        
    }
    
}
