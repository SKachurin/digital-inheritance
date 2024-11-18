<?php

namespace App\Form\Type;

use App\CommandHandler\Pipeline\Create\ActionDto;
use App\Enum\IntervalEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $customerActions = $options['customerActions'];
        $actionTypeChoices = [];

        foreach ($customerActions as $action) {
            $actionTypeEnum = $action->getActionType();
            $actionTypeValue = $actionTypeEnum->value;

            // Ensure uniqueness
            if (!isset($actionTypeChoices[$actionTypeValue])) {
                $actionTypeChoices[$actionTypeEnum->name] = $actionTypeEnum;
            }
        }

        $builder
            ->add('position', IntegerType::class, [
                'label' => 'form.label.position_number',
                'help' => 'form.help.position',
                'required' => true,
            ])
            ->add('actionType', ChoiceType::class, [
                'choices' => $actionTypeChoices,
                'choice_label' => fn($choice) => $choice->value,
                'choice_value' => fn($choice) => $choice ? $choice->value : '',
                'label' => 'form.label.action_name',
                'help' => 'form.help.action_name',
                'required' => false,
            ])
            ->add('interval', ChoiceType::class, [
                'choices' => IntervalEnum::cases(),
                'choice_label' => fn(IntervalEnum $enum) => $enum->value,
                'label' => 'form.label.action_interval',
                'help' => 'form.help.action_interval',
                'required' => false,
                'data' => IntervalEnum::NOT_SET,
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActionDto::class,
        ]);
        $resolver->setRequired('customerActions');
        $resolver->setAllowedTypes('customerActions', ['array']);
    }
}

