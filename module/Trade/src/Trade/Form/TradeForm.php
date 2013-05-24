<?php
/**
 * @category   Nouron
 * @package    Nouron_Ingame
 * @subpackage Forms
 */
class Trade_Form_TradeForm extends Nouron_Form
{

    public function __construct($type, $values = null, $options = null )
    {
        if (empty($type)) return false;

        parent::__construct($values, $options);
        $elements  = Zend_Registry::get($type);

        if ($elements instanceof Nouron_Model_Objects_Abstract) {
            $elements = $elements->getArrayCopy();
        }

        $this->setMethod('post')
        ->setAction("/")
        ->setName('TradeForm')
        ->setIsArray(true)
        ->setElementsBelongTo('ownOffers')
        ->setDecorators(array(
                'FormElements',
                array('HtmlTag', array('tag' => 'tr')),
            	'Form'
            )
        );

        foreach ($elements as $tmp)
        {
            if ($tmp['bTradeable'] == 1)
            {
                if (isset($tmp['sResource']))  {
                    $tmp['sName'] = $tmp['sResource'];
                } elseif (isset($tmp['sTechnology'])) {
                    $tmp['sName'] = $tmp['sTechnology'];
                } elseif (isset($tmp['sRoute'])) {
                    $tmp['sName'] = $tmp['sRoute'];
                } else {
                    $tmp['sName'] = '';
                }

                $subForm = new Zend_Form_SubForm();
                $subForm->setIsArray(true)
                    ->setElementsBelongTo('"'.$tmp['nId'].'"')
                    ->setDecorators(array(
                        'FormElements',
                        array('HtmlTag', array('tag' => 'tr')),
                    )
                );

                // 1. Spalte: Name der Resource (Label)
                // + 2. Spalte: Kauf oder Verkauf
                $direction = new Zend_Form_Element_Select('direction');
                $direction->setLabel($tmp['sName'])
                    ->addValidator(new Zend_Validate_InArray(array(0,1)))
                    ->setRequired(true)
                    ->addMultiOptions(array(0 => 'trade_buy', 1 => 'trade_sell'))
                    ->setDecorators(array(
                        'ViewHelper',
                        'Errors',
                        array('HtmlTag', array('tag' => 'td')),
                        array('Label', array('tag' => 'td')),
                    )
                );

                // 3. Spalte: Menge
                $amount = new Zend_Form_Element_Text('amount');
                $amount->setAttrib('size',4)
                    ->addValidator('Int')
                    ->setRequired(true)
                    ->setDecorators(array(
                        'ViewHelper',
                        'Errors',
                        array('HtmlTag', array('tag' => 'td')),
                    )
                );

                // 4. Spalte: Preis
                $price = new Zend_Form_Element_Text('price');
                $price->setAttrib('size',2)
                    ->addValidator('Int')
                    ->setRequired(true)
                    ->setDecorators(array(
                        'ViewHelper',
                        'Errors',
                        array('HtmlTag', array('tag' => 'td')),
                    )
                );

                // 5. Spalte: Restriktion
                $restriction = new Zend_Form_Element_Select('restriction');
                $restriction->addValidator('Int')
                    ->setRequired(true)
                    ->addMultiOptions(array(
                        0 => 'Keine',
                        1 => 'trade_ownRace',
                        2 => 'trade_ownFaction',
                        3 => 'trade_ownAlliance'
                    )
                )
                ->setDecorators(array(
                    'ViewHelper',
                    'Errors',
                    array('HtmlTag', array('tag' => 'td')),
                ));

                if ( !empty($values[$tmp['nId']]) ) {
                    $direction->setValue($values[$tmp['nId']]['nDirection']);
                    $amount->setValue($values[$tmp['nId']]['nAmount']);
                    $price->setValue($values[$tmp['nId']]['nPrice']);
                    $restriction->setValue($values[$tmp['nId']]['nRestriction']);
                } else {
                    $direction->setValue(0);
                    $amount->setValue(0);
                    $price->setValue(0);
                    $restriction->setValue(0);
                }

                $subForm->addElement($direction)
                ->addElement($amount)
                ->addElement($price)
                ->addElement($restriction);

                // alle 5 Spalten hinzufügen
                $this->addSubForms(
                    array('"'.$tmp['nId'].'"' => $subForm,)
                );
            }
        }
        $submit = new Zend_Form_Element_Submit('send');
        $submit->setAttribs(array('class' => 'button'))
            ->setDecorators(array(
                'ViewHelper',
                'Errors',
                array('HtmlTag', array('tag' => 'td','colspan' => 5)),
        ));

        $this->addElement($submit);
    }
}