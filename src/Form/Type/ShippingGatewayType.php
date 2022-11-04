<?php

declare(strict_types=1);

namespace Ikuzo\SyliusChronopostPlugin\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Sylius\Component\Core\Model\ShippingMethod;

final class ShippingGatewayType extends AbstractType
{
    static array $products = [
        'CHRONO10',
        'CHRONO13',
        'CHRONO18',
        'CHRONORELAIS',
        'CHRONOCLASSIC',
        'CHRONOEXPRESS',
        'RELAISEUROPE',
        'RELAISDOM',
        'SAMEDAY',
        'CHRONORDV'
    ];

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contractNumber', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.username',
                'required' => true
            ])
            ->add('password', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.password',
                'required' => true,
            ])
            ->add('expeditorAddress', CompanyType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.label'
            ])
            ->add('billingAddress', CompanyType::class, [
                'label' => 'ikuzo.ui.chronopost.billing_address.label'
            ])
            ->add('returnAddress', CompanyType::class, [
                'label' => 'ikuzo.ui.chronopost.return_address.label'
            ])

            ->add('print_mode', ChoiceType::class, [
                'label' => 'ikuzo.ui.chronopost.print_mode',
                'required' => true,
                'choices' => [
                    'Fichier PDF' => 'PDF',
                    'Imprimante thermique' => 'THE',
                    'Format PDF sans preuve de dépôt' => 'SPD'
                ]
            ])
        ;

        foreach (self::$products as $product) {
            foreach ($this->em->getRepository(ShippingMethod::class)->findAll() as $shippingMethod) {
                $choices[$shippingMethod->getCode()] = $shippingMethod->getId();
            }

            $builder->add('product_'.$product, ChoiceType::class, [
                'label' => 'ikuzo.ui.chronopost.products.'.$product,
                'choices' => $choices,
                'multiple' => true,
                'required' => false,
            ]);
        }
    }
}
