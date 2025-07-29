<?php

namespace App\DTO;

class ApiResponseDTO
{
    public string $requested_at;
    public mixed $data;

    public function __construct(mixed $data)
    {
        $this->requested_at = (new \DateTimeImmutable())->format(\DateTime::ATOM);
        $this->data = $data;
    }
}
