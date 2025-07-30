<?php

namespace App\Tests\Controller;

use App\Controller\ProductApiController;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductApiControllerTest extends TestCase
{
    public function testIndexReturnsJsonResponseWithUserProducts(): void
    {
        // Mock user
        $user = $this->createMock(UserInterface::class);

        // Mock category 1
        $category1 = $this->createMock(Category::class);
        $category1->method('getId')->willReturn(1);

        // Mock product 1
        $product1 = $this->createMock(Product::class);
        $product1->method('getName')->willReturn('Product A');
        $product1->method('getPrice')->willReturn(100.0);
        $product1->method('getStock')->willReturn(10);
        $product1->method('getDescription')->willReturn('Description A');
        $product1->method('getCategory')->willReturn($category1);

        // Mock category 2
        $category2 = $this->createMock(Category::class);
        $category2->method('getId')->willReturn(2);

        // Mock product 2
        $product2 = $this->createMock(Product::class);
        $product2->method('getName')->willReturn('Product B');
        $product2->method('getPrice')->willReturn(200.0);
        $product2->method('getStock')->willReturn(5);
        $product2->method('getDescription')->willReturn('Description B');
        $product2->method('getCategory')->willReturn($category2);

        // Mock product repository
        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user, 'isDeleted' => false])
            ->willReturn([$product1, $product2]);

        // Mock controller and override getUser() and json()
        $controller = $this->getMockBuilder(ProductApiController::class)
            ->onlyMethods(['getUser', 'json'])
            ->disableOriginalConstructor()
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('json')->willReturnCallback(function ($data) {
            return new JsonResponse($data);
        });

        // Call controller method
        $response = $controller->index($productRepository);

        // Assert JSON response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']);
    }

    public function testIndexReturnsEmptyArrayIfNoProductsFound(): void
    {
        $user = $this->createMock(UserInterface::class);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user, 'isDeleted' => false])
            ->willReturn([]);

        $controller = $this->getMockBuilder(ProductApiController::class)
            ->onlyMethods(['getUser', 'json'])
            ->disableOriginalConstructor()
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('json')->willReturnCallback(function ($data) {
            return new JsonResponse($data);
        });

        $response = $controller->index($productRepository);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertCount(0, $data['data']);
    }
}
