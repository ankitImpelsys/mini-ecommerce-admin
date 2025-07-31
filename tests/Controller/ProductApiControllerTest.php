<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductApiControllerTest extends WebTestCase
{
    public function testIndexReturnsUserProducts(): void
    {
        $client = static::createClient();
        $container = self::getContainer();

        $entityManager = $container->get(EntityManagerInterface::class);

        // Simulate a user with ROLE_ADMIN
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $user->setEmail('admin_' . uniqid() . '@example.com');
        $user->setPassword('$2y$13$examplehashedpasswordstring...'); // hashed dummy password

        $entityManager->persist($user);
        $entityManager->flush();

        // Login the user
        $client->loginUser($user);

        // Create a product for this user
        $product = new Product();
        $product->setName('Test Product');
        $product->setPrice(100);
        $product->setStock(10);
        $product->setUser($user);
        $product->setIsDeleted(false);

        $entityManager->persist($product);
        $entityManager->flush();

        // Call the endpoint
        $client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString('Test Product', $client->getResponse()->getContent());
    }

    public function testShowReturnsProductForAuthorizedUser(): void
    {
        $client = static::createClient();
        $container = self::getContainer();

        $entityManager = $container->get(EntityManagerInterface::class);

        // Create and persist user
        $user = new User();
        $user->setEmail('admin_' . uniqid() . '@example.com');

        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword('$2y$13$examplehashedpasswordstring...');

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        // Create and persist product
        $product = new Product();
        $product->setName('Test Product');
        $product->setPrice(99.99);
        $product->setStock(10);
        $product->setUser($user);
        $product->setIsDeleted(false);

        $entityManager->persist($product);
        $entityManager->flush();

        $client->request('GET', '/api/products/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString('Test Product', $client->getResponse()->getContent());
    }

    public function testCreateProductForAuthorizedUser(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Create a unique user
        $user = new User();
        $user->setEmail('admin_' . uniqid() . '@example.com');
        $user->setPassword('$2y$13$examplehashedpasswordstring...');
        $user->setRoles(['ROLE_ADMIN']);

        $entityManager->persist($user);
        $entityManager->flush();

        // Login the user
        $client->loginUser($user);

        // Make sure a category with ID 1 exists (or adjust this)
        $categoryId = 1;

        // Create product data
        $postData = [
            'name' => 'New Product',
            'price' => 149.99,
            'stock' => 20,
            'categoryId' => $categoryId,
        ];

        // Send POST request
        $client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($postData)
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $json);
        $this->assertEquals('Product created!', $json['data']['status']);
        $this->assertArrayHasKey('id', $json['data']);
    }

}
