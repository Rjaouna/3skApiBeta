<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\UserFamilyDiscount;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFamilyDiscountType extends AbstractType
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'label' => 'Utilisateur',
                'placeholder' => 'Sélectionner un utilisateur',
                'choice_label' => function (User $user): string {
                    $label = (string) $user->getEmail();

                    if (method_exists($user, 'getCompanyName') && $user->getCompanyName()) {
                        $label .= ' — ' . $user->getCompanyName();
                    }

                    return $label;
                },
                'query_builder' => fn(UserRepository $repo) => $repo
                    ->createQueryBuilder('u')
                    ->orderBy('u.email', 'ASC'),
                'attr' => [
                    'class' => 'form-select ufd-form-select',
                ],
            ])
            ->add('familyName', TextType::class, [
                'label' => 'Catégorie / famille',
                'required' => true,
                'attr' => [
                    'class' => 'form-control ufd-form-control js-family-input',
                    'autocomplete' => 'off',
                    'placeholder' => 'Commencez à taper une catégorie…',
                    'spellcheck' => 'false',
                ],
                'help' => 'Tapez quelques lettres pour afficher jusqu’à 3 catégories existantes.',
            ])
            ->add('discountRate', NumberType::class, [
                'label' => 'Remise (%)',
                'required' => true,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control ufd-form-control',
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.01,
                    'placeholder' => '0.00',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserFamilyDiscount::class,
        ]);
    }
}