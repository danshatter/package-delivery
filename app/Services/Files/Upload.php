<?php

namespace App\Services\Files;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\{Collection, Model};

class Upload
{

    /**
     * Get the file extension from file content
     */
    public function extension($content)
    {
        $finfo = finfo_open();

        $mimeType = finfo_buffer($finfo, $content, FILEINFO_MIME_TYPE);

        finfo_close($finfo);
    
        return explode('/', $mimeType)[1];
    }

    /**
     * Get the mime type of the file based on the file content
     */
    public function type($content)
    {
        $finfo = finfo_open();

        $mimeType = finfo_buffer($finfo, $content, FILEINFO_MIME_TYPE);

        finfo_close($finfo);

        return $mimeType;
    }

    /**
     * Create a filename for the file
     */
    public function filename()
    {
        return Str::random(40).uniqid();
    }

    /**
     * Upload a file
     */
    public function create($folder, $content, $visibility = 'public')
    {
        $filename = "{$this->filename()}.{$this->extension($content)}";

        // Remove preceeding slashes
        $folder = trim($folder, '/');

        if (!empty($folder)) {
            $pathname = "{$folder}/{$filename}";
        } else {
            $pathname = $filename;
        }

        // Create the file
        Storage::put($pathname, $content);

        // Set the visibility of the file
        Storage::setVisibility($pathname, $visibility);

        return Storage::path($pathname);
    }

    /**
     * Return the url for a particular order or orders
     */
    public function formatImage($orders)
    {
        if ($orders instanceof Collection) {
            $orders->each(fn($order) => $this->urlImages($order));
        }

        if (is_array($orders)) {
            foreach ($orders as $order) {
                $this->urlImages($order);
            }
        }

        if ($orders instanceof Model) {
            $this->urlImages($orders);
        }

        return $orders;
    }

    /**
     * Get path from URL
     */
    public function pathFromUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);

        return trim($path, '/');
    }

    /**
     * Format the images
     */
    private function urlImages($order)
    {
        return $order->setAttribute('images', collect($order->images)->map(fn($image) => Storage::url($image)));
    }

}

