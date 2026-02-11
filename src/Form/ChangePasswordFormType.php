<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // On ajoute le champ 'oldPassword' que si ce n'est pas un mot de passe oublié (demande de reinitialisation)
        if (!$options['is_forgotten']) {
            $builder->add('oldPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre mot de passe actuel'),
                ],
                'attr' => ['class' => 'form-control']
            ]);
        }

        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control w-100'
                    ],
                ],
                'first_options' => [
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez entrer un mot de passe' 
                        ),
                        new Length(
                            min: 8,
                            minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                            max: 4096,
                        ),

                        // Force un mot de passe pas trop simple
                        new PasswordStrength(
                            minScore: PasswordStrength::STRENGTH_WEAK,
                            message: "Veuillez choisir un mot de passe plus complexe"
                        ), 
                    ],
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'label' => 'Répétez le mot de passe',
                ],
                'invalid_message' => 'Les champs du nouveau mot de passe ne corresponde pas',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // On définit la valeur par défaut à false
            'is_forgotten' => false,
            'attr' => [
                'class' => 'form-container'
            ]
        ]);

        // On précise que cette option doit être un booléen
        $resolver->setAllowedTypes('is_forgotten', 'bool');
    }
}
