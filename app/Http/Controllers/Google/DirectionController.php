<?php

namespace App\Http\Controllers\Google;

use App\Http\Controllers\Controller;
use App\Services\Google\Direction;

class DirectionController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the current coordinates
     */
    public function show()
    {
        $validator = validator()->make(request()->all(), [
            'origin' => ['required'],
            'destination' => ['required'],
            'waypoints' => ['nullable', 'array']
        ]);

        if ($validator->fails()) {
            return $this->sendErrors($validator->errors());
        }

        extract($validator->validated());

        $data = app()->make(Direction::class)->get($origin, $destination, $waypoints ?? null);

        // Check if the response is okay from Google
        if ($data['status'] !== 'OK') {
            return $this->sendGoogleDirectionErrorMessage($data['status']);
        }

        return $this->sendSuccess('Request successful', $this->formatDirectionResponse($data));
    }

}