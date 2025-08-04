<?php

namespace App\Controller;

use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAllByUser($this->getUser()),
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order, [
            'current_user' => $this->getUser(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get product quantities from the form
            $productQuantities = $request->request->all('product_quantities') ?? [];

            // Set default quantity of 1 for products without specified quantities
            foreach ($order->getProducts() as $product) {
                if (!isset($productQuantities[$product->getId()])) {
                    $productQuantities[$product->getId()] = 1;
                }
            }

            // Validate stock availability
            $stockErrors = [];
            foreach ($order->getProducts() as $product) {
                $productId = $product->getId();
                $requestedQuantity = (int)$productQuantities[$productId];

                if ($product->getStock() < $requestedQuantity) {
                    $stockErrors[] = sprintf(
                        'Not enough stock for %s. Available: %d, Requested: %d',
                        $product->getName(),
                        $product->getStock(),
                        $requestedQuantity
                    );
                }
            }

            if (!empty($stockErrors)) {
                foreach ($stockErrors as $error) {
                    $this->addFlash('error', $error);
                }

                return $this->render('order/new.html.twig', [
                    'order' => $order,
                    'form' => $form,
                    'product_quantities' => $productQuantities,
                ]);
            }

            // Set order properties
            $order->setUser($this->getUser());
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setStatus('pending');

            // Update stock
            foreach ($order->getProducts() as $product) {
                $productId = $product->getId();
                $quantity = (int)$productQuantities[$productId];
                $currentStock = $product->getStock();
                $product->setStock($currentStock - $quantity);
            }

            // Persist order
            $entityManager->persist($order);
            $entityManager->flush();

            // Store quantities in session for display purposes
            $session = $request->getSession();
            $session->set('order_quantities_' . $order->getId(), $productQuantities);

            $this->addFlash('success', 'Order created successfully.');

            return $this->redirectToRoute('app_order_index');
        }

        return $this->render('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
            'product_quantities' => [],
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order, EntityManagerInterface $entityManager, Request $request): Response
    {
        // Eagerly load products with their categories
        $orderWithProducts = $entityManager
            ->getRepository(Order::class)
            ->createQueryBuilder('o')
            ->leftJoin('o.products', 'p')
            ->leftJoin('p.category', 'c')
            ->addSelect('p', 'c')
            ->where('o.id = :orderId')
            ->setParameter('orderId', $order->getId())
            ->getQuery()
            ->getOneOrNullResult();

        $order = $orderWithProducts ?? $order;

        // Get stored quantities
        $session = $request->getSession();
        $quantities = $session->get('order_quantities_' . $order->getId(), []);

        // Calculate order details
        $orderItems = [];
        $totalItems = 0;
        $totalAmount = 0.0;

        foreach ($order->getProducts() as $product) {
            $quantity = $quantities[$product->getId()] ?? 1;
            $subtotal = $product->getPrice() * $quantity;

            $orderItems[] = [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];

            $totalItems += $quantity;
            $totalAmount += $subtotal;
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
            'order_items' => $orderItems,
            'total_items' => $totalItems,
            'total_amount' => $totalAmount,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        // Get stored original quantities
        $session = $request->getSession();
        $originalQuantities = $session->get('order_quantities_' . $order->getId(), []);
        $originalProducts = $order->getProducts()->toArray();

        $form = $this->createForm(OrderType::class, $order, [
            'current_user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get new product quantities
            $newQuantities = $request->request->all('product_quantities') ?? [];

            // Set default quantity of 1 for products without specified quantities
            foreach ($order->getProducts() as $product) {
                if (!isset($newQuantities[$product->getId()])) {
                    $newQuantities[$product->getId()] = 1;
                }
            }

            // First, restore stock for originally ordered products
            foreach ($originalProducts as $product) {
                $productId = $product->getId();
                $originalQuantity = $originalQuantities[$productId] ?? 1;
                $currentStock = $product->getStock();
                $product->setStock($currentStock + $originalQuantity);
            }

            // Validate stock availability for new quantities
            $stockErrors = [];
            foreach ($order->getProducts() as $product) {
                $productId = $product->getId();
                $requestedQuantity = (int)$newQuantities[$productId];

                if ($product->getStock() < $requestedQuantity) {
                    $stockErrors[] = sprintf(
                        'Not enough stock for %s. Available: %d, Requested: %d',
                        $product->getName(),
                        $product->getStock(),
                        $requestedQuantity
                    );
                }
            }

            if (!empty($stockErrors)) {
                // Re-reduce stock for original products if validation fails
                foreach ($originalProducts as $product) {
                    $productId = $product->getId();
                    $originalQuantity = $originalQuantities[$productId] ?? 1;
                    $currentStock = $product->getStock();
                    $product->setStock($currentStock - $originalQuantity);
                }

                foreach ($stockErrors as $error) {
                    $this->addFlash('error', $error);
                }

                return $this->render('order/edit.html.twig', [
                    'order' => $order,
                    'form' => $form,
                    'product_quantities' => $originalQuantities,
                ]);
            }

            // Update stock with new quantities
            foreach ($order->getProducts() as $product) {
                $productId = $product->getId();
                $quantity = (int)$newQuantities[$productId];
                $currentStock = $product->getStock();
                $product->setStock($currentStock - $quantity);
            }

            // Update stored quantities
            $session->set('order_quantities_' . $order->getId(), $newQuantities);

            $entityManager->flush();

            $this->addFlash('success', 'Order updated successfully.');

            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
            'product_quantities' => $originalQuantities,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->getPayload()->getString('_token'))) {

            // Check if products are still available
            foreach ($order->getProducts() as $product) {
                if ($product->isDeleted()) {
                    $this->addFlash('error', 'Cannot delete order: Product '.$product->getName().' has been deleted from inventory.');
                    return $this->redirectToRoute('app_order_index');
                }
            }

            // Restore stock using stored quantities
            $session = $request->getSession();
            $quantities = $session->get('order_quantities_' . $order->getId(), []);

            foreach ($order->getProducts() as $product) {
                $productId = $product->getId();
                $quantity = $quantities[$productId] ?? 1;
                $currentStock = $product->getStock();
                $product->setStock($currentStock + $quantity);
            }

            // Clear stored quantities
            $session->remove('order_quantities_' . $order->getId());

            $entityManager->remove($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order deleted successfully.');
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }
}
