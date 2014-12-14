<?php
namespace TradeTest\Form;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Trade\Form\SearchForm;

class SearchFormTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testConstructor()
    {
        $searchItemType = 'resources';
        $items = array();
        $range = 0;
        $form = new SearchForm($searchItemType, $items, $range);
        $this->assertInstanceOf(
            "Trade\Form\SearchForm",
            $form
        );

        $searchItemType = 'researches';
        $items = array(
            1 => array(
                'name' => 'testresearchitem',
                'is_tradeable' => 1
            )
        );
        $range = 3;
        $form = new SearchForm($searchItemType, $items, $range);
        $this->assertInstanceOf(
            "Trade\Form\SearchForm",
            $form
        );

    }

    public function testGetInputFilterSpecification()
    {
        $searchItemType = 'resources';
        $items = array();
        $range = 0;
        $form = new SearchForm($searchItemType, $items, $range);
        $spec = $form->getInputFilterSpecification();
        $this->assertTrue(is_array($spec));
    }

}