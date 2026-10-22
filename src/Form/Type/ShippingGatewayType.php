<?php

declare(strict_types=1);

namespace Ikuzo\SyliusChronopostPlugin\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
        'CHRONORDV',
        'CHRONOFRESH13',
        'CHRONOFRESH18',
        'CHRONOFREEZE13',
        'SHOPTOSHOP'
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
            ->add('subAccount', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.sub_account',
                'required' => false
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
            ->add('weight_unit', ChoiceType::class, [
                'label' => 'ikuzo.ui.chronopost.weight_unit',
                'required' => true,
                'choices' => [
                    'Grammes' => 'g',
                    'Kilogrammes' => 'kg',
                ],
            ])
            ->add('recipientPreAlert', CheckboxType::class, [
                'label' => 'ikuzo.ui.chronopost.recipient_pre_alert',
                'required' => false,
            ])
            ->add('fresh_expiration_days', IntegerType::class, [
                'label' => 'ikuzo.ui.chronopost.fresh_expiration_days',
                'required' => false,
            ])
        ;

        $choices = [];
        foreach ($this->em->getRepository(ShippingMethod::class)->findBy(['enabled' => true]) as $shippingMethod) {
            try {
                $translation = $shippingMethod->getTranslations()->first();
            } catch (\TypeError) {
                $translation = false;
            }

            $name = $translation ? $translation->getName() : null;
            $label = $name ? sprintf('%s (%s)', $name, $shippingMethod->getCode()) : $shippingMethod->getCode();

            $choices[$label] = $shippingMethod->getId();
        }

        foreach (self::$products as $product) {
            $builder->add('product_'.$product, ChoiceType::class, [
                'label' => 'ikuzo.ui.chronopost.products.'.$product,
                'choices' => $choices,
                'multiple' => true,
                'required' => false,
            ]);
        }
    }
}
