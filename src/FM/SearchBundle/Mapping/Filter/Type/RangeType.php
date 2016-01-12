<?php

namespace FM\SearchBundle\Mapping\Filter\Type;

use FM\SearchBundle\Mapping\Filter;
use FM\SearchBundle\Mapping\Filter\Type;

/**
 * Range Filter: constructs a query in the form of "field:[start TO end]".
 * There are two ways a filter can be set up for this:
 *
 * 1: With predefined ranges. For instance with an age filter where you want to
 *    group ages together. The choices would be defined as:
 *
 *    <code>
 *      array(
 *        1 => array(0, 15),  // age 15 and younger
 *        2 => array(16, 30), // age 16 to 30
 *        3 => array(31, 45), // age 31 to 45
 *        4 => array(46, 60), // age 46 to 60
 *        5 => array(61, *),  // age 61 and older
 *      )
 *    </code>
 *
 *    The values used in forms/urls are 1-5, which are converted by the filter
 *    into the actual ranges.
 *
 * 2: With two fixed sets of choices. For instance with a price filter. Choices
 *    are defined by two arrays: 'start' and 'end'
 *
 *    <code>
 *      array(
 *        'start' => array(
 *          0,
 *          250,
 *          500,
 *          750
 *        ),
 *        'end' => array(
 *          250,
 *          500,
 *          750
 *          1000
 *        )
 *      )
 *    </code>
 *
 *    This filter always has to submit two values.
 *
 */
class RangeType implements Type
{
    public function isMultiValued()
    {
        return true;
    }

    public function getQuery($name)
    {
        return $name . ':[%1% TO %2%]';
    }

    /**
     * A range can be defined using an associative array containing 'start' and
     * 'end' keys, both containing arrays with choices. The other type is a
     * range with predefined choices, which convert to an array of values.
     */
    public function isPredefined(Filter $filter)
    {
        $choices = $filter->getChoices();

        return !(isset($choices['start']) && isset($choices['end']));
    }

    public function getChoice(Filter $filter, $choice)
    {
        $choices = $filter->getChoices();

        if (!$this->isPredefined($filter)) {
            if (isset($choices['start'][$choice])) {
                return $choices['start'][$choice];
            }

            if (isset($choices['end'][$choice])) {
                return $choices['end'][$choice];
            }
        } else {
            if (isset($choices[$choice])) {
                return $choices[$choice];
            }
        }
    }

    /**
     * Normalizes choices into values for Solr query. Transforms predefined
     * ranges and validates choices. The result is always an array with two
     * values (start and end).
     *
     * @param  Filter $filter
     * @param  mixed  $choice
     * @return array
     */
    public function normalizeChoice(Filter $filter, $choice)
    {
        $filterChoices = $filter->getChoices();

        // See if a number/string has been passed. This is the case with
        // predefined choices, that are transformed into arrays of ranges
        if (is_scalar($choice)) {
            if (!isset($filterChoices[$choice])) {
                throw new \OutOfBoundsException(sprintf(
                    '"%s" is not a valid choice',
                    $choice
                ));
            }

            return $filterChoices[$choice];
        }

        // choice has to be an array here
        if (!is_array($choice)) {
            throw new \UnexpectedValueException(sprintf(
                'Expecting a scalar value or array for normalizeChoice, got "%s"',
                gettype($choice)
            ));
        }

        $predefined = $this->isPredefined($filter);

        // go through each choice and validate it
        $transformed = array();
        foreach ($choice as $key => $value) {
            // check for valid key
            if (!$predefined && !isset($filterChoices[$key])) {
                throw new \OutOfBoundsException(sprintf(
                    '"%s" is not a valid range key',
                    $key
                ));
            }

            // empty choices translate to wildcards
            if (!$value) {
                $transformed[$key] = '*';
                continue;
            }

            if ($predefined) {
                if (!isset($filterChoices[$value])) {
                    throw new \OutOfBoundsException(sprintf(
                        '"%s" is not a valid choice',
                        $value
                    ));
                }

                $transformed[$key] = $filterChoices[$value];

            } else {
                if (!isset($filterChoices[$key][$value])) {
                    throw new \OutOfBoundsException(sprintf(
                        '"%s" is not a valid choice',
                        $value
                    ));
                }

                $transformed[$key] = $filterChoices[$key][$value];
            }
        }

        // both the start and end have to be set in a range
        if (sizeof($transformed) < 2) {
            if ($predefined) {
                // just add a wildcard to the values: we'll use the first one as start
                $transformed[] = '*';
            } else {
                // we can auto-fill the value with a wildcard
                foreach (array_keys($filterChoices) as $choice) {
                    if (!isset($transformed[$choice])) {
                        $transformed[$choice] = '*';
                    }
                }
            }
        }

        return $transformed;
    }
}
