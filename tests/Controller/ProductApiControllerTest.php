<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductApiControllerTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function createUser(string $emailPrefix = 'admin'): User
    {
        $user = new User();
        $user->setEmail($emailPrefix . '_' . uniqid() . '@example.com');
        $user->setPassword('$2y$13$examplehashedpasswordstring...'); // dummy hashed password
        $user->setRoles(['ROLE_ADMIN']);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function createCategory(string $name = 'Default Category'): Category
    {
        $category = new Category();
        $category->setName($name);

        $this->em->persist($category);
        $this->em->flush();

        return $category;
    }

    private function createProduct(User $user, Category $category = null, string $name = 'Test Product'): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setPrice(100);
        $product->setStock(10);
        $product->setUser($user);
        $product->setIsDeleted(false);

        if ($category) {
            $product->setCategory($category);
        }

        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    public function testIndexReturnsUserProducts(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);
        $this->createProduct($user);

        $this->client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString('Test Product', $this->client->getResponse()->getContent());
    }

    public function testShowReturnsProductForAuthorizedUser(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);
        $product = $this->createProduct($user);

        $this->client->request('GET', '/api/products/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertStringContainsString('Test Product', $this->client->getResponse()->getContent());
    }

    public function testCreateProductForAuthorizedUser(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);
        $category = $this->createCategory();

        $postData = [
            'name' => 'New Product',
            'price' => 149.99,
            'stock' => 20,
            'categoryId' => $category->getId(),
        ];

        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($postData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertEquals('Product created!', $json['data']['status']);
        $this->assertArrayHasKey('id', $json['data']);
    }

    public function testCreateProductWithNegativePrice(): void
    {
        $user = $this->createUser('invalid');
        $this->client->loginUser($user);
        $category = $this->createCategory('Invalid Category');

        $this->client->request(
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

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('price', $this->client->getResponse()->getContent());
    }

    public function testCreateProductWithNegativeStock(): void
    {
        $user = $this->createUser('stock');
        $this->client->loginUser($user);
        $category = $this->createCategory('Stock Test Category');

        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Invalid Stock Product',
                'price' => 100,
                'stock' => -5,
                'categoryId' => $category->getId()
            ])
        );

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('stock', $this->client->getResponse()->getContent());
    }

    public function testUpdateProductForAuthorizedUser(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory('Update Category');
        $product = $this->createProduct($user, $category, 'Old Product');

        $this->client->loginUser($user);

        $updatedData = [
            'name' => 'Updated Product',
            'price' => 199.99,
            'stock' => 15,
            'categoryId' => $category->getId(),
        ];

        $this->client->request(
            'PUT',
            '/api/products/' . $product->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedData)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Product updated!', json_decode($this->client->getResponse()->getContent(), true)['data']['status']);
    }

    public function testDeleteProductForAuthorizedUser(): void
    {
        $user = $this->createUser('delete');
        $category = $this->createCategory('Delete Category');
        $product = $this->createProduct($user, $category, 'Product To Delete');

        $this->client->loginUser($user);

        $this->client->request('DELETE', '/api/products/' . $product->getId());

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('Product deleted', json_decode($this->client->getResponse()->getContent(), true)['data']['status']);
    }

    public function testUserCannotEditProductCreatedByAnotherUser(): void
    {
        // User A creates a product
        $owner = $this->createUser('owner');
        $category = $this->createCategory();
        $product = $this->createProduct($owner, $category, 'Owner Product');

        // User B logs in and tries to edit User A's product
        $intruder = $this->createUser('intruder');
        $this->client->loginUser($intruder);

        $this->client->request(
            'PUT',
            '/api/products/' . $product->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Hacked Name',
                'price' => 99.99,
                'stock' => 50,
                'categoryId' => $category->getId()
            ])
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCannotCreateProductWithInvalidCategory(): void
    {
        $user = $this->createUser('user');
        $this->client->loginUser($user);

        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Some Product',
                'price' => 20.0,
                'stock' => 5,
                'categoryId' => 999999, // invalid category ID
            ])
        );

        $this->assertResponseStatusCodeSame(404); // or 400 depending on how you handle this
    }


}
