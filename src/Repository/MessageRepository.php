<?php

namespace App\Repository;

use App\Entity\Message;
use App\Model\SearchData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Message::class);
        $this->entityManager = $entityManager;

    }

    public function save(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return Message[] Returns an array of Message objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Message
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    public function messagesByCategories($category)
    {

        $query = $this->createQueryBuilder('c')
            ->Where('c.category = :val')
            ->setParameter('val', $category)
            ->orderBy('c.id', 'ASC');

        return $query
            ->getQuery()
            ->getResult();
    }

    public function messagesBySubCategories($category, $subCategory)
    {

        $query = $this->createQueryBuilder('c')
            ->Where('c.category = :val')
            ->andWhere('c.subCategory = :val2')
            ->setParameter('val', $category)
            ->setParameter('val2', $subCategory)
            ->orderBy('c.id', 'ASC');

        return $query
            ->getQuery()
            ->getResult();
    }

    public function messagesByResponses($category, $response)
    {
            $query = $this->createQueryBuilder('c')
                ->Where('c.category = :val')
                ->andWhere('c.response_id != :id')
                ->setParameter('val', $category)
                ->setParameter('id', 0);

        return $query
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }



    public function recup($id)
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT m1
        FROM App\Entity\Message m1
        LEFT JOIN App\Entity\Message m2 WITH m1.id = m2.response_id
        WHERE m1.id = :responseId'
        )->setParameter('responseId', $id);

        return $query->getResult();
    }

    public function findBySearch(SearchData $searchData, $category, $subCategory)
    {

//        dd($subCategory);
        $qb = $this->createQueryBuilder('p');
        $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->like('p.pseudo', ':query'),
                        $qb->expr()->like('p.content', ':query'),
                    ),
                )
            )
            ->andWhere('p.category = :category')
            ->andWhere('p.subCategory = :subCategory')
            ->setParameter('category', $category)
            ->setParameter('subCategory', $subCategory)
            ->setParameter('query', '%' . $searchData . '%')
        ;
        return $qb
            ->getQuery()
            ->getResult();
    }
}
