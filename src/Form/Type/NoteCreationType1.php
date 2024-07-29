<?php

namespace App\Form\Type;

use App\CommandHandler\Note\Create\NoteCreateInputDto;
use App\Entity\Beneficiary;
use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class NoteCreationType1 extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!isset($options['customerId']) || !$options['customerId']) {
            throw new \InvalidArgumentException('The customerId option is required.');
        }

        $customerId = $options['customerId'];


        $builder
            ->add('customerTextAnswerOne', TextareaType::class, [
                'label' => 'Customer Text Answer One',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Customer Text Answer One cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                ]
            ])
            ->add('customerTextAnswerTwo', TextareaType::class, [
                'label' => 'Customer Text Answer Two',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Customer Text Answer Two cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                ]
            ])
            ->add('beneficiaryTextAnswerOne', TextareaType::class, [
                'label' => 'Beneficiary Text Answer One',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Beneficiary Text Answer One cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                ]
            ])
            ->add('beneficiaryTextAnswerTwo', TextareaType::class, [
                'label' => 'Beneficiary Text Answer Two',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Beneficiary Text Answer Two cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                ]
            ])
//            ->add('beneficiaryId', EntityType::class, [
//                'class' => Beneficiary::class,
//                'choice_label' => 'beneficiaryName',
//                'label' => 'Beneficiary',
//                'required' => false,
//                'query_builder' => function (EntityRepository $entityRepository)  use ($customerId):  QueryBuilder {
//                    return $entityRepository->createQueryBuilder('c')
//                        ->select('b')
//                        ->from(Beneficiary::class, 'b')
//                        ->innerJoin('b.notes', 'n')
//                        ->where('n.customer = :customer')
//                        ->setParameter('customer', $customerId);
//                },
//            ])
            ->add('beneficiaryTextAnswerOne', TextareaType::class, [
                'label' => 'Beneficiary Text Answer One',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Beneficiary Text Answer One cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => array(
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                )
            ])
            ->add('beneficiaryTextAnswerTwo', TextareaType::class, [
                'label' => 'Beneficiary Text Answer Two',
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 10000,
                        'maxMessage' => 'Beneficiary Text Answer Two cannot be longer than {{ limit }} characters.',
                    ]),
                ],
                'attr' => array(
                    'placeholder' => 'This field is intended for encrypted data.',
                    'rows' => 10,
                    'style' => 'height: 15em',
                )
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
            'customerId' => null
        ]);
    }
}
