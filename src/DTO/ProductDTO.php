<?php

namespace App\DTO;

use App\Entity\Product;

class ProductDTO
{
    public string $name;
    public float $price;
    public int $stock;
    public ?string $description;
    public ?int $categoryId;


    public function __construct(Product $product)
    {
        $this->name = $product->getName();
        $this->price = $product->getPrice();
        $this->stock = $product->getStock();
        $this->description = $product->getDescription();
        $this->categoryId = $product->getCategory()?->getId();
    }
}
