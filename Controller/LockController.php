<?php

declare(strict_types=1);

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
use Symfony\Component\Routing\Annotation\Route;
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
     * @Route("/refresh", methods={"POST"}, options={"expose"=true})
     *
     * Refresh a page lock.
     */
    public function refreshpagelockAction(
        Request $request,
        LockingApiInterface $lockingApi,
        CurrentUserApiInterface $currentUserApi
    ): JsonResponse {
        $lockInfo = $this->getLockInfo($request, $lockingApi, $currentUserApi);

        return $this->json($lockInfo);
    }

    /**
     * @Route("/check", methods={"POST"}, options={"expose"=true})
     *
     * Change a page lock.
     */
    public function checkpagelockAction(
        Request $request,
        LockingApiInterface $lockingApi,
        CurrentUserApiInterface $currentUserApi
    ): JsonResponse {
        $lockInfo = $this->getLockInfo($request, $lockingApi, $currentUserApi);

        return $this->json($lockInfo);
    }

    /**
     * Requires a lock and returns it's information.
     */
    private function getLockInfo(
        Request $request,
        LockingApiInterface $lockingApi,
        CurrentUserApiInterface $currentUserApi
    ): array {
        $lockName = $request->request->get('lockname');
        $userName = $currentUserApi->get('uname');

        $lockInfo = $lockingApi->requireLock($lockName, $userName, $request->getClientIp(), $request->getSession()->getId());

        $lockInfo['message'] = $lockInfo['hasLock'] ? null : $this->__('Error! Lock broken!');

        return $lockInfo;
    }
}
