<?php

namespace Experium\ExtraBundle\Controller;

/**
 * @author Alexey Shockov <shokov@experium.ru>
 */
abstract class Controller extends \Symfony\Bundle\FrameworkBundle\Controller\Controller
{
    /**
     * @return \Symfony\Component\Security\Core\User\UserInterface|string
     */
    protected function getCurrentUser()
    {
        return $this->get('security.context')->getToken()->getUser();
    }
}
