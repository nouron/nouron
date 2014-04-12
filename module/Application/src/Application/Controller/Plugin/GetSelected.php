<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;

class GetSelected extends AbstractPlugin
{
    /**
     *
     * @param string $itemType
     * @return integer|null
     */
    public function __invoke($itemType)
    {
        #$sm = $this->getController()->getServiceLocator();
        $itemType = strtolower($itemType);
        switch ($itemType) {
            case 'user':   $idKey = 'uid'; break;
            case 'system': $idKey = 'sid'; break;
            case 'object': $idKey = 'oid'; break;
            case 'colony': $idKey = 'cid'; break;
            case 'fleet':  $idKey = 'fid'; break;
            case 'tech':   $idKey = 'tid'; break;
            default:       $idKey = 'id';  break;
        }

        $itemId = $this->getController()->params()->fromRoute($idKey);
        if (!$itemId) {
            $identifier = $itemType+'Id';
            $session = new Container('selectedIds');
            $itemId = $session->$identifier;
        }

        $session->$identifier = $itemId;
        return $itemId;
    }
}