<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['current_user'];

        $builder
            ->add('customerName', TextType::class, [
                'label' => 'Customer Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter customer name'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Order Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Processing' => 'processing',
                    'Shipped' => 'shipped',
                    'Delivered' => 'delivered',
                    'Cancelled' => 'cancelled'
                ],
                'placeholder' => 'Choose status...',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('createdAt', DateTimeType::class, [
                'label' => 'Order Date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('products', EntityType::class, [
                'class' => Product::class,
                'choice_label' => function (Product $product) {
                    return $product->getName() . ' - $' . number_format($product->getPrice(), 2) . ' (Stock: ' . $product->getStock() . ')';
                },
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-select product-selector',
                    'multiple' => true,
                    'data-placeholder' => 'Search and select products...'
                ],
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($user) {
                    return $er->createQueryBuilder('p')
                        ->where('p.user = :user')
                        ->andWhere('p.isDeleted = false')
                        ->andWhere('p.stock > 0')
                        ->orderBy('p.name', 'ASC')
                        ->setParameter('user', $user);
                },
                'choice_attr' => function (Product $product) {
                    return [
                        'data-price' => $product->getPrice(),
                        'data-stock' => $product->getStock(),
                        'data-category' => $product->getCategory() ? $product->getCategory()->getName() : 'No Category',
                        'data-image' => $product->getImageFilename() ?? 'default.jpg'
                    ];
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'current_user' => null,
        ]);
    }
}
