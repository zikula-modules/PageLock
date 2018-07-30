<?php

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
    const PAGELOCKLIFETIME = 30;

    /**
     * Requires a lock and adds the page locking code to the page header
     *
     * @param string $lockName        The name of the lock to be released
     * @param string $returnUrl       The URL to return control to (optional) (default: null)
     * @param bool   $ignoreEmptyLock Ignore an empty lock name (optional) (default: false)
     *
     * @return bool true
     */
    public function addLock($lockName, $returnUrl = null, $ignoreEmptyLock = false);

    /**
     * Generate a lock on a page
     *
     * @param string $lockName      The name of the page to create/update a lock on
     * @param string $lockedByTitle Name of user owning the current lock
     * @param string $lockedByIPNo  Ip address of user owning the current lock
     * @param string $sessionId     The ID of the session owning the lock (optional) (default: current session ID)
     *
     * @return ['haslock' => true if this user has a lock, false otherwise,
     *          'lockedBy' => if 'haslock' is false then the user who has the lock, null otherwise]
     */
    public function requireLock($lockName, $lockedByTitle, $lockedByIPNo, $sessionId = '');

    /**
     * Get all the locks for a given page
     *
     * @param string $lockName  The name of the page to return locks for
     * @param string $sessionId The ID of the session owning the lock (optional) (default: current session ID)
     *
     * @return array array of locks for $lockName
     */
    public function getLocks($lockName, $sessionId = '');

    /**
     * Releases a lock on a page
     *
     * @param string $lockName  The name of the lock to be released
     * @param string $sessionId The ID of the session owning the lock (optional) (default: current session ID)
     *
     * @return bool true
     */
    public function releaseLock($lockName, $sessionId = '');
}
