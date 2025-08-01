<?php

namespace App\Controller;

use App\DTO\ApiResponseDTO;
use App\DTO\ProductDTO;
use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
class ProductApiController extends AbstractController
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findBy([
            'user' => $this->getUser(),
            'isDeleted' => false
        ]);

        $dtos = array_map(fn(Product $product) => new ProductDTO($product), $products);

        return $this->json(new ApiResponseDTO($dtos));
    }

    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Product $product): JsonResponse
    {
        if (!$this->isOwnedByCurrentUser($product) || $product->isDeleted()) {
            return $this->unauthorizedResponse();
        }

        return $this->json(new ApiResponseDTO(new ProductDTO($product)));
    }

        
    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Request            $request,
        CategoryRepository $categoryRepo,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(new ApiResponseDTO(['error' => 'Invalid JSON']), Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['name'], $data['price'], $data['stock'], $data['category_id'])) {
            return $this->json(new ApiResponseDTO(['error' => 'Missing required fields: name, price, stock, category_id']), Response::HTTP_BAD_REQUEST);
        }

        $category = $categoryRepo->find($data['category_id']);
        if (!$category || !$this->isOwnedByCurrentUser($category)) {
            return $this->unauthorizedResponse('Invalid or unauthorized category');
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($data['price']);
        $product->setStock($data['stock']);
        $product->setIsDeleted(false);
        $product->setUser($this->getUser());
        $product->setCategory($category);

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(new ApiResponseDTO(['errors' => $errorMessages]), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json(new ApiResponseDTO([
            'status' => 'Product created!',
            'id' => $product->getId()
        ]), Response::HTTP_CREATED);
    }


    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Request            $request,
        Product            $product,
        CategoryRepository $categoryRepo,
        ValidatorInterface $validator
    ): JsonResponse
    {
        if (!$this->isOwnedByCurrentUser($product) || $product->isDeleted()) {
            return $this->unauthorizedResponse();
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(new ApiResponseDTO(['error' => 'Invalid JSON']), 400);
        }

        if (!isset($data['name'], $data['price'], $data['stock'])) {
            return $this->json(new ApiResponseDTO(['error' => 'Missing required fields: name, price, stock']), 400);
        }

        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice($data['price']);
        $product->setStock($data['stock']);

        if (isset($data['category_id'])) {
            $category = $categoryRepo->find($data['category_id']);
            if (!$category || !$this->isOwnedByCurrentUser($category)) {
                return $this->unauthorizedResponse('Invalid or unauthorized category');
            }
            $product->setCategory($category);
        }

        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(new ApiResponseDTO(['errors' => $errorMessages]), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->entityManager->flush();

        return $this->json(new ApiResponseDTO(['status' => 'Product updated!']));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Product $product): JsonResponse
    {
        if (!$this->isOwnedByCurrentUser($product) || $product->isDeleted()) {
            return $this->unauthorizedResponse();
        }

        $product->setIsDeleted(true);
        $this->entityManager->flush();

        return $this->json(new ApiResponseDTO(['status' => 'Product deleted']));
    }

    // 🔐 PROTECTED UTILITY METHODS

    protected function isOwnedByCurrentUser($entity): bool
    {
        return method_exists($entity, 'getUser') && $entity->getUser() === $this->getUser();
    }

    protected function unauthorizedResponse(string $message = 'Product not found or unauthorized'): JsonResponse
    {
        return $this->json(new ApiResponseDTO(['error' => $message]), 403);
    }
}
