<?php

namespace App\Events;

class Registered extends Event
{
    
    public $user;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }
    
}
