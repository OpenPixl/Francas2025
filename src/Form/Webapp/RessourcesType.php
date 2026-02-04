<?php

namespace App\Form\Webapp;

use App\Entity\Gestapp\RessourceCat;
use App\Entity\Gestapp\Ressources;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\Form\Type\VichImageType;

class RessourcesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('content')
            ->add('category',EntityType::class,[
                'class' => RessourceCat::class,
                'placeholder' => '-- Choisir le thème --',
                'required' => false,
                'label'=> "Thème du projet",
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Banniere au format : mp4',
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
            ->add('docFile', FileType::class, [
                'label' => 'Banniere au format : mp4',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10000k',
                        'mimeTypes' => [
                            'video/mp4',
                            'video/mpeg',
                        ],
                        'mimeTypesMessage' => 'Attention, veuillez charger un fichier au format mp4.',
                    ])
                ],
            ])
            ->add('Linkmedia', TextType::class, [
                'label' => 'Lien du media',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ressources::class,
        ]);
    }
}
