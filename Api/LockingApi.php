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

namespace Zikula\PageLockModule\Api;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
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
     * @var Environment
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

    public function __construct(
        Environment $twig,
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        PageLockRepository $repository,
        CurrentUserApiInterface $currentUserApi,
        AssetBag $jsAssetBag,
        AssetBag $cssAssetBag,
        AssetBag $footerAssetBag,
        Asset $assetHelper,
        string $tempDir
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

    public function addLock(string $lockName, string $returnUrl = null, bool $ignoreEmptyLock = false): void
    {
        if (empty($lockName) && $ignoreEmptyLock) {
            return;
        }

        $this->jsAssetBag->add($this->assetHelper->resolve('@ZikulaPageLockModule:js/PageLock.js'));
        $this->cssAssetBag->add($this->assetHelper->resolve('@ZikulaPageLockModule:css/style.css'));

        $lockInfo = $this->requireLock($lockName, $this->currentUserApi->get('uname'), $this->requestStack->getCurrentRequest()->getClientIp());

        $hasLock = $lockInfo['hasLock'];
        if ($hasLock) {
            return;
        }

        // add a good margin to lock timeout when pinging
        $pingTime = (LockingApiInterface::PAGELOCKLIFETIME * 2 / 3);

        $templateParameters = [
            'lockedBy' => $lockInfo['lockedBy'],
            'lockName' => $lockName,
            'hasLock' => $hasLock,
            'returnUrl' => $returnUrl,
            'pingTime' => $pingTime
        ];
        $lockedHtml = $this->twig->render('@ZikulaPageLockModule/lockedWindow.html.twig', $templateParameters);

        $this->footerAssetBag->add($lockedHtml);
    }

    public function requireLock(string $lockName, string $lockedByTitle, string $lockedByIPNo, string $sessionId = ''): array
    {
        $theSessionId = '' !== $sessionId ? $sessionId : $this->requestStack->getMasterRequest()->getSession()->getId();

        $this->requireAccess();

        $locks = $this->getLocks($lockName, $sessionId);
        if (count($locks) > 0) {
            $lockedBy = '';
            foreach ($locks as $lock) {
                if ('' !== $lockedBy) {
                    $lockedBy .= ', ';
                }
                $lockedBy .= $lock->getTitle() . ' (' . $lock->getIpno() . ') ' . $lock->getCdate()->format('Y-m-d H:m:s');
            }

            return ['hasLock' => false, 'lockedBy' => $lockedBy];
        }

        // Look for existing lock
        $count = $this->repository->getActiveLockAmount($lockName, $theSessionId);

        $expireDate = new DateTime();
        $expireDate->setTimestamp(time() + LockingApiInterface::PAGELOCKLIFETIME);

        if ($count > 0) {
            // update the existing lock with a new expiry date
            $this->repository->updateExpireDate($lockName, $theSessionId, $expireDate);
        } else {
            // create the new object
            $newLock = new PageLockEntity();
            $newLock->setName($lockName);
            $newLock->setCdate(new DateTime());
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

    public function getLocks(string $lockName, string $sessionId = ''): array
    {
        $theSessionId = '' !== $sessionId ? $sessionId : $this->requestStack->getMasterRequest()->getSession()->getId();

        $this->requireAccess();

        // remove expired locks
        $this->repository->deleteExpiredLocks();

        // get remaining active locks
        $locks = $this->repository->getActiveLocks($lockName, $theSessionId);

        $this->releaseAccess();

        return $locks;
    }

    public function releaseLock(string $lockName, string $sessionId = ''): void
    {
        $theSessionId = '' !== $sessionId ? $sessionId : $this->requestStack->getMasterRequest()->getSession()->getId();

        $this->requireAccess();

        $this->repository->deleteByLockName($lockName, $theSessionId);

        $this->releaseAccess();
    }

    /**
     * Internal locking mechanism to avoid concurrency inside the PageLock functions.
     */
    private function requireAccess(): void
    {
        if (null === self::$pageLockAccessCount) {
            self::$pageLockAccessCount = 0;
        }

        if (0 === self::$pageLockAccessCount) {
            self::$pageLockFile = fopen($this->tempDirectory . '/pagelock.lock', 'wb+');
            flock(self::$pageLockFile, LOCK_EX);
            fwrite(self::$pageLockFile, 'This is a locking file for synchronizing access to the PageLock module. Please do not delete.');
            fflush(self::$pageLockFile);
        }

        ++self::$pageLockAccessCount;
    }

    /**
     * Internal locking mechanism to avoid concurrency inside the PageLock functions.
     */
    private function releaseAccess(): void
    {
        --self::$pageLockAccessCount;

        if (0 === self::$pageLockAccessCount) {
            flock(self::$pageLockFile, LOCK_UN);
            fclose(self::$pageLockFile);
        }
    }
}
