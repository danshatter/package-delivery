<?php

namespace App\Http\Controllers;

use App\Models\Card;

class CardController extends Controller
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
     * Get the cards of a user
     */
    public function index()
    {
        $cards = auth()->user()->cards()->latest()->get();

        return $this->sendSuccess('Request successful', $cards);
    }

    /**
     * Get a card of a user
     */
    public function show($cardId)
    {
        $card = auth()->user()->cards()->find($cardId);

        if (is_null($card)) {
            return $this->sendErrorMessage('Card not found', 404);
        }

        return $this->sendSuccess('Request successful', $card);
    }

    /**
     * Delete a card of a user
     */
    public function destroy($cardId)
    {
        $card = auth()->user()->cards()->find($cardId);

        if (is_null($card)) {
            return $this->sendErrorMessage('Card not found', 404);
        }

        $card->delete();

        return $this->sendSuccess('Card deleted successfully');
    }

}
