<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\PromoCode;

class ValidPromoCodeType implements Rule
{

    private $allowed = [
        PromoCode::PUBLIC_CODE,
        PromoCode::PRIVATE_CODE,
        PromoCode::RESTRICTED_CODE
    ];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return in_array($value, $this->allowed);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is not valid. Should be any of '.implode(', ', $this->allowed);
    }

}
