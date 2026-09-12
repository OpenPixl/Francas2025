<?php

namespace App\Form\Admin;

use App\Entity\Admin\Etablissement;
use App\Entity\Admin\TypeEtablissement;
use App\Entity\Admin\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class EtablissementEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('address')
            ->add('complement')
            ->add('zipcode')
            ->add('city')
            ->add('etablissementEmail')
            ->add('groupEmail')
            ->add('etablissementPhone')
            ->add('groupPhone')
            ->add('animateur')
            ->add('GroupDescription')
            ->add('workMeeting')
            ->add('typeEtablissement', EntityType::class, [
                'class' => TypeEtablissement::class,
                'choice_label' => 'libelle',
                'label' => 'Type d\'établissement',
                'placeholder' => '-- Choisir le type --',
            ])
            ->add('headerFile', FileType::class, [
                'label' => 'Banniere au format : png ou jpg',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '100000k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpg',
                            'image/jpeg'
                        ],
                        'mimeTypesMessage' => 'Attention, veuillez charger un fichier au format jpg ou png',
                    ])
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'vignette au format : jpg ou png',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '100000k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpg',
                            'image/jpeg'
                        ],
                        'mimeTypesMessage' => 'Attention, veuillez charger un fichier au format jpg ou png',
                    ])
                ],
            ])
            ->add('user', EntityType::class, [
                'label' => 'anime l\'établissement',
                'class' => User::class,
                'placeholder' => '-- Choisir l\'administrateur --',
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $etablissement = $options['data'] ?? null;
                    $currentUser = $etablissement instanceof Etablissement ? $etablissement->getUser() : null;

                    $qb = $er->createQueryBuilder('u')
                        ->where('u.isActiv = :isActiv')
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('isActiv', 1)
                        ->setParameter('role', '%"ROLE_ETABLISSEMENT"%')
                        ->orderBy('u.id', 'ASC');

                    // L'utilisateur déjà lié doit rester sélectionnable même s'il n'a pas
                    // (ou plus) le statut actif, sinon le champ ne peut pas l'afficher/le
                    // conserver comme valeur pré-remplie.
                    if ($currentUser) {
                        $qb->orWhere('u.id = :currentUserId')
                            ->setParameter('currentUserId', $currentUser->getId());
                    }

                    return $qb;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Etablissement::class,
        ]);
    }
}
