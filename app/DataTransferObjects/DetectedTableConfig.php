<?php

namespace App\DataTransferObjects;

final readonly class DetectedTableConfig
{
    public function __construct(
        public string $rowSelector,
        public int $addressCol,
        public int $portCol,
        public float $confidence,
    ) {}
}
