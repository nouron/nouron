<?php
namespace Trade\Controller;

use Zend\View\Model\ViewModel;
use Trade\Service\Gateway;
use Techtree\Entity\Technology;
use Resources\Entity\Resource;

class IndexController extends \Nouron\Controller\IngameController
{
    public function technologiesAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Trade\Service\Gateway');
        $techOffers = $gw->getTechnologies();

        $techGw = $sm->get('Techtree\Service\Gateway');
        $techs = $techGw->getTechnologies();
        $form = new \Trade\Form\SearchForm('technologies', $techs->getArrayCopy('id'));
        $tech = new Technology();
        $form->bind($tech);

        $tradeService = $sm->get('Trade\Service\Gateway');
        $userService = $sm->get('User\Service\User');

         $request = $this->getRequest();
         if ($request->isPost()) {
             $form->setData($request->getPost());

             if ($form->isValid()) {
                 var_dump($tech);
             }
         }

        return new ViewModel( array(
            'searchForm' => $form,
            'technologies' => $techOffers
        ) );
    }

    public function resourcesAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Trade\Service\Gateway');
        $resourceOffers = $gw->getResources();

        $resourceService = $sm->get('Resources\Service\Gateway');
        $resources = $resourceService->getResources();

        $tradeService = $sm->get('Trade\Service\Gateway');
        $userService = $sm->get('User\Service\User');
        $resources = $resources->getArrayCopy('id');
        $searchForm = new \Trade\Form\SearchForm('resources', $resources);
        $newOfferForm = new \Trade\Form\NewOfferForm('resources', $resources);

        $request = $this->getRequest();
        if ($request->isPost()) {

            $post = $request->getPost();
            switch ($post['form_name']) {
                case 'new_offer': $newOfferForm = $this->_processNewOfferForm($newOfferForm, $post); break;
                case 'search':    $searchForm   = $this->_processNewOfferForm($searchForm, $post); break;
                default: break;
            }
        }

        return new ViewModel( array(
            'searchForm' => $searchForm,
            'newOfferForm' => $newOfferForm,
            'resourceOffers' => $resourceOffers
        ));
    }

    protected function _processSearchForm($searchForm, $data)
    {
        $resource = new Resource();
        $searchForm->bind($resource);
        $searchForm->setData($data);

        if ($searchForm->isValid()) {
            var_dump($resource);
        }
        return $searchForm;
    }

    protected function _processNewOfferForm($newOfferForm, $data)
    {
//         $resource = new NewOffer();
//         $form->bind($resource);
        $newOfferForm->setData($data);

        if ($newOfferForm->isValid()) {
            var_dump('test');
        }
        return $newOfferForm;
    }
}

