<?php
namespace TradeTest\Form;

use PHPUnit_Framework_TestCase;
use TechtreeTest\Bootstrap;
use Trade\Form\NewOfferForm;

class NewOfferFormTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testConstructor()
    {
        $searchItemType = 'resources';
        $items = array(
            1 => array(
                'name' => 'testresourceitem',
                'is_tradeable' => 1
            )
        );
        $range = 0;
        $form = new NewOfferForm($searchItemType, $items, $range);
        $this->assertInstanceOf(
            "Trade\Form\NewOfferForm",
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
        $form = new NewOfferForm($searchItemType, $items, $range);
        $this->assertInstanceOf(
            "Trade\Form\NewOfferForm",
            $form
        );
    }

    public function testGetInputFilterSpecification()
    {
        $searchItemType = 'resources';
        $items = array(
            1 => array(
                'name' => 'testresource',
                'is_tradeable' => 1
            )
        );
        $range = 0;
        $form = new NewOfferForm($searchItemType, $items, $range);
        $spec = $form->getInputFilterSpecification();
        $this->assertTrue(is_array($spec));
    }

}