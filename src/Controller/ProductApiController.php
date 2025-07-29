<?php

namespace App\Controller;

use App\DTO\ProductDTO;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
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
    #[IsGranted('ROLE_ADMIN')]
    public function index(ProductRepository $productRepository): JsonResponse
    {
        $user = $this->getUser();
        $products = $productRepository->findBy([
            'user' => $user,
            'isDeleted' => false
        ]);

        $dtos = array_map(fn(Product $product) => new ProductDTO($product), $products);

        return $this->json($dtos);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Ensure the user owns the product
        if ($product->getUser() !== $this->getUser() || $product->isDeleted()) {
            return $this->json(['error' => 'Product not found or unauthorized'], 403);
        }

        return $this->json(new ProductDTO($product));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepo): JsonResponse
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
        $product->setIsDeleted(false);
        $product->setUser($this->getUser());

        // Optional: set category if provided
        if (isset($data['category_id'])) {
            $category = $categoryRepo->find($data['category_id']);
            if ($category && $category->getUser() === $this->getUser()) {
                $product->setCategory($category);
            } else {
                return $this->json(['error' => 'Invalid or unauthorized category'], 403);
            }
        }

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json([
            'status' => 'Product created!',
            'id' => $product->getId()
        ], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, Product $product, EntityManagerInterface $entityManager, CategoryRepository $categoryRepo): JsonResponse
    {
        if ($product->getUser() !== $this->getUser() || $product->isDeleted()) {
            return $this->json(['error' => 'Product not found or unauthorized'], 403);
        }

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

        // update category (this is not used)
        if (isset($data['category_id'])) {
            $category = $categoryRepo->find($data['category_id']);
            if ($category && $category->getUser() === $this->getUser()) {
                $product->setCategory($category);
            } else {
                return $this->json(['error' => 'Invalid or unauthorized category'], 403);
            }
        }

        $entityManager->flush();

        return $this->json(['status' => 'Product updated!']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($product->getUser() !== $this->getUser() || $product->isDeleted()) {
            return $this->json(['error' => 'Product not found or unauthorized'], 403);
        }

        $product->setIsDeleted(true); // Soft delete
        $entityManager->flush();

        return $this->json(['status' => 'Product soft-deleted']);
    }
}
