<?php

declare(strict_types=1);

namespace App\Form\Search;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', TextType::class, [
                'required' => false,
                'label' => 'Rechercher un média',
                //'attr' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Rechercher un bénéficiaire',]
            ])
            ->add('etablissementChoice', ChoiceType::class, [
                'required' => false,
                'label' => "Type d'établissement",
                'choices' => $options['etablissementsChoices'],
                'placeholder' => 'Tous',
                //'attr' => ['class' => 'form-select form-select-sm']
            ])
            ->add('theme', ChoiceType::class, [
                'required' => false,
                'label' => 'Thème',
                'choices' => $options['themesChoices'],
                'placeholder' => 'Tous',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'etablissementsChoices' => [],
            'themesChoices' => [],
        ]);
    }
}
