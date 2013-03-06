<?php

namespace FM\SearchBundle\Tests\Search\Query;

use FM\SearchBundle\Search\Query\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    protected $dm;
    protected $search;

    public function setUp()
    {
        $filter = $this->getMockBuilder('FM\SearchBundle\Mapping\Filter')
                       ->disableOriginalConstructor()
                       ->setMethods(array('init'))
                       ->getMock();

        $this->dm = $this->getMockBuilder('FM\SearchBundle\DocumentManager')
                         ->disableOriginalConstructor()
                         ->getMock();

        $this->search = $this->getMockBuilder('FM\SearchBundle\Search\Search')
                             ->disableOriginalConstructor()
                             ->setMethods(array('hasFilter', 'getFilter'))
                             ->getMock();

        $this->search->expects($this->any())
                     ->method('hasFilter')
                     ->with($this->equalTo('foo'))
                     ->will($this->returnValue(true));

        $this->search->expects($this->any())
                     ->method('getFilter')
                     ->with($this->equalTo('foo'))
                     ->will($this->returnValue($filter));
    }

    /**
     * @dataProvider getEmptyValues
     */
    public function testBindEmptyValue($value, $bool)
    {
        $query = new Query($this->dm, $this->search);
        $query->bind(array(
            'foo' => $value
        ));

        $values = \PHPUnit_Framework_Assert::readAttribute($query, 'values');

        $bool ? $this->assertArrayHasKey('foo', $values, sprintf('Value %s should be included in query', json_encode($value)))
              : $this->assertArrayNotHasKey('foo', $values, sprintf('Value %s should not be included in query', json_encode($value)));
    }

    public static function getEmptyValues()
    {
        return array(
            array('', false),
            array(null, false),
            array('0', true),
            array(0, true),
            array(array(), false),
        );
    }
}
