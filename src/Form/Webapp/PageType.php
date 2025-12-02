<?php

namespace App\Form\Webapp;

use App\Entity\Webapp\Page;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title')
            ->add('intro', TextareaType::class)
            ->add('state', ChoiceType::class, [
                'choices'  => [
                    'Brouillon' => 'draft',
                    'Finalisée' => 'finished',
                ],
            ])
            ->add('metaKeywords')
            ->add('publishAt', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                // this is actually the default format for single_text
                'format' => 'dd-MM-yyyy',
                'attr' => ['class' => 'js-datepicker form-control form-control-sm'],
            ])
            ->add('publishEnd', DateType::class, [
                'widget' => 'single_text',
                'html5' => false,
                // this is actually the default format for single_text
                'format' => 'dd-MM-yyyy',
                'attr' => ['class' => 'js-datepicker'],
            ])
            ->add('isPublish', CheckboxType::class, [
                'label' => 'Publier l\'article ?',
                'required' => false,
            ])
            ->add('isMenu', CheckboxType::class, [
                'label' => 'Faire de la page un menu ?',
                'required' => false,
            ])
            ->add('isTitleShow', CheckboxType::class, [
                'label' => 'Afficher le titre ?',
                'required' => false,
            ])
            ->add('isIntroShow', CheckboxType::class, [
                'label' => 'Afficher l\'intro',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
            'translation_domain' => 'page'
        ]);
    }
}
