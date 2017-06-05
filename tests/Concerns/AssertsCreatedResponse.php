<?php

trait AssertsCreatedResponse
{
    
    /**
     * Checks if the response is a valid REST "Created" response.
     * 
     * @return void
     */
    public function assertCreatedResponse()
    {
        $headers = $this->response->headers;
        $location = $headers->get('Location');
        $contentType = $headers->get('Content-Type');
        $content = $this->response->content();

        $this->assertResponseStatus(201);
        $this->assertNotEmpty($location);
        $this->assertEquals('application/json', $contentType);
        $this->assertEquals($content, '');
    }

    /**
     * Returns the ID provided in the response Location header.
     * 
     * @return int 
     */
    protected function getResourceId()
    {
        $headers = $this->response->headers;
        if ($headers->has('Location')) {
            $location = $headers->get('Location');
            return array_last(explode('/', $location));
        } else {
            return null;
        }
    }

}