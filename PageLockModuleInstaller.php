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

namespace Zikula\PageLockModule;

use Exception;
use PDOException;
use Zikula\ExtensionsModule\Installer\AbstractExtensionInstaller;
use Zikula\PageLockModule\Entity\PageLockEntity;

/**
 * Installation and upgrade routines for the pagelock module.
 */
class PageLockModuleInstaller extends AbstractExtensionInstaller
{
    /**
     * @var array
     */
    private $entities = [
        PageLockEntity::class
    ];

    public function install(): bool
    {
        try {
            $this->schemaTool->create($this->entities);
        } catch (Exception $exception) {
            $this->addFlash('error', $exception->getMessage());

            return false;
        }

        return true;
    }

    public function upgrade(string $oldVersion): bool
    {
        switch ($oldVersion) {
            case '1.1.1':
                $this->schemaTool->update([
                    PageLockEntity::class
                ]);
            case '2.0.0':
                // current version
        }

        return true;
    }

    public function uninstall(): bool
    {
        try {
            $this->schemaTool->drop($this->entities);
        } catch (PDOException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return false;
        }

        // Delete any module variables.
        $this->delVars();

        // Deletion successful.
        return true;
    }
}
