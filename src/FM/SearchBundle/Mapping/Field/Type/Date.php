<?php

namespace FM\SearchBundle\Mapping\Field\Type;

use FM\SearchBundle\Mapping\Field\Type;

class Date implements Type
{
    public function getDateTimeFormatString()
    {
        return 'Y-m-d\TH:i:s\Z';
    }

    public function convertToPhpValue($value)
    {
        if ($value === null || $value instanceof \DateTime) {
            return $value;
        }

        if (!$val = \DateTime::createFromFormat($this->getDateTimeFormatString(), $value)) {
            throw new \InvalidArgumentException(sprintf(
                'Could not convert Solr value "%s" to DateTime. Expected format: %s',
                $value,
                $this->getDateTimeFormatString()
            ));
        }

        return $val;
    }

    public function convertToSolrValue($value)
    {
        return is_null($value) ? null : gmdate($this->getDateTimeFormatString(), $value->getTimestamp());
    }
}
