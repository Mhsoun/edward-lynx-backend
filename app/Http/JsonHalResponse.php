<?php

namespace App\Http;

use Route;
use stdClass;
use RuntimeException;
use App\Contracts\Routable;
use InvalidArgumentException;
use App\Contracts\JsonHalLinking;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Represents a JSON-HAL response returned by the API endpoints.
 */
class JsonHalResponse extends JsonResponse
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
     * @return  App\Http\JsonHalResponse
     */
    public function summarize()
    {
        $this->serializationOptions = self::SERIALIZE_SUMMARY;
        
        $data = $this->encode($this->input);
        $this->setData($data);
        
        return $this->update();
    }
    
    /**
     * Merges the provided $links array to the current response.
     *
     * @param   array   $links
     * @return  this
     */
    public function withLinks(array $links)
    {
        $links = $this->processLinks($links);
        
        $data = $this->getData();
        $data = $this->mergeLinks($links, $data);
        $this->setData($data);
        
        return $this->update();
    }
    
    /**
     * Appends additional fields to the response.
     *
     * @param   array   $fields
     * @return  this
     */
    public function with(array $fields)
    {
        $data = $this->getData();
        foreach ($fields as $key => $value) {
            $data->{$key} = $value;
        }
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
        } elseif ($input instanceof JsonHalCollection) {
            return $this->encodeJsonHalCollection($input);
        } elseif ($input instanceof Model) {
            return $this->encodeModel($input);
        } elseif ($input instanceof Collection) {
            return $this->encodeEloquentCollection($input);
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

        // Build initial response
        $resp = [
            '_links'    => $links,
            'total'     => $pager->total(),
            'num'       => $pager->perPage(),
            'pages'     => ceil($pager->total() / $pager->perPage()),
            'items'     => []
        ];
        
        if ($pager->isEmpty()) {
            return $resp;
        } else {
            // Process our collection.
            foreach ($pager->items() as $item) {
                $resp['items'][] = $this->encodeModel($item);
            }
            return $resp;
        }
    }
    
    /**
     * Converts a JsonHalCollection to a valid JSON-HAL response.
     *
     * @param   App\Http\JsonHalCollection  $collection
     * @return  array
     */
    public function encodeJsonHalCollection(JsonHalCollection $collection)
    {
        return $collection->jsonSerialize();
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
        if ($model instanceof Routable) {
            $url = $model->url();
            $links = [
                'self'  => ['href' => $url]
            ];
        }
        
        if ($model instanceof JsonHalLinking) {
            $newLinks = $model->jsonHalLinks();
            $newLinks = $this->processLinks($newLinks);
            $links = array_merge($links, $newLinks);
        }
        
        $json = $model->jsonSerialize($this->serializationOptions);
        
        if (empty($links)) {
            return $json;
        } else {
            return array_merge(['_links' => $links], $json);
        }
    }
    
    /**
     * Converts an Eloquent Collection into a proper JSON-HAL response.
     *
     * @param   Illuminate\Database\Eloquent\Collection $collection
     * @return  array
     */
    protected function encodeEloquentCollection(Collection $collection)
    {
        $result = [];
        $result['_links'] = [
            'self' => ['href' => request()->fullUrl()]
        ];
        
        $result['items'] = [];
        foreach ($collection as $record) {
            $result['items'][] = $this->encodeModel($record);
        }
        return $result;
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
        $result['_links'] = [
            'self' => ['href' => request()->fullUrl()]
        ];
        
        // If we have no items to encode, exit early.
        if (empty($arr)) {
            $result['items'] = [];
            return $result;
        }
        
        if (is_string(array_keys($arr)[0])) {
            $result = array_merge($result, $this->encodeAssociativeArray($arr));
        } else {
            $result['items'] = $this->encodeIndexedArray($arr);
        }
        
        return $result;
    }
    
    /**
     * Properly encodes an associative array as a JSON-HAL response.
     *
     * @param   array   $arr
     * @return  array
     */
    protected function encodeAssociativeArray(array $arr)
    {
        return $arr;
    }
    
    /**
     * Properly encodes an indexed (integer-keyed) array as a JSON-HAL response.
     *
     * @param   array   $arr
     * @return  array
     */
    protected function encodeIndexedArray(array $arr)
    {
        $items = [];
        foreach ($arr as $item) {
            if ($item instanceof Model) {
                $items[] = $this->encodeModel($item);
            } elseif ($item instanceof stdClass) {
                $items[] = json_decode(json_encode($item));
            } elseif (is_array($item)) {
                $items[] = $item;
            } else {
                throw new RuntimeException("Failed to encode item {$item} in array.");
            }
        }
        return $items;
    }
    
    /**
     * Converts string links into proper form.
     *
     * @param   array   $links
     * @return  array
     */
    protected function processLinks(array $links)
    {
        foreach ($links as $key => $link) {
            if (is_array($link)) {
                if (empty($link['href'])) {
                    throw new InvalidArgumentException('Missing href field in _links.');
                }
            } else {
                $link = strval($link);
                $links[$key] = ['href' => $link];
            }
        }
        return $links;
    }
    
    /**
     * Properly merges links into our response data, avoiding
     * existing links from being overwritten.
     *
     * @param   array           $links
     * @param   array|object    $data
     * @return  array
     */
    protected function mergeLinks(array $links, $data)
    {
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }
        
        if (!isset($data['_links'])) {
            $data = array_merge(['_links' => []], $data);
        }
        
        $data['_links'] = array_merge($data['_links'], $links);
        return $data;
    }
    
}