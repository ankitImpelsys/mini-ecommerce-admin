<?php

namespace App\DTO;

use App\Entity\Product;
use App\Service\Encryptor;

class ProductDTO
{
    public string $name;
    public float $price;
    public int $stock;
    public ?string $description;
    public ?string $categoryId; // Now encrypted string

    public function __construct(Product $product, Encryptor $encryptor)
    {
        $this->name = $product->getName();
        $this->price = $product->getPrice();
        $this->stock = $product->getStock();
        $this->description = $product->getDescription();

        $category = $product->getCategory();
        $this->categoryId = $category ? $encryptor->encrypt((string) $category->getId()) : null;
    }
}
