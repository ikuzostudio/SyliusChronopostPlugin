<?php

declare(strict_types=1);

namespace Ikuzo\SyliusChronopostPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class CompanyType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('company', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.company',
                'required' => true
            ])
            ->add('firstname', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.firstname',
                'required' => false
            ])
            ->add('lastname', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.lastname',
                'required' => false
            ])
            ->add('address1', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.address1',
                'required' => true
            ])
            ->add('address2', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.address2',
                'required' => false
            ])
            ->add('city', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.city',
                'required' => true
            ])
            ->add('zipcode', TextType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.zipcode',
                'required' => true
            ])
            ->add('country', ChoiceType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.country',
                'required' => true,
                'choices' => [
                    'France' => 'FR',
                    'Guadeloupe' => 'GP',
                    'Guyane Française' => 'GF',
                    'Martinique' => 'MQ',
                    'Réunion, Île de la' => 'RE',
                    'Mayotte' => 'YT',
                    'Saint-Martin' => 'MF'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.email',
                'required' => false
            ])
            ->add('phone', TelType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.phone',
                'required' => false
            ])
            ->add('mobile', TelType::class, [
                'label' => 'ikuzo.ui.chronopost.shipping_address.mobile',
                'required' => false
            ])
        ;

    }
}
