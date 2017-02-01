<?php

namespace App\Contracts;

/**
 * Classes that implement this contract can return a URL to
 * their instances.
 */
interface Routable
{
    
    /**
     * Returns the URL to this model.
     *
     * @return  string
     */
    public function url();
    
}