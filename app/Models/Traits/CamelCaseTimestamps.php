<?php

namespace App\Models\Traits;

trait CamelCaseTimestamps
{
    
    /**
     * Transforms the created_at and updated_at fields
     * to camelCase.
     *
     * @return  array
     */
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize();
        
        $data['createdAt'] = $data['created_at'];
        unset($data['created_at']);
        
        $data['updatedAt'] = $data['updated_at'];
        unset($data['updated_at']);
        
        return $data;
    }
    
}