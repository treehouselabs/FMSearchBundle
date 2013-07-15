<?php

namespace FM\SearchBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use FM\SearchBundle\Form\Exception;
use FM\SearchBundle\Mapping\Filter;
use FM\SearchBundle\Mapping\Field\Type as FieldType;
use FM\SearchBundle\Mapping\Facet;
use FM\SearchBundle\Search\Search;
use FM\SearchBundle\Search\Query\Result;

class FilteredSearchType extends AbstractType
{
    protected $site;
    protected $container;
    protected $propertyConfig;
    protected $filters;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['search']) || !($options['search'] instanceof Search)) {
            throw new \LogicException('Please pass a Search object as an option');
        }

        $search = $options['search'];
        $result = isset($options['result']) ? $options['result'] : null;

        foreach ($search->getFilters() as $filter) {
            if ($this->skipFilter($filter, $options)) {
                continue;
            }

            try {
                $config = $this->getChildConfig($filter, $options);
                $builder->add($config['child'], $config['type'], $config['options']);
            } catch (Exception\FormBuilderException $fbe) {
                // couldn't create form child...
            }
        }
    }

    /**
     * Skips filter if the `render` option is set to false
     *
     * @param  Filter  $filter  The filter
     * @param  array   $options The form options
     * @return boolean
     */
    protected function skipFilter(Filter $filter, array $options)
    {
        if ($filter->getConfig()->get('render', true) === false) {
            return true;
        }
    }

    /**
     * Returns form child config for a filter
     *
     * @param  Filter $filter  The filter
     * @param  array  $options The form options
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

        if ($filter->getField()->getType() instanceof FieldType\String) {
            return $this->getTextFilterConfig($filter, $options);
        }

        return $this->getChoiceFilterConfig($filter, $options);
    }

    /**
     * Returns form child config for a text-based filter
     *
     * @param  Filter $filter  The filter
     * @param  array  $options The form options
     * @return array
     */
    protected function getTextFilterConfig(Filter $filter, array $options)
    {
        return array(
            'child' => $filter->getName(),
            'type' => 'text',
            'options' => array(
                'label'    => $filter->getLabel() ?: sprintf($options['label_pattern'], $filter->getName()),
                'mapped'   => false,
                'required' => false
            )
        );
    }

    /**
     * Returns form child config for a hidden filter
     *
     * @param  Filter $filter  The filter
     * @param  array  $options The form options
     * @return array
     */
    protected function getHiddenFilterConfig(Filter $filter, array $options)
    {
        return array(
            'child' => $filter->getName(),
            'type' => 'hidden',
            'options' => array(
                'mapped'   => false,
                'required' => false
            )
        );
    }

    /**
     * Returns form child config for a range filter
     *
     * @param  Filter $filter  The filter
     * @param  array  $options The form options
     * @return array
     */
    protected function getRangeFilterConfig(Filter $filter, array $options)
    {
        $expanded = $this->getExpanded($filter);
        $choices = $this->getChoices($filter);

        if (empty($choices) && !$this->allowEmptyChoices($filter)) {
            throw new Exception\NoChoicesException;
        }

        $config = array(
            'child' => $filter->getName(),
            'type' => 'range',
            'options' => array(
                'type'     => 'choice',
                'label'    => $filter->getLabel() ?: sprintf($options['label_pattern'], $filter->getName()),
                'start_options' => array(
                    'choices' => $choices['start'],
                    'multiple' => false,
                    'expanded' => $expanded,
                    'empty_value' => ''
                ),
                'end_options' => array(
                    'choices' => $choices['end'],
                    'multiple' => false,
                    'expanded' => $expanded,
                    'empty_value' => ''
                ),
                'mapped'   => false,
                'required' => false,
            )
        );

        return $config;
    }

    /**
     * Returns form child config for a choice-based filter
     *
     * @param  Filter $filter  The filter
     * @param  array  $options The form options
     * @return array
     */
    protected function getChoiceFilterConfig(Filter $filter, array $options)
    {
        $multiple = $this->getMultiple($filter);
        $expanded = $this->getExpanded($filter);

        $result = isset($options['result']) ? $options['result'] : null;

        // use facet results
        $facetResult = null;
        $facet = $filter->getFacet();
        if ($facet && $result) {
            $facets = $result->getFacets();

            // TODO if Solarium ever makes it easy to extend the response parser, inject this code somehow
            // see https://github.com/basdenooijer/solarium/issues/145
            $facetResult = array();
            foreach ($facets->getFacet($facet->getName())->getValues() as $value => $count) {
                if (in_array($value, array('true', 'false'))) {
                    $value = $value === 'true';
                }

                $facetResult[$value] = $count;
            }
        }

        $choices = $this->getChoices($filter, $facetResult);

        if (empty($choices) && !$this->allowEmptyChoices($filter)) {
            throw new Exception\NoChoicesException;
        }

        // empty value for radio buttons
        if (($expanded === true) && ($multiple === false)) {
            $choices[''] = 'empty_value_label';
        }

        $config = array(
            'child' => $filter->getName(),
            'type' => 'choice',
            'options' => array(
                'label'    => $filter->getLabel() ?: sprintf($options['label_pattern'], $filter->getName()),
                'choices'  => $choices,
                'multiple' => $multiple,
                'expanded' => $this->getExpanded($filter),
                'mapped'   => false,
                'required' => false
            )
        );

        // set facet options
        if ($facetResult !== null) {
            $config['type'] = 'faceted_choice';
            $config['options']['facet_result'] = $facetResult;
        }

        // set empty value for selects
        if (($expanded === false) && ($multiple === false)) {
            $config['options']['empty_value'] = 'empty_value_label';
        }

        return $config;
    }

    protected function allowEmptyChoices(Filter $filter)
    {
        return $filter->getConfig()->get('allow_empty', false);
    }

    public function getMultiple(Filter $filter)
    {
        return $filter->getConfig()->get('multiple', true);
    }

    public function getExpanded(Filter $filter)
    {
        return $filter->getConfig()->get('expanded', true);
    }

    public function getChoices(Filter $filter, $facetResult = null)
    {
        // use filter choices if it provides them
        if ($filter->hasChoices()) {
            return $this->getTranslatedChoices($filter, $filter->getChoices());
        }

        // Use yes/no checkboxes for booleans. A single checkbox does not support
        // negation ("no") queries, hence both options.
        if ($filter->getField()->getType() instanceof FieldType\Boolean) {
            return $this->getTranslatedChoices($filter, array(
                1 => 'yes',
                0 => 'no',
            ));
        }

        // use facet results
        if ($facetResult) {
            $facetValues = array_keys($facetResult);
            $choices = array_combine($facetValues, $facetValues);

            return $this->getTranslatedChoices($filter, $choices);
        }

        return array();
    }

    protected function getTranslatedChoices(Filter $filter, $choices)
    {
        foreach ($choices as $value => &$label) {
            $label = $filter->getChoiceLabel($value, $label);
        }

        return $choices;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'translation_domain' => 'forms',
            'label_pattern' => '%s',
        ));

        $resolver->setRequired(array(
            'search'
        ));

        $resolver->setOptional(array(
            'result'
        ));
    }

    public function getName()
    {
        return 'filters';
    }
}
