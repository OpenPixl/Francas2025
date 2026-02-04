<?php

namespace App\Form\Webapp;

use App\Entity\Gestapp\Support;
use App\Entity\Gestapp\Theme;
use App\Entity\Webapp\Article;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ArticlesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title',TextType::class,[
                'label'=> 'titre',
            ])
            ->add('content',TextareaType::class,[
                'label'=> "Contenu de l'article",
                'required' => false
            ])
            ->add('theme',EntityType::class,[
                'class' => Theme::class,
                'placeholder' => '-- Choisir le thème --',
                'required' => false,
                'label'=> "Thème du projet",
            ])
            ->add('support',EntityType::class,[
                'class' => Support::class,
                'placeholder' => '-- Choisir le support --',
                'required' => false,
                'label'=> "Support du Projet",
            ])
            ->add('isShowReadMore', CheckboxType::class,[
                'label' => 'Afficher "Lire la suite"',
                'required' => false,
            ])
            ->add('isTitleShow', CheckboxType::class,[
                'label' => 'Afficher le titre',
                'required' => false,
            ])
            ->add('category')
            ->add('imageFile', FileType::class, [
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
            ->add('isSupprImage')
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
            ->add('isSupprDoc')
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
