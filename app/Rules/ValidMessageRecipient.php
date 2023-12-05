<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\{User, Role};
use App\Services\Phone\Nigeria;

class ValidMessageRecipient implements Rule
{

    /**
     * The authenticated user
     */
    private $user;

    /**
     * Create an instance
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Get the recipient
        $recipient = User::find($value);

        // If the authenticated user is a driver, then they can only send messages to customers
        if ($this->user->role_id === Role::DRIVER) {
            return $recipient->role_id === Role::CUSTOMER;
        }

        // If the authenticated user is a customer, then they can only send messages to drivers
        if ($this->user->role_id === Role::CUSTOMER) {
            return $recipient->role_id === Role::DRIVER;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The user is not a valid recipient';
    }

}
