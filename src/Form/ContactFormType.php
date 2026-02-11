<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'attr' => ['placeholder' => 'NOM', 'class' => 'form-input']
            ])
            ->add('prenom', TextType::class, [
                'attr' => ['placeholder' => 'Prénom', 'class' => 'form-input']
            ])
            ->add('email', EmailType::class, [
                'attr' => ['placeholder' => 'Adresse Mail', 'class' => 'form-input full-width']
            ])

            # HoneyPot Field (Non visible, nécessaire pour la protection de spam)
            ->add('telephone_pro', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => false,
                'attr' => [
                    'style' => 'display:none !important;',
                    'tabindex' => '-1',
                    'autocomplete' => 'off'
                ]
            ])

            ->add('message', TextareaType::class, [
                'attr' => ['placeholder' => 'Message', 'class' => 'form-textarea']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
