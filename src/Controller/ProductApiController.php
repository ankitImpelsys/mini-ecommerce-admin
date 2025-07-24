<?php

namespace App\Controller;

use App\DTO\ProductDTO;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/products')]
final class ProductApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function index(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();

        $dtos = array_map(fn(Product $product) => new ProductDTO($product), $products);

        return $this->json($dtos);
    }


    #[Route('/{id}', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json(new ProductDTO($product));
    }


    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        if (!isset($data['name'], $data['price'], $data['stock'])) {
            return $this->json(['error' => 'Missing required fields: name, price, stock'], 400);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($data['price']);
        $product->setStock($data['stock']);

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'status' => 'Product created!',
            'id' => $product->getId()
        ], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        if (!isset($data['name'], $data['price'], $data['stock'])) {
            return $this->json(['error' => 'Missing required fields: name, price, stock'], 400);
        }

        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($data['price']);
        $product->setStock($data['stock']);

        $entityManager->flush();

        return $this->json(['status' => 'Product updated!']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json(['status' => 'Product deleted']);
    }
}

