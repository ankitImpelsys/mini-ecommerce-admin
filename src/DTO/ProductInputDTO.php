<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ProductInputDTO
{
    #[Assert\NotBlank(message: 'Name is required.')]
    public ?string $name = null;

    public ?string $description = null;

    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\Type(type: 'numeric', message: 'Price must be a valid number.')]
    #[Assert\Range(
        min: 0.01,
        max: 9999999.99,
        notInRangeMessage: 'Price must be between {{ min }} and {{ max }}.'
    )]
    public mixed $price = null;

    #[Assert\NotBlank(message: 'Stock is required.')]
    #[Assert\Type(type: 'integer', message: 'Stock must be a valid number.')]
    #[Assert\Range(
        min: 0,
        max: 999999999,
        notInRangeMessage: 'Stock must be between {{ min }} and {{ max }}.'
    )]
    public mixed $stock = null;

    #[Assert\NotBlank(message: 'Category ID is required.')]
    public ?string $category_id = null;


    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->name = $data['name'] ?? null;
        $dto->description = $data['description'] ?? null;
        $dto->price = $data['price'] ?? null;
        $dto->stock = $data['stock'] ?? null;
        $dto->category_id = $data['category_id'] ?? null;

        return $dto;
    }
}
