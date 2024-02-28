<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Message;
use App\Repository\SubCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MessageType extends AbstractType
{

    private $subCategoryRepository;
    public function __construct(private EntityManagerInterface $manager, SubCategoryRepository $subCategoryRepository)
    {
        $this->subCategoryRepository = $subCategoryRepository;

    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $subCategories = $this->subCategoryRepository->findAll();

        $subCategoryChoices = [];
        foreach ($subCategories as $subCategory) {
            $subCategoryChoices[$subCategory->getName()] = $subCategory->getName();
        }

        $builder
            ->add('pseudo', TextType::class)
            ->add('content', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Envoyer un message',
                ],
            ])
//            ->add('category')
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                $categories = $this->manager->getRepository(Category::class)->findAll();

                $form->add('category', ChoiceType::class, [
                    'choices' => $categories,
//                 array(
//                    'data' => 'PHP',
//                 ),
//                 'data' => 'PHP',
                    'empty_data' => '',
                    'choice_value' => function ($category) {
                        return $category ;
                    },
                    // a callback to return the label for a given choice
                    // if a placeholder is used, its empty value (null) may be passed but
                    // its label is defined by its own "placeholder" option
                    'choice_label' => function (?Category $category) {
                        return $category ?$category->getName(): '';
                    },
                    'label' => 'Catégorie : ',
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ]);

            })
            ->add('file', FileType::class, [
                'label' => 'Upload files',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,
                'empty_data' => '',

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
//                        'maxSize' => '1024k',
//                        'mimeTypes' => [
//                            'application/pdf',
//                            'application/x-pdf',
//                        ],
                        'mimeTypesMessage' => 'Please upload a valid image document',
                    ])
                ],
            ])
            ->add('subCategory', ChoiceType::class, [
                'choices' => $subCategoryChoices,
                'choice_label' => function ($value, $key, $index) {
                    return $key;
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}
