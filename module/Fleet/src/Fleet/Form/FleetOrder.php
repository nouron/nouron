<?php //
// namespace Fleet\Form;

// use Zend\Form\Form;
// use Zend\InputFilter\InputFilterInterface;
// use Zend\InputFilter\InputFilterProviderInterface;

// class FleetOrder extends Form implements InputFilterProviderInterface
// {
//     public function __construct()
//     {
//         parent::__construct('fleetorder');
//         $this->setAttribute('action', '/galaxy/fleet/order');
//         $this->setAttribute('method', 'post');
//         $this->add(array(
//             'name' => 'fleet_id',
//             'attributes' => array(
//                     'type' => 'hidden',
//                     'value' => 0
//             ),
//         ));
//         $this->add(array(
//             'name' => 'order',
//             'attributes' => array(
//                 'type' => 'input',
//                 'value' => 'Flotte'
//             ),
//         ));
//         $this->add(array(
//             'name' => 'submit',
//             'attributes' => array(
//                 'type' => 'submit',
//                 'value' => 'Hinzufügen'
//             ),
//         ));
//     }

//     public function getInputFilterSpecification()
//     {
//         return array(
//             'fleetordername' => array (
//                 'required' => true,
//                 'filters' => array(
//                     array(
//                         'name' => 'StringTrim'
//                     )
//                 ),
//                 'validators' => array(
//                     array(
//                         'name' => 'NotEmpty',
//                         'options' => array(
//                             'message' =>
//                             "Bitte gib der Flotte einen Namen."
//                         )
//                     )
//                 )
//              ),
//         );
//     }
// }