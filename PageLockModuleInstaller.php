<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\PageLockModule;

use Zikula\Core\AbstractExtensionInstaller;
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

    /**
     * Initialise the module.
     *
     * @return boolean True if initialisation successful, false otherwise
     */
    public function install()
    {
        try {
            $this->schemaTool->create($this->entities);
        } catch (\Exception $exception) {
            $this->addFlash('error', $exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * upgrade the module from an old version
     *
     * @param string $oldVersion version number string to upgrade from
     *
     * @return bool true as there are no upgrade routines currently
     */
    public function upgrade($oldVersion)
    {
        // Upgrade dependent on old version number
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

    /**
     * delete the Pagelock module
     *
     * @return bool true if deletion successful, false otherwise
     */
    public function uninstall()
    {
        try {
            $this->schemaTool->drop($this->entities);
        } catch (\PDOException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return false;
        }

        // Delete any module variables.
        $this->delVars();

        // Deletion successful.
        return true;
    }
}
