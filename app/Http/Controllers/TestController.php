<?php

namespace App\Http\Controllers;

use App\Models\{Order, Vehicle, User, VehicleModel};
use Carbon\Carbon;
use App\Services\Google\Geocoding;
use App\Services\Geography\Location;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendPushNotification;

class TestController extends Controller
{
    use \App\Traits\Response\HandlesFormat;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * For testing purposes
     */
    public function index()
    {
        
    }

}

