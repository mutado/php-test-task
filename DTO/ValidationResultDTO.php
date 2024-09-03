<?php

namespace Operations\Notification\DTO;

class ValidationResultDTO
{
    public function __construct(
        public bool $isValid,
        public array $errors,
        public ?array $validatedData = null
    ) {}
}