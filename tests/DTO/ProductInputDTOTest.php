<?php

namespace App\Tests\DTO;

use App\DTO\ProductInputDTO;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductInputDTOTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidDTO(): void
    {
        $dto = ProductInputDTO::fromArray([
            'name' => 'Test Product',
            'description' => 'Optional description',
            'price' => 99.99,
            'stock' => 10,
            'category_id' => '123'
        ]);

        $errors = $this->validator->validate($dto);
        $this->assertCount(0, $errors, 'DTO should be valid with all correct fields.');
    }

    public function testMissingFields(): void
    {
        $dto = new ProductInputDTO(); // all null

        $errors = $this->validator->validate($dto);

        $this->assertGreaterThanOrEqual(1, count($errors));
        $this->assertEquals('Name is required.', $errors[0]->getMessage());
    }

    public function testInvalidTypes(): void
    {
        $dto = ProductInputDTO::fromArray([
            'name' => 'Test Product',
            'price' => 'invalid',
            'stock' => 'not_an_integer',
            'category_id' => '123'
        ]);

        $errors = $this->validator->validate($dto);

        $messages = array_map(fn($e) => $e->getMessage(), iterator_to_array($errors));
        $this->assertContains('Price must be a valid number.', $messages);
        $this->assertContains('Stock must be a valid number.', $messages);
    }

    public function testInvalidRanges(): void
    {
        $dto = ProductInputDTO::fromArray([
            'name' => 'Test Product',
            'price' => 0,
            'stock' => -1,
            'category_id' => '123'
        ]);

        $errors = $this->validator->validate($dto);

        $messages = array_map(fn($e) => $e->getMessage(), iterator_to_array($errors));
        $this->assertContains('Price must be between 0.01 and 9999999.99.', $messages);
        $this->assertContains('Stock must be between 0 and 999999999.', $messages);
    }
}
