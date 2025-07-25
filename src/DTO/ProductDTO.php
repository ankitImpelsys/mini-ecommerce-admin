<?php

namespace App\DTO;

use App\Entity\Product;

class ProductDTO
{
    public int $id;
    public string $name;
    public ?string $description;
    public float $price;
    public int $stock;
    public ?string $image;

    public function __construct(Product $product)
    {
        $this->id = $product->getId();
        $this->name = $product->getName();
        $this->description = $product->getDescription();
        $this->price = $product->getPrice();
        $this->stock = $product->getStock();
        $this->image = $product->getImageFilename();
    }
}

