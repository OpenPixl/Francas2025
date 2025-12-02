<?php

namespace App\Form\Webapp;

use App\Entity\Admin\User;
use App\Entity\Admin\Message;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject')
            ->add('content', TextareaType::class,[
                'label'=> "Contenu"
            ])
            ->add('recipient',EntityType::class, [
                'label'=> 'Autres options de bien',
                'class' => User::class,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('d')
                    ->orderBy('d.firstName', 'ASC'),
                'choice_label' => 'firstName',
                'multiple' => true,
                'choice_attr' => fn(User $product, $key, $index) => ['data-data' => $product->getFirstName() ." ". $product->getLastName() ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}
