<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ApplicationUpTest extends TestCase
{
    /**
     * Application is alive
     */
    public function test_application_is_up()
    {
        $this->get('/');

        $this->assertResponseOk();
    }
}
