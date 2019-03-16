<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\PageLockModule\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Zikula\Core\Controller\AbstractController;
use Zikula\PageLockModule\Api\ApiInterface\LockingApiInterface;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;

/**
 * @Route("/lock")
 *
 * Lock controller for the pagelock module
 */
class LockController extends AbstractController
{
    /**
     * @Route("/refresh", options={"expose"=true})
     * @Method("POST")
     *
     * Refresh a page lock.
     *
     * @param Request $request
     * @param LockingApiInterface $lockingApi
     * @param CurrentUserApiInterface $currentUserApi
     *
     * @return JsonResponse
     */
    public function refreshpagelockAction(Request $request, LockingApiInterface $lockingApi, CurrentUserApiInterface $currentUserApi)
    {
        $lockInfo = $this->getLockInfo($request, $lockingApi, $currentUserApi);

        return $this->json($lockInfo);
    }

    /**
     * @Route("/check", options={"expose"=true})
     * @Method("POST")
     *
     * Change a page lock.
     *
     * @param Request $request
     * @param LockingApiInterface $lockingApi
     * @param CurrentUserApiInterface $currentUserApi
     *
     * @return JsonResponse
     */
    public function checkpagelockAction(Request $request, LockingApiInterface $lockingApi, CurrentUserApiInterface $currentUserApi)
    {
        $lockInfo = $this->getLockInfo($request, $lockingApi, $currentUserApi);

        return $this->json($lockInfo);
    }

    /**
     * Requires a lock and returns it's information.
     *
     * @param Request $request
     * @param LockingApiInterface $lockingApi
     * @param CurrentUserApiInterface $currentUserApi
     *
     * @return array Lock information data
     */
    private function getLockInfo(Request $request, LockingApiInterface $lockingApi, CurrentUserApiInterface $currentUserApi)
    {
        $lockName = $request->request->get('lockname');
        $userName = $currentUserApi->get('uname');

        $lockInfo = $lockingApi->requireLock($lockName, $userName, $request->getClientIp(), $request->getSession()->getId());

        $lockInfo['message'] = $lockInfo['hasLock'] ? null : $this->__('Error! Lock broken!');

        return $lockInfo;
    }
}
