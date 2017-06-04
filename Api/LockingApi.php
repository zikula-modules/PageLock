<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\PageLockModule\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig_Environment;
use Zikula\PageLockModule\Api\ApiInterface\LockingApiInterface;
use Zikula\PageLockModule\Entity\PageLockEntity;
use Zikula\PageLockModule\Entity\Repository\PageLockRepository;
use Zikula\ThemeModule\Engine\Asset;
use Zikula\ThemeModule\Engine\AssetBag;
use Zikula\UsersModule\Api\ApiInterface\CurrentUserApiInterface;

/**
 * Class LockingApi.
 *
 * This class provides means for using a locking mechanism.
 */
class LockingApi implements LockingApiInterface
{
    /**
     * Amount of required/opened accesses.
     */
    public static $pageLockAccessCount = 0;

    /**
     * Reference to file containing the internal lock.
     */
    public static $pageLockFile;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var PageLockRepository
     */
    private $repository;

    /**
     * @var CurrentUserApiInterface
     */
    private $currentUserApi;

    /**
     * @var AssetBag
     */
    private $jsAssetBag;

    /**
     * @var AssetBag
     */
    private $cssAssetBag;

    /**
     * @var AssetBag
     */
    private $footerAssetBag;

    /**
     * @var Asset
     */
    private $assetHelper;

    /**
     * @var string
     */
    private $tempDirectory;

    /**
     * LockingApi constructor.
     *
     * @param Twig_Environment $twig
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $entityManager
     * @param PageLockRepository $repository
     * @param CurrentUserApiInterface $currentUserApi
     * @param AssetBag $jsAssetBag AssetBag
     * @param AssetBag $cssAssetBag AssetBag
     * @param AssetBag $footerAssetBag AssetBag
     * @param Asset $assetHelper Asset
     * @param string $tempDir
     */
    public function __construct(
        Twig_Environment $twig,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        PageLockRepository $repository,
        CurrentUserApiInterface $currentUserApi,
        AssetBag $jsAssetBag,
        AssetBag $cssAssetBag,
        AssetBag $footerAssetBag,
        Asset $assetHelper,
        $tempDir
    ) {
        $this->twig = $twig;
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
        $this->repository = $repository;
        $this->currentUserApi = $currentUserApi;
        $this->jsAssetBag = $jsAssetBag;
        $this->cssAssetBag = $cssAssetBag;
        $this->footerAssetBag = $footerAssetBag;
        $this->assetHelper = $assetHelper;
        $this->tempDirectory = $tempDir;
    }

    /**
     * {@inheritdoc}
     */
    public function addLock($lockName, $returnUrl = null, $ignoreEmptyLock = false)
    {
        if (empty($lockName) && $ignoreEmptyLock) {
            return true;
        }

        $this->jsAssetBag->add($this->assetHelper->resolve('@ZikulaPageLockModule:js/PageLock.js'));
        $this->cssAssetBag->add($this->assetHelper->resolve('@ZikulaPageLockModule:css/style.css'));

        $lockInfo = $this->requireLock($lockName, $this->currentUserApi->get('uname'), $this->requestStack->getCurrentRequest()->getClientIp());

        $hasLock = $lockInfo['hasLock'];
        if ($hasLock) {
            return true;
        }

        // add a good margin to lock timeout when pinging
        $pingTime = (self::PAGELOCKLIFETIME * 2 / 3);

        $templateParameters = [
            'lockedBy' => $lockInfo['lockedBy'],
            'lockName' => $lockName,
            'hasLock' => $hasLock,
            'returnUrl' => $returnUrl,
            'pingTime' => $pingTime
        ];
        $lockedHtml = $this->twig->render('@ZikulaPageLockModule/lockedWindow.html.twig', $templateParameters);

        $this->footerAssetBag->add($lockedHtml);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function requireLock($lockName, $lockedByTitle, $lockedByIPNo, $sessionId = '')
    {
        $theSessionId = $sessionId != '' ? $sessionId : $this->requestStack->getMasterRequest()->getSession()->getId();

        $this->requireAccess();

        $locks = $this->getLocks($lockName, $sessionId);
        if (count($locks) > 0) {
            $lockedBy = '';
            foreach ($locks as $lock) {
                if (strlen($lockedBy) > 0) {
                    $lockedBy .= ', ';
                }
                $lockedBy .= $lock['title'] . " ($lock[ipno]) " . $lock['cdate']->format('Y-m-d H:m:s');
            }

            return ['hasLock' => false, 'lockedBy' => $lockedBy];
        }

        // Look for existing lock
        $count = $this->repository->getActiveLockAmount($lockName, $theSessionId);

        $expireDate = new \DateTime();
        $expireDate->setTimestamp(time() + self::PAGELOCKLIFETIME);

        if ($count > 0) {
            // update the existing lock with a new expiry date
            $this->repository->updateExpireDate($lockName, $theSessionId, $expireDate);
        } else {
            // create the new object
            $newLock = new PageLockEntity();
            $newLock->setName($lockName);
            $newLock->setCdate(new \DateTime());
            $newLock->setEdate($expireDate);
            $newLock->setSession($theSessionId);
            $newLock->setTitle($lockedByTitle);
            $newLock->setIpno($lockedByIPNo);
            $this->entityManager->persist($newLock);
        }
        $this->entityManager->flush();

        $this->releaseAccess();

        return ['hasLock' => true];
    }

    /**
     * {@inheritdoc}
     */
    public function getLocks($lockName, $sessionId = '')
    {
        $theSessionId = $sessionId != '' ? $sessionId : $this->requestStack->getMasterRequest()->getSession()->getId();

        $this->requireAccess();

        // remove expired locks
        $this->repository->deleteExpiredLocks();

        // get remaining active locks
        $locks = $this->repository->getActiveLocks($lockName, $theSessionId);

        $this->releaseAccess();

        return $locks;
    }

    /**
     * {@inheritdoc}
     */
    public function releaseLock($lockName, $sessionId = '')
    {
        $theSessionId = $sessionId != '' ? $sessionId : $this->requestStack->getMasterRequest()->getSession()->getId();

        $this->requireAccess();

        $this->repository->deleteByLockName($lockName, $theSessionId);

        $this->releaseAccess();

        return true;
    }

    /**
     * Internal locking mechanism to avoid concurrency inside the PageLock functions.
     *
     * @return void
     */
    private function requireAccess()
    {
        if (null === self::$pageLockAccessCount) {
            self::$pageLockAccessCount = 0;
        }

        if (self::$pageLockAccessCount == 0) {
            self::$pageLockFile = fopen($this->tempDirectory . '/pagelock.lock', 'w+');
            flock(self::$pageLockFile, LOCK_EX);
            fwrite(self::$pageLockFile, 'This is a locking file for synchronizing access to the PageLock module. Please do not delete.');
            fflush(self::$pageLockFile);
        }

        ++self::$pageLockAccessCount;
    }

    /**
     * Internal locking mechanism to avoid concurrency inside the PageLock functions.
     *
     * @return void
     */
    private function releaseAccess()
    {
        --self::$pageLockAccessCount;

        if (self::$pageLockAccessCount == 0) {
            flock(self::$pageLockFile, LOCK_UN);
            fclose(self::$pageLockFile);
        }
    }
}
