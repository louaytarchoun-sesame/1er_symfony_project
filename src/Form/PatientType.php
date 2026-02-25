<?php
namespace App\Form;

use App\Entity\Patient;
use App\Entity\Profil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PatientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateInscription', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date d\'inscription',
            ])
                ->add('name', TextType::class, [
                'label' => 'Nom',
            ])
                ->add('last_name', TextType::class, [
                'label' => 'Prénom',
            ])
                ->add('cin', TextType::class, [
                'label' => 'CIN',
            ])
                ->add('image', TextType::class, [
                'label' => 'Image',
                'required' => false,
            ])
                ->add('tel', TextType::class, [
                'label' => 'Téléphone',
            ])
                ->add('sexe', TextType::class, [
                'label' => 'Sexe',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Patient::class,
        ]);
    }
}
