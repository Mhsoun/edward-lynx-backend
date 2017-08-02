<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\QuestionCategory;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    
    /**
     * All question categories endpoint.
     *
     * @param   Illuminate\Http\Request $request
     * @return  App\Http\JsonHalResponse
     */
    public function index(Request $request)
    {
        $currentUser = $request->user();
        $colleagues = $currentUser->colleagues()->map(function ($item) {
            return $item->id;
        })->toArray();

        $categories = QuestionCategory::notForInstantFeedbacks()
            ->where('isSurvey', false)
            ->whereIn('ownerId', $colleagues)
            ->orderBy('title', 'ASC')
            ->get();
        
        return response()->jsonHal($categories);
    }
    
    /**
     * Returns question category details.
     *
     * @param   App\Models\QuestionCategory
     * @return  App\Http\JsonHalResponse
     */
    public function show(QuestionCategory $category)
    {
        return response()->jsonHal($category);
    }

}
