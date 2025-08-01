<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderEntityTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testOrderCanBePersistedWithProducts(): void
    {
        $user = new User();
        $user->setEmail('orderuser_' . uniqid() . '@example.com');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_ADMIN']);

        $category = new Category();
        $category->setName('Order Test Category');

        $product1 = new Product();
        $product1->setName('Product 1')->setPrice(100)->setStock(10)->setUser($user)->setIsDeleted(false)->setCategory($category);

        $product2 = new Product();
        $product2->setName('Product 2')->setPrice(200)->setStock(5)->setUser($user)->setIsDeleted(false)->setCategory($category);

        $order = new Order();
        $order->setCustomerName('Test Customer')
            ->setStatus('pending')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUser($user)
            ->addProduct($product1)
            ->addProduct($product2);

        $this->em->persist($user);
        $this->em->persist($category);
        $this->em->persist($product1);
        $this->em->persist($product2);
        $this->em->persist($order);
        $this->em->flush();

        $savedOrder = $this->em->getRepository(Order::class)->find($order->getId());

        $this->assertNotNull($savedOrder);
        $this->assertEquals('Test Customer', $savedOrder->getCustomerName());
        $this->assertCount(2, $savedOrder->getProducts());
    }

    public function testOrderReturnsOnlyActiveProducts(): void
    {
        $user = new User();
        $user->setEmail('deletedproducts_' . uniqid() . '@example.com');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_ADMIN']);

        $category = new Category();
        $category->setName('Deleted Test');

        $activeProduct = new Product();
        $activeProduct->setName('Active Product')->setPrice(100)->setStock(10)->setUser($user)->setIsDeleted(false)->setCategory($category);

        $deletedProduct = new Product();
        $deletedProduct->setName('Deleted Product')->setPrice(200)->setStock(5)->setUser($user)->setIsDeleted(true)->setCategory($category);

        $order = new Order();
        $order->setCustomerName('Customer X')
            ->setStatus('confirmed')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUser($user)
            ->addProduct($activeProduct)
            ->addProduct($deletedProduct);

        $this->em->persist($user);
        $this->em->persist($category);
        $this->em->persist($activeProduct);
        $this->em->persist($deletedProduct);
        $this->em->persist($order);
        $this->em->flush();

        $retrieved = $this->em->getRepository(Order::class)->find($order->getId());

        $this->assertCount(2, $retrieved->getProducts()); // All products
        $this->assertCount(1, $retrieved->getActiveProducts()); // Only non-deleted
        $this->assertSame('Active Product', $retrieved->getActiveProducts()[0]->getName());
    }
}
