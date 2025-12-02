<?php

namespace App\Form\Admin;

use App\Entity\Admin\College;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichImageType;

class CollegeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('address')
            ->add('complement')
            ->add('zipcode')
            ->add('city')
            ->add('collegeEmail')
            ->add('groupEmail')
            ->add('collegePhone')
            ->add('groupPhone')
            ->add('animateur')
            ->add('GroupDescription')
            ->add('workMeeting')
            ->add('headerFile', FileType::class, [
                'label' => 'Banniere au format : png ou jpg',
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
            // ...
            ->add('logoFile', FileType::class, [
                'label' => 'vignette au format : jpg ou png',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10000k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Attention, veuillez charger un fichier au format jpg ou png',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => College::class,
        ]);
    }
}
