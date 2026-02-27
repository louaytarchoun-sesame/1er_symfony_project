<?php

namespace App\Form;

use App\Entity\Medecin;
use App\Entity\Specialite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MedecinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateEmbauche', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => "Date d'embauche",
            ])
            ->add('specialite', EntityType::class, [
                'class' => Specialite::class,
                'choice_label' => 'labelle',
                'label' => 'Spécialité',
            ])
            ->add('profile', ProfilType::class, [
                'label' => false, // Pas de label, c’est un sous-formulaire
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Medecin::class,
        ]);
    }
}