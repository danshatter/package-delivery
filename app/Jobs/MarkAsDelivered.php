<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;

class MarkAsDelivered extends Job
{
    use Batchable;

    public $communication;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($communication)
    {
        $this->communication = $communication;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->communication->markAsDelivered();
    }

}
