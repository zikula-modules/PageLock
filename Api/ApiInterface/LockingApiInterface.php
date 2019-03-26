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

namespace Zikula\PageLockModule\Api\ApiInterface;

/**
 * This class provides means for using a locking mechanism.
 */
interface LockingApiInterface
{
    /**
     * length of time to lock a page
     */
    public const PAGELOCKLIFETIME = 30;

    /**
     * Requires a lock and adds the page locking code to the page header.
     */
    public function addLock(string $lockName, string $returnUrl = null, bool $ignoreEmptyLock = false): void;

    /**
     * Generate a lock on a page.
     */
    public function requireLock(string $lockName, string $lockedByTitle, string $lockedByIPNo, string $sessionId = ''): array;

    /**
     * Get all the locks for a given page.
     */
    public function getLocks(string $lockName, string $sessionId = ''): array;

    /**
     * Releases a lock on a page.
     */
    public function releaseLock(string $lockName, string $sessionId = ''): void;
}
