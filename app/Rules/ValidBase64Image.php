<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Services\Files\Upload;

class ValidBase64Image implements Rule
{

    /**
     * The allowed file formats
     */
    private $allowed = ['jpg', 'jpeg', 'png'];
    
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Decode the base64 string
        $content = base64_decode($value);

        // Get the extension based on the file content
        $extension = app()->make(Upload::class)->extension($content);

        return in_array($extension, $this->allowed);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The file format must be one of '.implode(', ', $this->allowed);
    }

}
