<?php

namespace App\Services\Exports;

class Csv
{

    /**
     * Create CSV content
     */
    public function make($fields, $data, $header = '')
    {
        // Create the header columns
        $header .= implode(',', $fields);

        // Return the CSV content for each iteration of the inputted data
        $content = collect($data)->reduce(fn($acc, $list) => "{$acc}".PHP_EOL.implode(',', $this->convert($list)), $header);

        return $content;
    }

    /**
     * Convert empty values to empty string
     */
    private function convert($values)
    {
        return collect($values)->map(fn($value) => $value ?: '')->all();
    }

}

