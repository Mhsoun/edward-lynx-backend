<?php

namespace App\Http;

use Route;
use stdClass;
use RuntimeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Represents a JSON-HAL response returned by the API endpoints.
 */
class HalResponse extends JsonResponse
{
    
    const SERIALIZE_FULL = 0;
    const SERIALIZE_SUMMARY = 1;
    
    /**
     * Our input data.
     *
     * @var integer
     */
    protected $input;
    
    /**
     * Serialization options flag sent when invoking
     * jsonSerialize() for Models.
     *
     * @var integer
     */
    protected $serializationOptions = 0;
    
    /**
     * Constructor.
     *
     * @param   mixed   $input
     * @param   integer $status
     * @param   array   $headers
     * @param   integer $options
     */
    public function __construct($input, $status = 200, array $headers = [], $options = 0)
    {
        $this->input = $input;
        
        $headers['Content-Type'] = 'application/json';
        parent::__construct($this->encode($input), $status, $headers, $options);
    }
    
    /**
     * Tells serializers to not return a whole data set
     * but instead a summary only.
     *
     * @return  App\Http\HalResponse
     */
    public function summarize()
    {
        $this->serializationOptions = self::SERIALIZE_SUMMARY;
        
        $data = $this->encode($this->input);
        $this->setData($data);
        
        return $this->update();
    }
    
    /**
     * Invokes the proper encoder depending on the type of $input.
     *
     * @param   mixed   $input
     * @return  array
     */
    protected function encode($input)
    {
        if ($input instanceof LengthAwarePaginator) {
            return $this->encodeLengthAwarePaginator($input);
        } elseif ($input instanceof Model) {
            return $this->encodeModel($input);
        } elseif (is_array($input)) {
            return $this->encodeArray($input);
        }
    }
    
    /**
     * Converts a LengthAwarePaginator input to a proper HAL response.
     *
     * @param   Illuminate\Contracts\Pagination\LengthAwarePaginator    $pager
     * @return  array
     */
    protected function encodeLengthAwarePaginator(LengthAwarePaginator $pager)
    {
        // Include the number of entries per page if it is present.
        $nextPage = $pager->nextPageUrl();
        $prevPage = $pager->previousPageUrl();
        if (request()->has('num')) {
            $num = intval(request('num'));
            $nextPage = $nextPage ? "{$nextPage}&num={$num}" : null;
            $prevPage = $prevPage ? "{$prevPage}&num={$num}" : null; 
        }
        
        // Build our top level "_links" field
        $links = [];
        $links['self'] = ['href' => request()->fullUrl()];
        if ($nextPage) {
            $links['next'] = ['href' => $nextPage];
        }
        if ($prevPage) {
            $links['prev'] = ['href' => $prevPage];
        }
        
        // Generate the pluralized name of the collection
        $key = class_basename($pager->items()[0]);
        $key = strtolower(str_plural($key));
        
        // Process our collection.
        $collection = [];
        foreach ($pager->items() as $item) {
            $collection[] = $this->encodeModel($item);
        }
        
        return [
            '_links'    => $links,
            $key        => $collection,
            'total'     => $pager->total(),
            'num'       => $pager->perPage(),
            'pages'     => ceil($pager->total() / $pager->perPage())
        ];
    }
    
    /**
     * Converts an Eloquent Model to a JSON-HAL response.
     *
     * @param   Illuminate\Database\Eloquent\Model  $model
     * @return  array
     */
    protected function encodeModel(Model $model)
    {
        $links = [];
        if ($modelUrl = $this->modelUrl($model)) {
            $links = [
                '_links' => [
                    'self' => ['href' => $modelUrl]
                ]
            ];
        }
        
        $modelJson = $model->jsonSerialize($this->serializationOptions);
        
        return array_merge($links, $modelJson);
    }
    
    /**
     * Generates the URL to a model/resource.
     *
     * @param   Illuminate\Database\Eloquent\Model  $model
     * @return  string
     */
    protected function modelUrl(Model $model)
    {
        $apiPrefix = 'api1';
        $key = class_basename($model);
        $key = strtolower(str_singular($key));
        $modelRoute = "{$apiPrefix}-{$key}";
        
        if (Route::has($modelRoute)) {
            return route($modelRoute, $model);
        } else {
            return null;
        }
    }
    
    /**
     * Converts an array of Models, objects or arrays to a JSON-HAL response.
     *
     * @param   array   $arr
     * @return  array
     */
    protected function encodeArray(array $arr)
    {
        $result = [];
        foreach ($arr as $item) {
            if ($item instanceof Model) {
                $result[] = $this->encodeModel($item);
            } elseif ($item instanceof stdClass) {
                $result[] = json_decode(json_encode($item));
            } elseif (is_array($item)) {
                $result[] = $item;
            } else {
                throw new RuntimeException("Failed to encode item {$item} in array.");
            }
        }
        return $result;
    }
    
}