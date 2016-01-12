<?php

namespace FM\SearchBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use FM\SearchBundle\Mapping\Facet;

class FacetedChoiceType extends AbstractType
{
    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'faceted_choice';
    }

    protected function getChoiceCount(Facet $facet, $facetResult, $choice)
    {
        if ($facet->getCountType() === Facet::COUNT_TYPE_EXACT) {
            if (isset($facetResult[$choice->data])) {
                return $facetResult[$choice->data];
            }
        } elseif ($facet->getCountType() === Facet::COUNT_TYPE_CUMULATIVE) {
            $retval = 0;

            // TODO if choices are not in the right order these counts won't be
            // correct, and something more clever will be needed. For now this
            // will work fine though.
            foreach ($facetResult as $data => $count) {
                $retval += $count;

                if ($data === $choice->data) {
                    break;
                }
            }

            return $retval;
        } elseif ($facet->getCountType() === Facet::COUNT_TYPE_INVERSED_CUMULATIVE) {
            $retval = 0;

            foreach (array_reverse(array_keys($facetResult)) as $data) {
                $count = $facetResult[$data];
                $retval += $count;

                if ($data === $choice->data) {
                    break;
                }
            }

            return $retval;
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $facet = $options['facet'];
        $result = $options['facet_result'];

        $view->vars['counts'] = array();

        foreach ($view->vars['choices'] as $i => $choices) {
            // if choices are not nested, use the current index, otherwise use the nested choice keys as indices
            $index = null;
            if (!is_array($choices)) {
                $index = $i;
                $choices = array($choices);
            }

            foreach ($choices as $j => $choice) {
                $choiceIndex = is_null($index) ? $j : $index;
                $view->vars['counts'][$choiceIndex] = $this->getChoiceCount($facet, $result, $choice);
            }
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'facet',
            'facet_result',
        ));

        $resolver->setAllowedTypes(array(
            'facet' => 'FM\SearchBundle\Mapping\Facet',
        ));
    }
}
