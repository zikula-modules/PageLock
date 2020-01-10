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
use Symfony\Component\Validator\Constraints as Assert;

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
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    private $id;

    /**
     * Pagelock name
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     * @Assert\Length(min="0", max="100", allowEmptyString="false")
     * @var string
     */
    private $name;

    /**
     * Creation date of the pagelock
     *
     * @ORM\Column(name="cdate", type="datetime", nullable=false)
     * @var DateTime
     */
    private $cdate;

    /**
     * Expiry date of the pagelock
     *
     * @ORM\Column(name="edate", type="datetime", nullable=false)
     * @var DateTime
     */
    private $edate;

    /**
     * Session ID for this pagelock
     *
     * @ORM\Column(name="session", type="string", length=50, nullable=false)
     * @Assert\Length(min="0", max="50", allowEmptyString="false")
     * @var string
     */
    private $session;

    /**
     * Title of the pagelock
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=false)
     * @Assert\Length(min="0", max="100", allowEmptyString="false")
     * @var string
     */
    private $title;

    /**
     * IP address of the machine acquiring the pagelock
     *
     * @ORM\Column(name="ipno", type="string", length=40, nullable=false)
     * @Assert\Length(min="0", max="40", allowEmptyString="false")
     * @var string
     */
    private $ipno;

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCdate(DateTime $cdate): self
    {
        $this->cdate = $cdate;

        return $this;
    }

    public function getCdate(): DateTime
    {
        return $this->cdate;
    }

    public function setEdate(DateTime $edate): self
    {
        $this->edate = $edate;

        return $this;
    }

    public function getEdate(): DateTime
    {
        return $this->edate;
    }

    public function setSession(string $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getSession(): string
    {
        return $this->session;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setIpno(string $ipno): self
    {
        $this->ipno = $ipno;

        return $this;
    }

    public function getIpno(): string
    {
        return $this->ipno;
    }
}
