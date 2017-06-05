<?php

trait AssertsCreatedResource
{
    
    /**
     * Checks if a resource has been created in the provided
     * database table.
     * 
     * @param  string   $table
     * @param  string   $id
     * @return void
     */
    public function assertCreatedResource($table, $id)
    {
        $resource = DB::table($table)->where('id', $id)->first();
        $this->assertNotNull($resource);
    }

}