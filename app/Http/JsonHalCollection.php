<?php

namespace App\Http;

use Illuminate\Support\Collection;

class JsonHalCollection extends Collection
{
     
    /**
     * The URL to this collection.
     *
     * @var string
     */
    protected $selfUrl;
    
    /**
     * Constructor.
     *
     * @param   array   $items
     * @param   string  $selfUrl
     */
    public function __construct(array $items = [], $selfUrl = null)
    {
        parent::__construct($items);
        $this->selfUrl = $selfUrl;
    }
    
    /**
     * Serializes this collection to a valid JSON-HAL response.
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        return [
            '_links'    => [
                'self'  => ['href' => $this->selfUrl]
            ],
            'items'     => $this->items
        ];
    }
    
}