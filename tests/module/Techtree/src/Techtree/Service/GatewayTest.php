<?php

namespace Techtree\Service;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class GatewayTest extends \PHPUnit_Framework_TestCase implements ServiceLocatorAwareInterface
{
    /**
     * @var Gateway
     */
    protected $gateway;
    protected $serviceLocator;

    /**
     * Set the service locator.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return TechnologyNameLink
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }
    /**
     * Get the service locator.
     *
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }


    function setUp()
    {
        parent::setUp();

        $serviceLocator = $this->getServiceLocator();
        $this->gateway = $serviceLocator->get("Techtree\Service\Gateway");
    }

    function tearDown()
    {
//         // only rebuild database when $this->_rebuild is true:
//         if ( $this->_rebuild ) {
//             parent::rebuildDatabase();
//         }

        unset($this->_rebuild);
        unset($this->gateway);
    }

    public function testGetTechnology()
    {
        $object = $this->gateway->getTechnology(25);
        $this->assertType('Technology', $object);

        $this->setExpectedException('Nouron_Model_Gateway_Exception');
        $this->gateway->getTechnology('a');
    }

//     /**
//      * @dataProvider providerForWrongParameterExceptions
//      */
//     public function testGetRequirementExceptions($techId, $colonyId)
//     {
//         $testparams = array('tech_id' => $techId, 'required_tech_id' => $colonyId);
//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->getRequirement($testparams);
//     }

//     public function testGetRequirement()
//     {
//         $object = $this->gateway->getRequirement(array('tech_id' => 26, 'required_tech_id' => 25));
//         $this->assertType('Requirement', $object);

//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->getRequirement(array());
//     }

//     public function testGetPossession()
//     {
//         $object = $this->gateway->getPossession(array('colony_id' => 4, 'tech_id' => 25));
//         $this->assertType('Possession', $object);

//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->getPossession(array());
//     }

//     public function testGetPossessions()
//     {
//         $objects = $this->gateway->getPossessionsByColonyId(0);
//         $this->assertType('Possessions', $objects);
//         $object = $objects->current();
//         $this->assertType('Possession', $object);
//     }

//     public function testGetTechnologies()
//     {
//         $objects = $this->gateway->getTechnologies();
//         $this->assertType('Technologies', $objects);
//         $object = $objects->current();
//         $this->assertType('Technology', $object);
//     }

//     public function testGetCurrentMaxStage()
//     {
//         $stage = $this->gateway->getCurrentMaxStage(4);
//         $this->assertType('integer', $stage);

//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->getCurrentMaxStage('a');
//     }

//     public function testGetTechtreeByColonyId()
//     {
//         $techtree = $this->gateway->getTechtreeByColonyId(0);
//         $this->assertType('array', $techtree);

//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->getTechtreeByColonyId('a');
//     }

//     public function testGetGraphicalTechtreeByColonyId()
//     {
//         $techtree = $this->gateway->getGraphicalTechtreeByColonyId(0,800,600);
//         $this->assertType('array', $techtree);

//         $techtree = $this->gateway->getGraphicalTechtreeByColonyId(0);
//         $this->assertType('array', $techtree);

//         // wrong parameters for dimensions are ignored and standard is taken instead
//         $techtree = $this->gateway->getGraphicalTechtreeByColonyId(0,'abc','xyz');
//         $this->assertType('array', $techtree);

//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $techtree = $this->gateway->getGraphicalTechtreeByColonyId('a');
//     }

//     public function testGetRequirementsAsArray()
//     {
//         $reqs = $this->gateway->getRequirementsAsArray();
//         $this->assertType('array', $reqs);
//         $this->assertTrue(!empty($reqs));
//         $this->assertTrue(!empty($reqs[26][25]['count']));
//         $this->assertEquals(1, $reqs[26][25]['count']);
//     }

//     public function testGetRequirementsByTechnologyId()
//     {
//         $objects = $this->gateway->getRequirementsByTechnologyId(29);
//         $this->assertType('Requirements', $objects);
//         $object = $objects->current();
//         $this->assertType('Requirement', $object);

//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $objects = $this->gateway->getRequirementsByTechnologyId('a');
//     }

//     public function testGetStageRequirementsByStage()
//     {
//         $rqrmnts = $this->gateway->getStageRequirementsByStage(0);
//         $this->assertTrue(is_array($rqrmnts));
//     }

//     public function testGetCosts()
//     {
//         $objects = $this->gateway->getCosts();
//         $this->assertType('Costs', $objects);
//         $object = $objects->current();
//         $this->assertEquals('Cost', get_class($object));
//     }

//     public function testGetCostsByTechnologyId()
//     {
//         $objects = $this->gateway->getCostsByTechnologyId(25);
//         $this->assertType('Costs', $objects);
//         $object = $objects->current();
//         $this->assertEquals('Cost', get_class($object));

//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $objects = $this->gateway->getCostsByTechnologyId('a');
//     }

//     public function testCheckRequirementsByTechnologyId()
//     {
//         $result = $this->gateway->checkRequirementsByTechnologyId(29, 0);
//         $this->assertTrue(is_bool($result));
//     }

//     /**
//      * @dataProvider providerForWrongParameterExceptions
//      */
//     public function testCheckRequirementsByTechnologyIdExceptions($techId, $colonyId)
//     {
//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->checkRequirementsByTechnologyId($techId, $colonyId);
//     }

//     public function testCheckResourcePossessionByTechnologyId()
//     {
//         $result = $this->gateway->checkResourcePossessionByTechnologyId(25, 0);
//         $this->assertTrue($result);

//         $result = $this->gateway->checkResourcePossessionByTechnologyId(35, 0);
//         $this->assertFalse($result);

//         $this->markTestIncomplete();
//     }

//     /**
//      * @dataProvider providerForWrongParameterExceptions
//      */
//     public function testCheckResourcePossessionByTechnologyIdExceptions($techId, $colonyId)
//     {
//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->checkResourcePossessionByTechnologyId($techId, $colonyId);
//     }

//     public function testTechnologyLevelUp()
//     {
//         $this->_rebuild = true;

//         $colonyId = 0;

//         $techId   = 25; // one tick buildtime
//         $techId2  = 26; // three ticks buildtime
//         $techId3  = 36; // advisor (needs no buildtime)

//         $this->gateway->cancelOrder($techId, $colonyId, true);
//         $before   = $this->gateway->getOrders("colony_id = $colonyId")->count();
//         $this->gateway->technologyLevelUp($techId, $colonyId);
//         $after    = $this->gateway->getOrders("colony_id = $colonyId")->count();
//         $this->assertTrue($after == $before+1);

//         // test multi ticks buildtime:
//         $this->gateway->cancelOrder($techId2, $colonyId, true);
//         $before   = $this->gateway->getOrders("colony_id = $colonyId")->count();
//         $this->gateway->technologyLevelUp($techId2, $colonyId);
//         $after    = $this->gateway->getOrders("colony_id = $colonyId")->count();
//         $this->assertTrue($after == $before+3);

//         $this->gateway->cancelOrder($techId3, $colonyId, true);
//         $before   = $this->gateway->getOrders("colony_id = $colonyId")->count();
//         $this->gateway->technologyLevelUp($techId3, $colonyId);
//         $after    = $this->gateway->getOrders("colony_id = $colonyId")->count();
//         $this->assertTrue($after == $before);
//     }

//     /**
//      * @dataProvider providerForWrongParameterExceptions
//      */
//     public function testTechnologyLevelUpExceptions($techId, $colonyId)
//     {
//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->technologyLevelUp($techId, $colonyId);
//     }

//     /**
//      * @dataProvider providerTechnologyLevelUpTechtreeExceptions
//      */
//     public function testTechnologyLevelUpTechtreeExceptions($techId, $colonyId)
//     {
//         $this->setExpectedException('Exception');
//         $this->gateway->technologyLevelUp($techId, $colonyId);
//     }

//     public function providerTechnologyLevelUpTechtreeExceptions()
//     {
//         return array(
//             array(99,0), // unknown technology
//             array(95,0), // fail requirements
//             array(29,0), // unfullfilled requirements
//             array(35,0) // exception_NotEnoughResources
//             //array(99 // @todo: exception_MaximumReached
//         );
//     }

//     public function testTechnologyLevelDown()
//     {
//         $this->_rebuild = true;
//         $techId   = 25;
//         $invalidTechId = 99;
//         $colonyId = 0;

//         $this->gateway->cancelOrder($techId, $colonyId);
//         $before   = $this->gateway->getOrders("colony_id = $colonyId")->count();
//         $this->gateway->technologyLevelDown($techId, $colonyId);
//         $after    = $this->gateway->getOrders("colony_id = $colonyId")->count();
//         $this->assertTrue($after == $before+1);

//         $this->setExpectedException('Exception');
//         $this->gateway->technologyLevelDown($invalidTechId, $colonyId);
//     }

//     /**
//      * @dataProvider providerForWrongParameterExceptions
//      */
//     public function testTechnologyLevelDownExceptions($techId, $colonyId)
//     {
//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->technologyLevelDown($techId, $colonyId);
//     }

//     public function testIncreaseAmount()
//     {
//         $this->_rebuild = true;

//         $techId   = 25;
//         $colonyId = 0;

//         $key = array('colony_id' => $colonyId, 'tech_id' => $techId);

//         // update entry
//         $before = $this->gateway->getPossession($key)->nCount;
//         $this->gateway->increaseAmount($colonyId, $techId, 1);
//         $after = $this->gateway->getPossession($key)->nCount;

//         $this->assertTrue($after == $before + 1);

//         // when tech not exists it must be inserted:

//         $tech = $this->gateway->getPossession($key);
//         $tech->delete();

//         $before =  $this->gateway->getPossession($key);
//         $this->assertTrue( !isset($before->nCount) );
//         $this->gateway->increaseAmount($colonyId, $techId, 1);
//         $after = $this->gateway->getPossession($key);
//         $this->assertTrue( $after->nCount == 1);

//     }

//     public function testCreateObjects()
//     {
//         // test createTechnology()
//         $object = $this->gateway->createTechnology(array());
//         $this->assertType('Technology', $object);
//         // test createPossession()
//         $object = $this->gateway->createPossession(array());
//         $this->assertType('Possession', $object);
//         // test createRequirement()
//         $object = $this->gateway->createRequirement(array());
//         $this->assertType('Requirement', $object);
//         // test createCost()
//         $object = $this->gateway->createCost(array());
//         $this->assertType('Cost', $object);
//     }

//     public function testGetLevelByTechnologyId()
//     {
//         $count = $this->gateway->getLevelByTechnologyId(25, 0);
//         $this->assertTrue(is_integer($count));
//     }

//     /**
//      * @dataProvider providerForWrongParameterExceptions
//      */
//     public function testGetLevelByTechnologyIdExceptions($techId, $colonyId)
//     {
//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->getLevelByTechnologyId($techId, $colonyId);
//     }

//     public function testGetMaxBuildingOrders()
//     {
//         $colonyId = 3;
//         $count = $this->gateway->getMaxBuildingOrders($colonyId);
//         $this->assertTrue(is_integer($count));
//         $this->assertEquals(4, $count);

//         $colonyId = 0;
//         $count = $this->gateway->getMaxBuildingOrders($colonyId);
//         $this->assertTrue(is_integer($count));
//         $this->assertEquals(3, $count);
//     }

//     public function testGetMaxResearchOrders()
//     {
//         $colonyId = 3;
//         $count = $this->gateway->getMaxResearchOrders($colonyId);
//         $this->assertTrue(is_integer($count));
//         $this->assertEquals(5, $count);

//         $colonyId = 0;
//         $count = $this->gateway->getMaxResearchOrders($colonyId);
//         $this->assertTrue(is_integer($count));
//         $this->assertEquals(1, $count);
//     }

//     public function testGetMaxFleetOrders()
//     {
//         $colonyId = 3;
//         $count = $this->gateway->getMaxFleetOrders($colonyId);
//         $this->assertTrue(is_integer($count));
//         $this->assertEquals(1, $count);

//         $colonyId = 0;
//         $count = $this->gateway->getMaxFleetOrders($colonyId);
//         $this->assertTrue(is_integer($count));
//         $this->assertEquals(1, $count);

//         $this->markTestIncomplete();
//     }

//     public function testCancelOrder()
//     {
//         $this->_rebuild = true;

//         $techId   = 25;
//         $colonyId = 0;

//         $this->gateway->technologyLevelUp($techId, $colonyId);

//         $resourcesGw = new Resources_Model_Gateway();
//         $test = $resourcesGw->getResourcesByColonyId($colonyId, true);
//         //print_r($test);
//         $before   = $this->gateway->getOrders("colony_id = $colonyId AND tech_id = $techId")->count();
//         $this->gateway->cancelOrder($techId, $colonyId);

//                 $test = $resourcesGw->getResourcesByColonyId($colonyId, true);
//         //print_r($test);

//         $after   = $this->gateway->getOrders("colony_id = $colonyId AND tech_id = $techId")->count();
//         $this->assertTrue($before > 0);
//         $this->assertTrue($after == 0);
//     }

//     /**
//      * @dataProvider providerForWrongParameterExceptions
//      */
//     public function testCancelOrderExceptions($techId, $colonyId)
//     {
//         $this->setExpectedException('Nouron_Model_Gateway_Exception');
//         $this->gateway->cancelOrder($techId, $colonyId);
//     }
}