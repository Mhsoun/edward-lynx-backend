<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as BaseModel;

class Model extends BaseModel
{
 
    /**
     * Performs transformations to JSON responses.
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();
        $json = $this->isoDates($json);
        $json = $this->camelCaseTimestamps($json);
        return $json;
    }
    
    /**
     * Converts the keys names of this model's timestamps to camelCase.
     *
     * @param   array   $json
     * @return  array
     */
    protected function camelCaseTimestamps(array $json)
    {
        if (isset($json['created_at'])) {
            $json['createdAt'] = $json['created_at'];
            unset($json['created_at']);
        }
        
        if (isset($json['updated_at'])) {
            $json['updatedAt'] = $json['updated_at'];
            unset($json['updated_at']);
        }
        
        return $json;
    }
    
    /**
     * Transforms dates on this model to ISO 8601
     *
     * @param   array   $json
     * @return  array
     */
    protected function isoDates(array $json)
    {
        foreach ($this->getDates() as $key) {
            $date = Carbon::parse($json[$key]);
            $json[$key] = $date->toIso8601String();
        }
        return $json;
    }
    
}