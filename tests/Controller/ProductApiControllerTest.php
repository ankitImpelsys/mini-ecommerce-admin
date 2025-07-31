<?php

namespace App\Tests\Controller;

use App\Entity\Category;
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

    public function testCreateProductWithNegativePrice(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Create and persist a user
        $user = new User();
        $user->setEmail('invalid_' . uniqid() . '@example.com');
        $user->setPassword('$2y$13$examplehashedpasswordstring...');
        $user->setRoles(['ROLE_ADMIN']);
        $entityManager->persist($user);

        // Create and persist a category
        $category = new Category();
        $category->setName('Invalid Category');
        $entityManager->persist($category);

        $entityManager->flush();

        $client->loginUser($user);

        // Send POST request with negative price
        $client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Invalid Product',
                'price' => -100,
                'stock' => 10,
                'categoryId' => $category->getId()
            ])
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertStringContainsString('price', json_encode($json));
    }


    public function testUpdateProductForAuthorizedUser(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Create a user
        $user = new User();
        $user->setEmail('admin_' . uniqid() . '@example.com');
        $user->setPassword('$2y$13$examplehashedpasswordstring...');
        $user->setRoles(['ROLE_ADMIN']);
        $entityManager->persist($user);

        // Create a category (if required by product)
        $category = new Category();
        $category->setName('Test Category');
        $entityManager->persist($category);

        // Create a product
        $product = new Product();
        $product->setName('Old Product');
        $product->setPrice(100);
        $product->setStock(10);
        $product->setUser($user);
        $product->setCategory($category);
        $product->setIsDeleted(false);
        $entityManager->persist($product);

        $entityManager->flush();

        // Login user
        $client->loginUser($user);

        // Prepare updated data
        $updatedData = [
            'name' => 'Updated Product',
            'price' => 199.99,
            'stock' => 15,
            'categoryId' => $category->getId(),
        ];

        // Send PUT request
        $client->request(
            'PUT',
            '/api/products/' . $product->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedData)
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertEquals('Product updated!', $json['data']['status']);
    }

    public function testDeleteProductForAuthorizedUser(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Create user
        $user = new User();
        $user->setEmail('delete_' . uniqid() . '@example.com');
        $user->setPassword('$2y$13$examplehashedpasswordstring...');
        $user->setRoles(['ROLE_ADMIN']);
        $entityManager->persist($user);

        // Create category (if needed)
        $category = new Category();
        $category->setName('Delete Category');
        $entityManager->persist($category);

        // Create product
        $product = new Product();
        $product->setName('Product To Delete');
        $product->setPrice(50);
        $product->setStock(5);
        $product->setUser($user);
        $product->setCategory($category);
        $product->setIsDeleted(false);
        $entityManager->persist($product);

        $entityManager->flush();

        // Login the user
        $client->loginUser($user);

        // Send DELETE request
        $client->request(
            'DELETE',
            '/api/products/' . $product->getId()
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertEquals('Product deleted', $json['data']['status']);
    }



}
