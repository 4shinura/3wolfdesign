<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\Utilisateur;
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

    public function getPaginationQuery(?string $search, ?string $status, string $sortBy, string $order)
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.utilisateur', 'u')
            ->leftJoin('c.details_commande', 'd')
            ->leftJoin('d.produit', 'p')
            ->addSelect('u', 'd', 'p');

        // Filtre Recherche
        if ($search) {
            $qb->andWhere('c.reference LIKE :search OR c.nom LIKE :search OR c.prenom LIKE :search OR u.email LIKE :search')
            ->setParameter('search', '%' . $search . '%');
        }

        // Filtre Statut
        if ($status && $status !== 'tous') {
            $qb->andWhere('c.status = :status')
            ->setParameter('status', $status);
        }

        // Gestion du Tri (Sécurisation des colonnes)
        $validSorts = ['dateCreation', 'status', 'prixTotal'];
        $sortBy = in_array($sortBy, $validSorts) ? 'c.' . $sortBy : 'c.dateCreation';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy($sortBy, $order);

        return $qb->getQuery();
    }

    public function getPaginationUserOrdersQuery(Utilisateur $user)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery();
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
