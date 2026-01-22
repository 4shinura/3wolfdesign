<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control w-100'
                ]
            ])

            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label_attr' => [
                    'style' => 'margin: 0'
                ],
                'row_attr' => [
                    'class' => 'checkbox-row'
                ],
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter nos termes, ainsi être en accord avec nôtre CGU pour poursuivre la création de votre compte.'
                    ),
                ]
            ])

            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control w-100'
                    ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez entrer un mot de passe' 
                    ),
                    new Length(
                        min: 8,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
            'attr' => [
                'class' => 'form-container'
            ],
        ]);
    }
}
