<?php

namespace App\Form\Admin;

use App\Entity\Admin\College;
use App\Entity\Admin\user;
use phpDocumentor\Reflection\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class userType extends AbstractType
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $request = $this->requestStack->getCurrentRequest();
        $route = $request?->attributes->get('_route');

        $builder
            ->add('email')
            ->add('typeuser', ChoiceType::class, [
                'choices'  => [
                    'College' => 'college',
                    'Administrateur' => "administrator",
                ],
            ])
            ->add('firstName')
            ->add('lastName')
            ->add('avatarFile', FileType::class, [
                'label' => 'Avatar au format : png ou jpg',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10000k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                            'image/jpg'
                        ],
                        'mimeTypesMessage' => 'Attention, veuillez charger un fichier au format jpg ou png',
                    ])
                ],
            ])
            ->add('adress1')
            ->add('adress2')
            ->add('zipcode')
            ->add('city')
            ->add('phoneDesk')
            ->add('phoneGsm')

        ;

        if ($route == 'op_admin_user_new') {
            $builder
                ->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'first_options' => [
                        'constraints' => [
                            new NotBlank([
                                'message' => 'entrez un mot de passe',
                            ]),
                            new Length([
                                'min' => 8,
                                'minMessage' => 'Your password should be at least {{ limit }} characters',
                                // max length allowed by Symfony for security reasons
                                'max' => 4096,
                            ]),
                        ],
                        'label' => 'Nouveau password',
                    ],
                    'second_options' => [
                        'label' => 'Répétez le Password',
                    ],
                    'invalid_message' => 'The password fields must match.',
                    // Instead of being set onto the object directly,
                    // this is read and encoded in the controller
                    'mapped' => true,
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => user::class,
        ]);
    }
}
