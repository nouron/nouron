<?php
namespace Trade\Controller;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Trade\Service\Gateway;
use Techtree\Entity\Technology;
use Resources\Entity\Resource;

class IndexController extends \Nouron\Controller\IngameController
{
    public function addOfferAction()
    {
        $sm = $this->getServiceLocator();
        $gw = $sm->get('Trade\Service\Gateway');
        $resourceOffers = $gw->getResources();

        $resourceService = $sm->get('Resources\Service\Gateway');
        $resources = $resourceService->getResources();

        $tradeService = $sm->get('Trade\Service\Gateway');
        $userService = $sm->get('User\Service\User');
        $resources = $resources->getArrayCopy('id');
        $form = new \Trade\Form\NewOfferForm('resources', $resources);

        $request = $this->getRequest();
        if ( $request->isPost() ) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $sm = $this->getServiceLocator();
                $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session
                $gw = $sm->get('Trade\Service\Gateway');
                $result = $gw->addOffer($request->getPost());
                if (empty($result)) {
                    $result = new ViewModel();
                    $result->setTerminal(true);
                    return null;
                }
            }
        }

        $result = new ViewModel(array(
            'form' => $form
        ));
        $result->setTerminal(true);
        return $result;
    }

    public function technologiesAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Trade\Service\Gateway');

        $techGw = $sm->get('Techtree\Service\Gateway');
        $techs = $techGw->getTechnologies();
        $searchForm = new \Trade\Form\SearchForm('technologies', $techs->getArrayCopy('id'));

        $tradeService = $sm->get('Trade\Service\Gateway');
        $userService = $sm->get('User\Service\User');

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
            'searchForm' => $searchForm,
            'paginator' => $this->_initPaginator($techOffers->getArrayCopy()),
        ));
    }

    /**
     *
     * @param array $offers
     * @return \Zend\Paginator\Paginator
     */
    private function _initPaginator(array $offers)
    {
        \Zend\Paginator\Paginator::setDefaultScrollingStyle('Sliding');
        \Zend\View\Helper\PaginationControl::setDefaultViewPartial(
            'layout/pagination_control.phtml'
        );

        $page = $this->params()->fromRoute('page');
        $page = $page ? $page : 1;
        $paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($offers));
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }

    public function resourcesAction()
    {
        $sm = $this->getServiceLocator();

        $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session

        $colonyId = $sm->get('colonyId');
        $tick     = $sm->get('Nouron\Service\Tick');

        $gw = $sm->get('Trade\Service\Gateway');

        $resourceService = $sm->get('Resources\Service\Gateway');
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


        $resourceOffers = $gw->getResources($where);

        return new ViewModel( array(
            'searchForm' => $searchForm,
            'newOfferForm' => $newOfferForm,
            'paginator' => $this->_initPaginator($resourceOffers->getArrayCopy()),
            'resources' => $resources,
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
            $sm = $this->getServiceLocator();
            $sm->setService('colonyId', 0); // TODO: get colonyId via controller plugin or session
            $gw = $sm->get('Trade\Service\Gateway');
            $result = $gw->storeNewOffer($data);
        }
        return $newOfferForm;
    }
}

