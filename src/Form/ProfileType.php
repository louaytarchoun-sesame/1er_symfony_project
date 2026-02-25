<?php

namespace App\Form;

use App\Entity\Profil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfilType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('cin', TextType::class, ['label' => 'CIN'])
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('last_name', TextType::class, ['label' => 'Prénom'])
            ->add('role', TextType::class, ['label' => 'Rôle'])
            ->add('image', TextType::class, ['label' => 'Image', 'required' => false])
            ->add('tel', TextType::class, ['label' => 'Téléphone'])
            ->add('sexe', TextType::class, ['label' => 'Sexe']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Profil::class,
        ]);
    }
}