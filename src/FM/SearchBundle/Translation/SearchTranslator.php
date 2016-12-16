<?php

namespace FM\SearchBundle\Translation;

use Symfony\Component\Translation\TranslatorInterface;
use FM\SearchBundle\Mapping\Filter;
use FM\SearchBundle\Search\Search;

/**
 * Translates a search object into human-readable text.
 */
class SearchTranslator
{
    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var Search
     */
    protected $search;

    /**
     * @var array
     */
    protected $filters = array();

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $translationDomain;

    /**
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->setTwig($twig);
    }

    /**
     * Clones Twig environment, and overrides loader with a string loader.
     *
     * @param \Twig_Environment $twig
     */
    public function setTwig(\Twig_Environment $twig)
    {
        $this->twig = clone $twig;
        $this->twig->setLoader(new \Twig_Loader_String());
    }

    /**
     * @param TranslatorInterface $translator
     * @param string              $defaultDomain
     */
    public function setTranslator(TranslatorInterface $translator, $defaultDomain = null)
    {
        $this->translator = $translator;

        if (!is_null($defaultDomain)) {
            $this->translationDomain = $defaultDomain;
        }
    }

    /**
     * @param string $domain
     */
    public function setTranslationDomain($domain)
    {
        $this->translationDomain = $domain;
    }

    /**
     * @param Search $search
     */
    public function setSearch(Search $search)
    {
        $this->search = $search;
    }

    /**
     * @param Filter $filter
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[$filter->getName()] = $filter;
    }

    /**
     * @param string $name
     *
     * @throws \OutOfBoundsException When filter doesn't exist
     *
     * @return Filter
     */
    public function getFilter($name)
    {
        if (!array_key_exists($name, $this->filters)) {
            if ($this->search && ($filter = $this->search->getFilter($name))) {
                $this->filters[$name] = $filter;
            }
        }

        if (array_key_exists($name, $this->filters)) {
            return $this->filters[$name];
        }

        throw new \OutOfBoundsException(sprintf(
            'No filter with the name "%s" defined. Please add the filter using
            addFilter or setSearch',
            $name
        ));
    }

    /**
     * Tries to translate the filter using the supplied translator. Filter
     * choices are translated using their respective label configurations.
     *
     * @param Filter $filter
     * @param mixed  $value
     *
     * @return array Array containing the translated choices
     */
    public function humanizeFilter(Filter $filter, $value)
    {
        $parts = array();

        foreach ((array) $value as $key => $val) {
            if ($filter->hasChoices()) {
                try {
                    $choice = $filter->getChoice($val);
                } catch (\OutOfBoundsException $e) {
                    // choice does not exist (anymore), that's ok, we won't use
                    // it in our translation than.
                    continue;
                }
            } else {
                $choice = $filter->transformValue($val);
            }

            $choiceLabel = $filter->getChoiceLabel($val, $choice);

            if ($this->translator) {
                $choiceLabel = $this->translator->trans($choiceLabel, array(), $this->translationDomain);
            }

            $parts[$key] = $choiceLabel;
        }

        return $parts;
    }

    /**
     * Translates values into readable/translated choices. Returns array with
     * exact/translated values, useful in translation functions.
     *
     * @param array $values
     *
     * @return array
     */
    public function getPlaceholders(array $values)
    {
        $placeholders = array();

        foreach ($values as $name => $value) {
            if (!empty($value)) {
                try {
                    $filter = $this->getFilter($name);
                    $placeholders[$filter->getName()] = $this->humanizeFilter($filter, $value);
                } catch (\OutOfBoundsException $e) {
                    // Ignore this:
                    // No filter with the name "neighbourhood_id" defined. Please add the filter using
                    // addFilter or setSearch
                }
            }
        }

        return $placeholders;
    }

    /**
     * Translates search values using the supplied text as a template.
     *
     * @param mixed $text   Can be a string, or an instance of Twig_Template
     * @param array $values
     *
     * @return string
     */
    public function translate($text, array $values)
    {
        $placeholders = $this->getPlaceholders($values);

        if ($text instanceof \Twig_TemplateWrapper) {
            return $text->render($placeholders);
        } else {
            return $this->twig->render($text, $placeholders);
        }
    }
}
