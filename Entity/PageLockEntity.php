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

namespace Zikula\PageLockModule\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Pagelock
 *
 * @ORM\Entity(repositoryClass="Zikula\PageLockModule\Entity\Repository\PageLockRepository")
 * @ORM\Table(name="pagelock")
 */
class PageLockEntity
{
    /**
     * Pagelock ID
     *
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Pagelock name
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * Creation date of the pagelock
     *
     * @var DateTime
     *
     * @ORM\Column(name="cdate", type="datetime", nullable=false)
     */
    private $cdate;

    /**
     * Expiry date of the pagelock
     *
     * @var DateTime
     *
     * @ORM\Column(name="edate", type="datetime", nullable=false)
     */
    private $edate;

    /**
     * Session ID for this pagelock
     *
     * @var string
     *
     * @ORM\Column(name="session", type="string", length=50, nullable=false)
     */
    private $session;

    /**
     * Title of the pagelock
     *
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=false)
     */
    private $title;

    /**
     * IP address of the machine acquiring the pagelock
     *
     * @var string
     *
     * @ORM\Column(name="ipno", type="string", length=40, nullable=false)
     */
    private $ipno;

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): PageLockEntity
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCdate(DateTime $cdate): PageLockEntity
    {
        $this->cdate = $cdate;

        return $this;
    }

    public function getCdate(): DateTime
    {
        return $this->cdate;
    }

    public function setEdate(DateTime $edate): PageLockEntity
    {
        $this->edate = $edate;

        return $this;
    }

    public function getEdate(): DateTime
    {
        return $this->edate;
    }

    public function setSession(string $session): PageLockEntity
    {
        $this->session = $session;

        return $this;
    }

    public function getSession(): string
    {
        return $this->session;
    }

    public function setTitle(string $title): PageLockEntity
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setIpno(string $ipno): PageLockEntity
    {
        $this->ipno = $ipno;

        return $this;
    }

    public function getIpno(): string
    {
        return $this->ipno;
    }
}
