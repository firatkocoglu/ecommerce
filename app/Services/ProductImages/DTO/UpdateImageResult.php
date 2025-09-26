<?php

namespace App\Services\ProductImages\DTO;

use App\Models\ProductImage;

readonly class UpdateImageResult
{
    public function __construct(
        public ProductImage $image,
        public bool $siblingsChanged,
    ) {}
}
