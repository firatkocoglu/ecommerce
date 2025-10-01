<?php

namespace App\Helpers;

class ImageUrlHelper
{
    public static function createCloudinaryUrl(string $publicId): ?array
    {
        if (empty($publicId)) {
            return null;
        }

        $cloudName = env('CLOUDINARY_CLOUD_NAME', '');
        if (empty($cloudName)) {
            return null;
        }

        $transform = [
            'thumb' => 'f_auto,q_auto,w_160,h_160,c_fill,g_auto',
            'list' => 'f_auto,q_auto,w_400,h_400,c_fill,g_auto',
            'detail' => 'f_auto,q_auto,w_1000,c_limit',
        ];

        $baseUrl = "https://res.cloudinary.com/$cloudName/image/upload/";

        $urls = [];

        foreach ($transform as $key => $transformation) {
            $urls[$key] = $baseUrl.$transformation.'/'.ltrim($publicId, '/');
        }

        return $urls;
    }
}
