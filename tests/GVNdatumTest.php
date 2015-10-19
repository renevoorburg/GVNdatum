<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/GVNdatum.php';

class GVNdatumTest extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider datumProvider
     */
    public function testStartDate($input, $start, $end)
    {

        $date = new GVNdatum($input);
        $this->assertEquals($start, $date->getStartDate());
        $this->assertEquals($end, $date->getEndDate());
    }


    public function datumProvider()
    {
        $all = json_decode(file_get_contents('tests/testdata.json'));
        return $all->testdata;
    }


}