<?php

namespace App\Services\Firebase;

class FirebaseNotification
{

    /**
     * The title of this notification.
     * 
     * @var  string
     */
    public $title;

    /**
     * The body/message of this notification.
     * 
     * @var  string
     */
    public $body;

    /**
     * The data of this notification.
     * 
     * @var  array
     */
    public $data = [];

    /**
     * The device tokens this notification will be sent to.
     * 
     * @var  array
     */
    public $tokens = [];

    /**
     * Constructor.
     * 
     * @param   string  $title
     * @param   string  $body
     * @param   array   $data
     */
    public function __construct($title = '', $body = '', array $data = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Sets the notification title.
     * 
     * @param   string  $title
     * @return  this
     */
    public function title($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Sets the notification body.
     * 
     * @param   string  $body
     * @return  this
     */
    public function body($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Sets the notification data.
     * 
     * @param   string  $data
     * @return  this
     */
    public function data($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Specifies the devices this notification will be sent to.
     * 
     * @param   string  $tokens
     * @return  this
     */
    public function to($tokens)
    {
        if (is_string($tokens)) {
            $tokens = [$tokens];
        }

        $this->tokens = $tokens;
        return $this;
    }

}