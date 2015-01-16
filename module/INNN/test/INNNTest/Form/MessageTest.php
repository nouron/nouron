<?php
namespace INNNTest\Form;

use PHPUnit_Framework_TestCase;
use INNNTest\Bootstrap;
use INNN\Form\Message;

class MessageTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->sm = Bootstrap::getServiceManager();
        #$this->sm->setAllowOverride(true);
    }

    public function testConstructor()
    {
        $form = new Message();
        $this->assertInstanceOf(
            "INNN\Form\Message",
            $form
        );
    }

    public function testGetInputFilterSpecification()
    {
        $form = new Message();
        $spec = $form->getInputFilterSpecification();
        $this->assertTrue(is_array($spec));
    }

}