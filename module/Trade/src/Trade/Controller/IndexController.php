<?php
namespace Trade\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Trade\Service\Gateway;
use Techtree\Entity\Technology;
use Resources\Entity\Resource;

class IndexController extends \Nouron\Controller\IngameController
{
    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function addTechnologyOfferAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Trade\Service\Gateway');
        $userService = $sm->get('User\Service\User');

        $techOffers = $gw->getTechnologies();
        $technologyService = $sm->get('Techtree\Service\BuildingService');
        $techs = $technologyService->getTechnologies();
        $techs = $techs->getArrayCopy('id');
        $form = new \Trade\Form\NewOfferForm('technologies', $techs);

        $request = $this->getRequest();
        if ( $request->isPost() ) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $data = (array) $request->getPost();
                $data['user_id']   = $this->getActive('user');
                $data['colony_id'] = $this->getActive('colony');
                $result = $gw->addTechnologyOffer($data);

                if ($result) {
                    $result = new ViewModel();
                    $result->setTerminal(true);
                    return $result;
                }
            }
        }

        $result = new ViewModel(array(
            'form' => $form
        ));
        $result->setTerminal(true);
        return $result;
    }


    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function addResourceOfferAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Trade\Service\Gateway');
        $userService = $sm->get('User\Service\User');

        $resourceOffers = $gw->getResources();
        $resourceService = $sm->get('Resources\Service\ResourcesService');
        $resources = $resourceService->getResources();
        $resources = $resources->getArrayCopy('id');
        $form = new \Trade\Form\NewOfferForm('resources', $resources);

        $request = $this->getRequest();
        if ( $request->isPost() ) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $data = (array) $request->getPost();
                $data['user_id']   = $this->getActive('user');
                $data['colony_id'] = $this->getActive('colony');
                $result = $gw->addResourceOffer($data);

                if ($result) {
                    $result = new ViewModel();
                    $result->setTerminal(true);
                    return $result;
                }
            }
        }

        $result = new ViewModel(array(
            'form' => $form
        ));
        $result->setTerminal(true);
        return $result;
    }

    /**
     *
     * @return \Zend\View\Model\JsonModel
     */
    public function removeOfferAction()
    {
        $request = $this->getRequest();
        if ( $request->isPost() ) {
            $sm = $this->getServiceLocator();
            $gw = $sm->get('Trade\Service\Gateway');

            $data = (array) $request->getPost();
            if (isset($data['resource_id'])) {
                $result = $gw->removeResourceOffer($data);
            } elseif (isset($data['tech_id'])) {
                $result = $gw->removeTechnologyOffer($data);
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return new JsonModel(array('result'=>$result));
    }

    /**
     *
     * @param array $offers
     * @return \Zend\Paginator\Paginator
     */
    private function _initPaginator($offers)
    {
        \Zend\Paginator\Paginator::setDefaultScrollingStyle('Sliding');
        \Zend\View\Helper\PaginationControl::setDefaultViewPartial(
            'layout/pagination_control.phtml'
        );

        $page = $this->params()->fromRoute('page');
        $page = $page ? $page : 1;
        $paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($offers->getArrayCopy()));
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function technologiesAction()
    {
        $sm = $this->getServiceLocator();
        $sm->setService('colonyId', 1); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Trade\Service\Gateway');

        $buildingService = $sm->get('Techtree\Service\BuildingService');
        $techs = $buildingService->getEntities();

        $tradeService = $sm->get('Trade\Service\Gateway');
        $userService = $sm->get('User\Service\User');
        $techs = $techs->getArrayCopy('id');
        $searchForm = new \Trade\Form\SearchForm('technologies', $techs);
        $newOfferForm = new \Trade\Form\NewOfferForm('technologies', $techs);

        $where = array();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            if ($post['form_name'] == 'search') {
                $searchForm->setData($request->getPost());
                if ($searchForm->isValid()) {
                    print_r('valid');
                    $where['direction'] = $post['direction'];
                    if (!empty($post['item_id'])) {
                        $where['tech_id'] = $post['item_id'];
                    }
                } else {
                    print_r($searchForm->getMessages());
                }
            }
         }

        $techOffers = $gw->getTechnologies($where);

        return new ViewModel( array(
            'user_id' => $this->getActive('user'),
            'searchForm' => $searchForm,
            'newOfferForm' => $newOfferForm,
            'paginator' => $this->_initPaginator($techOffers),
            'technologies' => $techs,
        ));
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function resourcesAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 1); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $resourceService = $sm->get('Resources\Service\ResourcesService');
        $resources = $resourceService->getResources();

        $tradeService = $sm->get('Trade\Service\Gateway');
        $userService = $sm->get('User\Service\User');

        $resources = $resources->getArrayCopy('id');
        $searchForm = new \Trade\Form\SearchForm('resources', $resources);
        $newOfferForm = new \Trade\Form\NewOfferForm('resources', $resources);
        $where = array();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            if ($post['form_name'] == 'search') {
                $searchForm->setData($request->getPost());
                if ($searchForm->isValid()) {
                    print_r('valid');
                    $where['direction'] = $post['direction'];
                    if (!empty($post['item_id'])) {
                        $where['resource_id'] = $post['item_id'];
                    }
                } else {
                    print_r($searchForm->getMessages());
                }
            }
         }
        $resourceOffers = $tradeService->getResources($where);

        return new ViewModel( array(
            'user_id' => $this->getActive('user'),
            'searchForm' => $searchForm,
            'newOfferForm' => $newOfferForm,
            'paginator' => $this->_initPaginator($resourceOffers),
            'resources' => $resources,
        ));
    }

//     protected function _processSearchForm($searchForm, $data)
//     {
//         $resource = new Resource();
//         $searchForm->bind($resource);
//         $searchForm->setData($data);

//         if ($searchForm->isValid()) {
//             var_dump($resource);
//         }
//         return $searchForm;
//     }

//     protected function _processNewOfferForm($newOfferForm, $data)
//     {
// //         $resource = new NewOffer();
// //         $form->bind($resource);
//         $newOfferForm->setData($data);

//         if ($newOfferForm->isValid()) {
//             $sm = $this->getServiceLocator();
//             $sm->setService('colonyId', 1); // TODO: get colonyId via controller plugin or session
//             $gw = $sm->get('Trade\Service\Gateway');
//             $result = $gw->storeNewOffer($data);
//         }
//         return $newOfferForm;
//     }
}

