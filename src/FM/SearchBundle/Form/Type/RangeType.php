<?php

namespace FM\SearchBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RangeType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultOptions = [
            'required' => $options['required'],
            'error_bubbling' => true,
        ];

        $startOptions = array_merge(
            $defaultOptions,
            ['label' => $options['label'] . '_start'],
            $options['start_options']
        );

        $endOptions = array_merge(
            $defaultOptions,
            ['label' => $options['label'] . '_end'],
            $options['end_options']
        );

        $builder->add('start', $options['type'], $startOptions);
        $builder->add('end', $options['type'], $endOptions);
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => 'choice',
            'start_options' => [],
            'end_options' => [],
            'compound' => true,
            'placeholder' => 'Choose a value',
        ]);

        $resolver->setAllowedTypes('type', ['string']);
        $resolver->setAllowedTypes('start_options', ['array']);
        $resolver->setAllowedTypes('end_options', ['array']);
    }

    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return FormType::class;
    }
}
