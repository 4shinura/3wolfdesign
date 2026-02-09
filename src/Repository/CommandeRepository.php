<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function countOrders(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPendingOrders(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status != :status OR c.status IS NULL') // Sécurité si le statut est null
            ->setParameter('status', 'Terminé')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalSales(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.prix_total)')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function getTotalSalesThisMonth(): float
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');

        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.prix_total)')
            ->where('c.dateCreation >= :start')
            ->setParameter('start', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return (float)($result ?? 0);
    }

    public function findAllWithDetails(?string $search = null, ?string $status = null, string $sortBy = 'dateCreation', string $order = 'ASC'): array
    {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.details_commande', 'd') 
            ->addSelect('d');

        if ($search) {
            $query->andWhere('c.reference LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status && $status !== 'tous') {
        $query->andWhere('c.status = :stat')
              ->setParameter('stat', $status);
        }   

        // Tri dynamique
        $allowedSorts = ['dateCreation', 'status', 'prix_total']; // Utilisation de $sortBy passée en paramètre
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy('c.' . $sortBy, ($order === 'DESC') ? 'DESC' : 'ASC');
        } else {
            $query->orderBy('c.dateCreation', 'ASC');
        }

        return $query->getQuery()->getResult();
    }
}
