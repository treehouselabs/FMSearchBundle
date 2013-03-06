<?php

namespace FM\SearchBundle\Tests\Mapping;

class FilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getQueryValues
     */
    public function testValueToQueryConversion($qs, $query)
    {
        $this->markTestIncomplete('Implement me');

        $query = array();
        parse_str($qs, $query);

        // $this->assertEquals($query, $value);
    }

    public static function getQueryValues()
    {
        return array(
            array('price[start]=250&price[end]=750', 'price:[250 TO *]')
        );
    }
}
