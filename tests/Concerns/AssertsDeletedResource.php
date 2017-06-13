<?php

trait AssertsDeletedResource
{

    /**
     * Checks if the response is a valid REST 204 Response.
     * 
     * @return void
     */
    public function assertDeletedResource()
    {
        $headers = $this->response->headers;
        $contentType = $headers->get('Content-Type');
        $content = $this->response->content();

        $this->assertResponseStatus(204);
        $this->assertEquals($content, '');
    }

}