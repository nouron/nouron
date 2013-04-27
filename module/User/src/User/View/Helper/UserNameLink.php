<?php
namespace User\View\Helper;
use Zend\View\Helper\AbstractHelper;

class UserNameLink extends AbstractHelper
{
    public function __invoke($userId)
    {
        $url  = 'test';
        $name = 'aaaa';
        $xhtml  = '<a href="' . $url . '">' . $name . '</a>';
        return $xhtml;
    }
}