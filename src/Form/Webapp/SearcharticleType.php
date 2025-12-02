<?php

namespace App\Form\Webapp;

use App\Entity\Admin\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearcharticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', SearchType::class,[
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'placeholder' => 'Rechercher'
                ]
            ])
            ->add('author', EntityType::class, [
                'class' => User::class,
                'label' => 'author',
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'placeholder' => 'Auteur'
                ],
                'required' => false
            ])
            ->add('go', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary btn-sm'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
