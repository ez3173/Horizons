<?php

namespace App\Repository;

use App\Entity\Journey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Journey>
 */
class JourneyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journey::class);
    }

       /**
        * Recherche les carnets publiés avec filtres optionnels
        * @param string|null $search    mot-clé recherché dans le titre/description
        * @param int|null    $category  id de la catégorie choisie
        */
       public function findByFilters(?string $search = null,?int $categoryId = null)
       {
        $qb = $this->createQueryBuilder('j')
            ->andWhere('j.published = :published')
            ->setParameter('published', true)
            ->orderBy('j.createdAt','DESC');

        if($search){
            $qb->andWhere('j.title LIKE :search OR j.description LIKE :search')
                ->setParameter('search',  '%' . $search . '%');
        }
        if($categoryId){
            $qb->andWhere('j.category = :category')
               ->setParameter('category', $categoryId);
        }

        return $qb->getQuery()->getResult();
       }

}
