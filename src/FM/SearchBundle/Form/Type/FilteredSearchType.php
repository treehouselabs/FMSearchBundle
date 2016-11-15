<?php

namespace FM\SearchBundle\Form\Type;

use FM\KamersBundle\Property\Config\Config;
use FM\SearchBundle\Form\Exception;
use FM\SearchBundle\Form\Exception\NoChoicesException;
use FM\SearchBundle\Mapping\Facet\Type as FacetType;
use FM\SearchBundle\Mapping\Field\Type as FieldType;
use FM\SearchBundle\Mapping\Filter;
use FM\SearchBundle\Search\Search;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilteredSearchType extends AbstractType
{
    /**
     * @var string
     */
    protected $site;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Config
     */
    protected $propertyConfig;

    /**
     * @var
     */
    protected $filters;

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'translation_domain' => 'forms',
            'label_pattern' => '%s',
        ]);

        $resolver->setRequired([
            'search',
        ]);

        $resolver->setDefined([
            'result',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['search']) || !($options['search'] instanceof Search)) {
            throw new \LogicException('Please pass a Search object as an option');
        }

        $search = $options['search'];

        foreach ($search->getFilters() as $filter) {
            if ($this->skipFilter($filter, $options)) {
                continue;
            }

            try {
                $filterConfig = $filter->getConfig();
                $formOptions = isset($filterConfig['form']) ? $filterConfig['form'] : [];

                // TODO merge with form_options to preserve forwards compatibility
                $fieldOptions = array_merge(
                    $options,
                    $formOptions
                );

                $config = $this->getChildConfig($filter, $fieldOptions);
                $builder->add($config['child'], $config['type'], $config['options']);
            } catch (Exception\FormBuilderException $fbe) {
                // couldn't create form child...
            }
        }
    }

    /**
     * @param Filter $filter
     *
     * @return mixed
     */
    public function getMultiple(Filter $filter)
    {
        return $filter->getConfig()->get('multiple', true);
    }

    /**
     * @param Filter $filter
     *
     * @return mixed
     */
    public function getExpanded(Filter $filter)
    {
        return $filter->getConfig()->get('expanded', true);
    }

    /**
     * @param Filter $filter
     * @param null   $facetResult
     *
     * @return array
     */
    public function getChoices(Filter $filter, $facetResult = null)
    {
        // use filter choices if it provides them
        if ($filter->hasChoices()) {
            return $this->getTranslatedChoices($filter, $filter->getChoices());
        }

        // Use yes/no checkboxes for booleans. A single checkbox does not support
        // negation ("no") queries, hence both options.
        if ($filter->getField()->getType() instanceof FieldType\BooleanType) {
            return $this->getTranslatedChoices($filter, [
                1 => 'yes',
                0 => 'no',
            ]);
        }

        // use facet results
        if ($facetResult) {
            $facetValues = array_keys($facetResult);
            $choices = array_combine($facetValues, $facetValues);

            return $this->getTranslatedChoices($filter, $choices);
        }

        return [];
    }

    /**
     * Skips filter if the `render` option is set to false.
     *
     * @param Filter $filter  The filter
     * @param array  $options The form options
     *
     * @return bool
     */
    protected function skipFilter(Filter $filter, array $options)
    {
        if ($filter->getConfig()->get('render', true) === false) {
            return true;
        }
    }

    /**
     * Returns form child config for a filter.
     *
     * @param Filter $filter  The filter
     * @param array  $options The form options
     *
     * @return array
     */
    protected function getChildConfig(Filter $filter, array $options)
    {
        if ($filter->getConfig()->get('render') === 'range') {
            return $this->getRangeFilterConfig($filter, $options);
        }

        if ($filter->getConfig()->get('render') === 'hidden') {
            return $this->getHiddenFilterConfig($filter, $options);
        }

        if ($filter->getConfig()->get('render') === 'choice') {
            return $this->getChoiceFilterConfig($filter, $options);
        }

        if ($filter->getField()->getType() instanceof FieldType\StringType) {
            return $this->getTextFilterConfig($filter, $options);
        }

        return $this->getChoiceFilterConfig($filter, $options);
    }

    /**
     * Returns form child config for a text-based filter.
     *
     * @param Filter $filter  The filter
     * @param array  $options The form options
     *
     * @return array
     */
    protected function getTextFilterConfig(Filter $filter, array $options)
    {
        return [
            'child' => $filter->getName(),
            'type' => TextType::class,
            'options' => [
                'label' => $filter->getLabel() ?: sprintf($options['label_pattern'], $filter->getName()),
                'mapped' => false,
                'required' => false,
            ],
        ];
    }

    /**
     * Returns form child config for a hidden filter.
     *
     * @param Filter $filter  The filter
     * @param array  $options The form options
     *
     * @return array
     */
    protected function getHiddenFilterConfig(Filter $filter, array $options)
    {
        return [
            'child' => $filter->getName(),
            'type' => HiddenType::class,
            'options' => [
                'mapped' => false,
                'required' => false,
            ],
        ];
    }

    /**
     * Returns form child config for a range filter.
     *
     * @param Filter $filter  The filter
     * @param array  $options The form options
     *
     * @throws NoChoicesException
     *
     * @return array
     */
    protected function getRangeFilterConfig(Filter $filter, array $options)
    {
        $expanded = $this->getExpanded($filter);
        $choices = $this->getChoices($filter);

        if (empty($choices) && !$this->allowEmptyChoices($filter)) {
            throw new NoChoicesException();
        }

        $config = [
            'child' => $filter->getName(),
            'type' => RangeType::class,
            'options' => [
                'type' => ChoiceType::class,
                'label' => $filter->getLabel() ?: sprintf($options['label_pattern'], $filter->getName()),
                'start_options' => [
                    'choices' => $this->getTranslatedChoices($filter, $choices['start']),
                    'multiple' => false,
                    'expanded' => $expanded,
                    'placeholder' => false,
                ],
                'end_options' => [
                    'choices' => $this->getTranslatedChoices($filter, $choices['end']),
                    'multiple' => false,
                    'expanded' => $expanded,
                    'placeholder' => false,
                ],
                'mapped' => false,
                'required' => false,
            ],
        ];

        return $config;
    }

    /**
     * Returns form child config for a choice-based filter.
     *
     * @param Filter $filter  The filter
     * @param array  $options The form options
     *
     * @throws NoChoicesException
     *
     * @return array
     */
    protected function getChoiceFilterConfig(Filter $filter, array $options)
    {
        $multiple = $this->getMultiple($filter);
        $expanded = $this->getExpanded($filter);

        $result = isset($options['result']) ? $options['result'] : null;

        // use facet results
        $counts = null;
        $facet = $filter->getFacet();
        if ($facet && $result) {
            $facets = $result->getFacets();

            // TODO if Solarium ever makes it easy to extend the response parser, inject this code somehow
            // see https://github.com/basdenooijer/solarium/issues/145
            $counts = [];
            $facetResult = $facets->getFacet($facet->getName());
            $values = $facetResult->getValues();
            foreach ($values as $value => $count) {
                if (in_array($value, ['true', 'false'])) {
                    $value = $value === 'true';
                }

                $counts[$value] = $count;
            }

            if ($facet->getType() instanceof FacetType\RangeType) {
                // add before/after counts
                reset($values);
                if ($before = $facetResult->getBefore()) {
                    $counts[key($values)] += $before;
                }

                end($values);
                if ($after = $facetResult->getAfter()) {
                    $counts[key($values)] += $after;
                }
            }
        }

        $choices = $this->getChoices($filter, $counts);

        if (empty($choices) && !$this->allowEmptyChoices($filter)) {
            throw new NoChoicesException();
        }

        // empty value for radio buttons
        if (($expanded === true) && ($multiple === false)) {
            $choices[''] = 'empty_value_label';
        }

        $config = [
            'child' => $filter->getName(),
            'type' => ChoiceType::class,
            'options' => [
                'label' => $filter->getLabel() ?: sprintf($options['label_pattern'], $filter->getName()),
                'choices' => $choices,
                'multiple' => $multiple,
                'expanded' => $this->getExpanded($filter),
                'mapped' => false,
                'required' => false,
            ],
        ];

        // set facet options
        if ($counts !== null) {
            $config['type'] = isset($options['type']) ? $options['type'] : FacetedChoiceType::class;
            $config['options']['facet'] = $facet;
            $config['options']['facet_result'] = $counts;
        }

        // set empty value for selects
        if (($expanded === false) && ($multiple === false)) {
            $config['options']['empty_value'] = 'empty_value_label';
        }

        return $config;
    }

    /**
     * @param Filter $filter
     *
     * @return mixed
     */
    protected function allowEmptyChoices(Filter $filter)
    {
        return $filter->getConfig()->get('allow_empty', false);
    }

    /**
     * @param Filter $filter
     * @param        $choices
     *
     * @return mixed
     */
    protected function getTranslatedChoices(Filter $filter, $choices)
    {
        foreach ($choices as $value => &$label) {
            $label = $filter->getChoiceLabel($value, $label);
        }

        return $choices;
    }
}
