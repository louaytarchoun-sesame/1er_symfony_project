<?php

namespace App\Repository;

use App\Entity\ResetPasswordToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResetPasswordToken>
 */
class ResetPasswordTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordToken::class);
    }

    /**
     * Remove all expired tokens (cleanup).
     */
    public function removeExpiredTokens(): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    /**
     * Remove all tokens for a given profil.
     */
    public function removeTokensForProfil(int $profilId): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.profil = :pid')
            ->setParameter('pid', $profilId)
            ->getQuery()
            ->execute();
    }
}
