<?php

namespace App\Form\Admin;

use App\Entity\Admin\User;
use App\Entity\Admin\Message;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('subject')
            ->add('content', TextareaType::class,[
                'label'=> "Contenu",
                'data' => "..."
            ])
            ->add('recipient',EntityType::class, [
                'class' => User::class,
                'label' => 'Equipement du bien',
                'multiple' => true,
                'choice_attr' => ChoiceList::attr($this, fn(?User $user) => $user ? ['data-data' => $user->getfirstName().' '.$user->getLastName()] : [])
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
