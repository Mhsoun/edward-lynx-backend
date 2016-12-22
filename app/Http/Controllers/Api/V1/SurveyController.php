<?php namespace App\Http\Controllers\Api\V1;

use App\Models\Survey;
use Illuminate\Http\Request;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;

class SurveyController extends Controller
{

    /**
     * Returns a list of surveys the user can access.
     *
     * @param  Illuminate\Http\Request $request
     * @return JSONResponse
     */
    public function index(Request $request)
    {
        $perPage = intval($request->input('per_page', 10));

        if ($perPage <= 0 || $perPage > 50) {
            throw new ApiException("Parameter 'per_page' should be greater than 0 but less than or equal to 50. You provided '$perPage'.");
        }

        $user = $request->user();

        if ($user->isA('superadmin')) {
            $surveys = Survey::select('*');
        } else {
            $surveys = Survey::where('ownerId', $user->id);
        }

        $surveys = $surveys->latest('startDate')
            ->simplePaginate($perPage);

        return response()->json($surveys);
    }

}