<?php

namespace App\Form\Webapp;

use App\Entity\Gestapp\RessourceCat;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RessourceCatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('parent', EntityType::class, [
                'class' => RessourceCat::class,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('r')
                    ->orderBy('r.id', 'ASC'),
                'choice_label' => 'name',
                'mapped'=> false
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RessourceCat::class,
        ]);
    }
}
