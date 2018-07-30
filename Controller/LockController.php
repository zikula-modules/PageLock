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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Response\Ajax\AjaxResponse;

/**
 * @Route("/lock")
 *
 * Lock controller for the pagelock module
 */
class LockController extends AbstractController
{
    /**
     * @Route("/refresh", methods = {"POST"}, options={"expose"=true})
     *
     * Refresh a page lock.
     *
     * @param Request $request
     *
     * @return AjaxResponse containing { hasLock: bool, message: string, lockedBy: string, message:string|null }
     */
    public function refreshpagelockAction(Request $request)
    {
        $lockInfo = $this->getLockInfo($request);

        return new AjaxResponse($lockInfo);
    }

    /**
     * @Route("/check", methods = {"POST"}, options={"expose"=true})
     *
     * Change a page lock.
     *
     * @param Request $request
     *
     * @return AjaxResponse containing { hasLock: bool, message: string, lockedBy: string, message:string|null }
     */
    public function checkpagelockAction(Request $request)
    {
        $lockInfo = $this->getLockInfo($request);

        return new AjaxResponse($lockInfo);
    }

    /**
     * Requires a lock and returns it's information.
     *
     * @param Request $request
     *
     * @return array Lock information data
     */
    private function getLockInfo(Request $request)
    {
        $lockName = $request->request->get('lockname');
        $userName = $this->get('zikula_users_module.current_user')->get('uname');

        $lockInfo = $this->get('zikula_pagelock_module.api.locking')
            ->requireLock($lockName, $userName, $request->getClientIp(), $request->getSession()->getId());

        $lockInfo['message'] = $lockInfo['hasLock'] ? null : $this->__('Error! Lock broken!');

        return $lockInfo;
    }
}
