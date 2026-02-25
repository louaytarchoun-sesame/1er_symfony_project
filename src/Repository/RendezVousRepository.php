<?php
namespace App\Repository;

use App\Entity\RendezVous;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }

    public function findRendezVousByMedecin(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.medecin) as medecin, COUNT(r.id) as count')
            ->groupBy('r.medecin');
        $result = $qb->getQuery()->getResult();
        // Optionally map medecin id to name
        return $result;
    }
}
