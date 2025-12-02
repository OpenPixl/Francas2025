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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class userEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email')
            ->add('typeuser', ChoiceType::class, [
                'choices'  => [
                    'administrateur' => "administrator",
                    'membre' => 'member',
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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => user::class,
        ]);
    }
}
