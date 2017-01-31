<?php

namespace App\Contracts;

/**
 * Classes that implement this contract can return additional URLs
 * or links to resources related to this class.
 */
interface JsonHalLinking
{
    
    /**
     * Returns additional JSON-HAL link entries.
     *
     * @return  array
     */
    public function jsonHalLinks();
    
}