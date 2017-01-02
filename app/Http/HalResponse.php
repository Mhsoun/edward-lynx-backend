<?php

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Represents a JSON-HAL response returned by the API endpoints.
 */
class HalResponse extends JsonResponse
{
    
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
        $data = $this->encode($input);
        $headers['Content-Type'] = 'application/hal+json';
        
        parent::__construct($data, $status, $headers, $options);
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
            $collection[] = array_merge([
                '_links' => ['self' => ['href' => $this->modelUrl($item)]]
            ], $item->jsonSerialize());
        }
        
        return [
            '_links'    => $links,
            $key        => $collection,
            'total'     => $pager->total(),
            'perPage'   => $pager->perPage(),
            'totalPages'=> ceil($pager->total() / $pager->perPage())
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
        $links = [
            'self' => ['href' => $this->modelUrl($model)]
        ];
        return array_merge($links, $model->jsonSerialize());
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
        return route("{$apiPrefix}-{$key}", $model);
    }
    
}