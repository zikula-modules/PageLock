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

namespace Zikula\PageLockModule\Entity\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Zikula\PageLockModule\Entity\PageLockEntity;

/**
 * Repository class used to implement own convenience methods for performing certain DQL queries.
 *
 * This is the repository class for pagelock entities.
 */
class PageLockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageLockEntity::class);
    }

    /**
     * Returns amount of active locks.
     *
     * @throws InvalidArgumentException Thrown if invalid parameters are received
     * @throws NonUniqueResultException
     */
    public function getActiveLockAmount(string $lockName, string $sessionId): int
    {
        if ('' === $lockName || '' === $sessionId) {
            throw new InvalidArgumentException('Invalid parameter received.');
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('COUNT(tbl.id)');
        $qb = $this->addCommonFilters($qb, $lockName, $sessionId);

        $query = $qb->getQuery();

        return (int)$query->getSingleScalarResult();
    }

    /**
     * Returns active locks.
     *
     * @throws InvalidArgumentException Thrown if invalid parameters are received
     */
    public function getActiveLocks(string $lockName, string $sessionId): array
    {
        if ('' === $lockName || '' === $sessionId) {
            throw new InvalidArgumentException('Invalid parameter received.');
        }

        $qb = $this->createQueryBuilder('tbl')
            ->select('tbl')
            ->where('tbl.name = :lockName')
            ->setParameter('lockName', $lockName)
            ->andWhere('tbl.session != :sessionId')
            ->setParameter('sessionId', $sessionId);

        $query = $qb->getQuery();

        $locks = $query->getArrayResult();

        // now flush to database
        $this->getEntityManager()->flush();

        return $locks;
    }

    /**
     * Updates the expire date of affected lock.
     *
     * @throws InvalidArgumentException Thrown if invalid parameters are received
     */
    public function updateExpireDate(string $lockName, string $sessionId, DateTime $expireDate): void
    {
        // check parameters
        if ('' === $lockName || '' === $sessionId) {
            throw new \InvalidArgumentException('Invalid parameter received.');
        }

        $qb = $this->createQueryBuilder('tbl');
        $qb->update('Zikula\PageLockModule\Entity\PageLockEntity', 'tbl')
           ->set('tbl.edate', $qb->expr()->literal($expireDate->format('Y-m-d H:i:s')));
        $qb = $this->addCommonFilters($qb, $lockName, $sessionId);

        $query = $qb->getQuery();
        $query->execute();
    }

    /**
     * Deletes all locks which expired.
     */
    public function deleteExpiredLocks(): void
    {
        $qb = $this->createQueryBuilder('tbl')
            ->delete('Zikula\PageLockModule\Entity\PageLockEntity', 'tbl')
            ->where('tbl.edate < :now')
            ->setParameter('now', new DateTime());
        $query = $qb->getQuery();

        $query->execute();
    }

    /**
     * Deletes a lock for a given name.
     *
     * @throws InvalidArgumentException Thrown if invalid parameters are received
     */
    public function deleteByLockName(string $lockName, string $sessionId): void
    {
        if ('' === $lockName || '' === $sessionId) {
            throw new \InvalidArgumentException('Invalid parameter received.');
        }

        $qb = $this->createQueryBuilder('tbl')
            ->delete('Zikula\PageLockModule\Entity\PageLockEntity', 'tbl');
        $qb = $this->addCommonFilters($qb, $lockName, $sessionId);

        $query = $qb->getQuery();

        $query->execute();

        // now flush to database
        $this->getEntityManager()->flush();
    }

    /**
     * Adds common filters to the given query builder.
     */
    private function addCommonFilters(QueryBuilder $qb, string $lockName, string $sessionId): QueryBuilder
    {
        $qb
           ->where('tbl.name = :lockName')
           ->setParameter('lockName', $lockName)
           ->andWhere('tbl.session = :sessionId')
           ->setParameter('sessionId', $sessionId);

        return $qb;
    }
}
