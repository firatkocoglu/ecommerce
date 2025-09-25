<?php

namespace App\Services\ProductImages\DTO;

use App\Models\ProductImage;

class UpdateImageResult
{
    public function __construct(
        public readonly ProductImage $image,
        public readonly bool $siblingsChanged,
    ) {}
}
