<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Traits\Response\HandlesFormat;
use App\Traits\Response\Google\{Geocoding, Geolocation, Direction, Firebase};

class Controller extends BaseController
{
    use HandlesFormat, Geocoding, Geolocation, Direction, Firebase;
}
