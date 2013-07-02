<?php

namespace FM\SearchBundle\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

class SearchExtension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('enumerate', array($this, 'enumerate')),
        );
    }

    /**
     * Creates a readable enumeration of an array. Parts are glued together with
     * a comma, with an optional different last glue part.
     *
     * Examples:
     * enumerate([foo, bar, baz])        => foo, bar, baz
     * enumerate([foo, bar, baz], 'and') => foo, bar and baz
     * enumerate([foo, bar], 'or')       => foo or bar
     *
     * @param  array  $parts    The enumerated parts
     * @param  string $lastGlue The glue to use for the last part
     * @param  string $glue     The default glue to use
     * @return string
     */
    public function enumerate(array $parts, $lastGlue = ', ', $glue = ', ')
    {
        if (sizeof($parts) > 2) {
            $lastPart = array_pop($parts);

            return implode(sprintf(' %s ', $lastGlue), array(implode($glue, $parts), $lastPart));
        }

        if (sizeof($parts) > 1) {
            return implode(sprintf(' %s ', $lastGlue), $parts);
        } else {
            return implode('', $parts);
        }
    }

    public function getName()
    {
        return 'twig_extension_fm_search';
    }
}
