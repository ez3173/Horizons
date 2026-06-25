<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Journey;
use App\Form\StepType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JourneyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title',null,[
                'label' => 'titre du carnet',
            ])
            ->add('description',TextareaType::class,[
                'label'=>'Description',
                'attr'=>['rows'=>5],
            ])
            ->add('coverImageFile',FileType::class,[
                'label' => 'image de couverture',
                'mapped' => false,
                'required'=>false,
                'constraints'=> [
                    new File(
                        maxSize: '3M',
                        mimeTypes:[
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                    mimeTypesMessage: 'Merci d\'uploader une image valide (JPEG, PNG ou WEBP)',    
                    )
                ]
            ])
            ->add('budget',IntegerType::class,[
                'label'=> 'Budget estimé (€)'
            ])
            ->add('duration',IntegerType::class,[
                'label'=> 'Durée (Jours)'
            ])
            
         
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder'=> 'Choisir une catégorie',
            ])
            ->add('steps', CollectionType::class, [
                'entry_type' => StepType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Étapes du voyage',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Journey::class,
        ]);
    }
}
