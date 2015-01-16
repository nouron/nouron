<?php
namespace Map;

use Core\Module as CoreModule;

class Module extends CoreModule
{
    public function onBootstrap($e)
    {
        \Locale::setDefault('de_DE');
        $sm = $e->getApplication()->getServiceManager();
        $translator = $sm->get('translator');

        \Zend\Validator\AbstractValidator::setDefaultTranslator(
            new \Zend\Mvc\I18n\Translator($translator)
        );
    }

}
