# LockingApi

Interface: `\Zikula\PageLockModule\Api\LockingApi\LockingApiInterface`
Class: `\Zikula\PageLockModule\Api\LockingApi`

This class is used to work with page locks. You can require and release locks and determine information
about currently existing locks.

The class makes the following methods available:

```php
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
```
