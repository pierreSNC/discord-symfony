<?php

namespace App\Form;

use App\Model\SearchData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options); // TODO: Change the autogenerated stub
        $builder
            ->add('q', TextType::class, [
                'attr' => [
                    'placeholder' => 'Recherche...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver); // TODO: Change the autogenerated stub
        $resolver->setDefaults([
            "data_class" => SearchData::class,
            'method' => 'GET',
            'csrf_protection' => false

        ]);
    }
}