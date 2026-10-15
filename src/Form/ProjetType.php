<?php

namespace App\Form;

use App\Entity\Projet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;

class ProjetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du projet',
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Web' => 'web',
                    'Desktop' => 'desktop',
                    'IoT' => 'iot',
                ],
            ])
            ->add('technologies', TextType::class, [
                'label' => 'Stack technique',
                'required' => false,
                'help' => 'Ex. Symfony, MySQL, Docker',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 6],
            ])
            ->add('lien', TextType::class, [
                'label' => 'Lien du projet (facultatif)',
                'required' => false,
            ])
            ->add('fichiers', FileType::class, [
                'label' => 'Photos et vidéos',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => ['accept' => 'image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/ogg,video/quicktime'],
                'help' => 'Jusqu’à 12 fichiers, 50 Mo par fichier. Les fichiers sont rangés dans medias/<nom-du-projet>.',
                'constraints' => [
                    new Count(max: 12, maxMessage: 'Vous pouvez envoyer au maximum {{ limit }} fichiers à la fois.'),
                    new All([
                        new File(
                            maxSize: '50M',
                            mimeTypes: [
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                                'video/mp4',
                                'video/webm',
                                'video/ogg',
                                'video/quicktime',
                            ],
                            mimeTypesMessage: 'Chaque fichier doit être une image ou une vidéo prise en charge.',
                        ),
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Projet::class,
        ]);
    }
}
