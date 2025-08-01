<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductEntityTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testProductCanBePersistedToDatabase(): void
    {
        $user = new User();
        $user->setEmail('entityuser_' . uniqid() . '@example.com');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_ADMIN']);

        $category = new Category();
        $category->setName('Test Category');

        $product = new Product();
        $product->setName('Entity Test Product');
        $product->setPrice(99.99);
        $product->setStock(5);
        $product->setIsDeleted(false);
        $product->setUser($user);
        $product->setCategory($category);

        $this->em->persist($user);
        $this->em->persist($category);
        $this->em->persist($product);
        $this->em->flush();

        $retrieved = $this->em->getRepository(Product::class)->find($product->getId());

        $this->assertNotNull($retrieved);
        $this->assertEquals('Entity Test Product', $retrieved->getName());
        $this->assertSame(5, $retrieved->getStock());
    }

    public function testNegativePriceValidationFails(): void
    {
        $product = new Product();
        $product->setName('Invalid Product');
        $product->setPrice(-50);
        $product->setStock(10);

        $violations = $this->validator->validate($product);
        $this->assertGreaterThan(0, count($violations));

        $priceViolation = array_filter(iterator_to_array($violations), fn($v) => $v->getPropertyPath() === 'price');
        $this->assertNotEmpty($priceViolation);
    }

    public function testNegativeStockValidationFails(): void
    {
        $product = new Product();
        $product->setName('Invalid Product');
        $product->setPrice(100);
        $product->setStock(-1);

        $violations = $this->validator->validate($product);
        $this->assertGreaterThan(0, count($violations));

        $stockViolation = array_filter(iterator_to_array($violations), fn($v) => $v->getPropertyPath() === 'stock');
        $this->assertNotEmpty($stockViolation);
    }
}
