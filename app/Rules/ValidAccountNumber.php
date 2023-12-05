<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\{Rule, DataAwareRule};
use App\Models\Account;

class ValidAccountNumber implements Rule, DataAwareRule
{
    
    /**
     * All of the data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
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
        return Account::where('account_number', $value)
                    ->where('bank_code', $this->data['bank_code'])
                    ->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute has already been taken';
    }

}
