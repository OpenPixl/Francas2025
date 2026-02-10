<?php

namespace App\Form\Webapp;

use App\Entity\Gestapp\Support;
use App\Entity\Gestapp\Theme;
use App\Entity\Webapp\Article;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class Articles2Type extends AbstractType
{
    private function getDocConstraintsBySupportId(?int $supportId): array
    {
        return match ($supportId) {
            1 => [ // audio
                new File([
                    'maxSize' => '100000k',
                    'mimeTypes' => ['audio/mpeg', 'audio/wav'],
                    'mimeTypesMessage' => 'Attention, veuillez charger un fichier au format mp3.',
                ])
            ],
            2 => [ // vidéo
                new File([
                    'maxSize' => '100000k',
                    'mimeTypes' => [
                        'video/mp4',
                        'video/mpeg',
                    ],
                    'mimeTypesMessage' => 'Attention, veuillez charger un fichier au format mp4.',
                ])
            ],
            3 => [ // document
                new File([
                    'maxSize' => '10000k',
                    'mimeTypes' => [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ],
                    'mimeTypesMessage' => 'Veuillez charger un document PDF ou Word',
                ])
            ],
            default => [],
        };
    }


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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $article = $event->getData();
            $form = $event->getForm();

            if (!$article) {
                return;
            }

            $support = $article->getSupport();
            $supportId = $support?->getId();

            $form->add('docFile', FileType::class, [
                'label' => 'Fichier associé',
                'mapped' => false,
                'required' => false,
                'constraints' => $this->getDocConstraintsBySupportId($supportId),
            ]);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if (!isset($data['support'])) {
                return;
            }

            $supportId = (int) $data['support'];

            $form->add('docFile', FileType::class, [
                'label' => 'Fichier associé',
                'mapped' => false,
                'required' => false,
                'constraints' => $this->getDocConstraintsBySupportId($supportId),
            ]);
        });

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
