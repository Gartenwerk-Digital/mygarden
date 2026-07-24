<?php

namespace App\Support;

readonly class StoredImage
{
    public function __construct(
        public string $path,
        public string $mimeType,
        public int $size,
    ) {}
}
