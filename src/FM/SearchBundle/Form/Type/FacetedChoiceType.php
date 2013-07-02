<?php

namespace FM\SearchBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Solarium\QueryType\Select\Result\Facet\Field as FacetResult;

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

    protected function getChoiceCount($facetResult, $choice)
    {
        if (isset($facetResult[$choice->data])) {
            return $facetResult[$choice->data];
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $facetResult = $options['facet_result'];

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
                $view->vars['counts'][$choiceIndex] = $this->getChoiceCount($facetResult, $choice);
            }
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'facet_result'
        ));
    }
}
