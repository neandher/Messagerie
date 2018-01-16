<?php

namespace App\Repository;

use App\Entity\Messages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\RegistryInterface;

class MessagesRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Messages::class);
    }

    public function getMessagesFor($from, $to, $page = 1)
    {
        $qb = $this->createQueryBuilder('m')
            ->addSelect('m')
            ->innerJoin('m.to', 'to')
            ->addSelect('to')
            ->innerJoin('m.from', '_from')
            ->addSelect('_from');

        $where = $qb->expr()->orX()->addMultiple([
            $qb->expr()->andX()->add('m.from = :from')->add('m.to = :to'),
            $qb->expr()->andX()->add('m.from = :to')->add('m.to = :from')
        ]);

        $qb = $qb->where($where)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery();

        return $this->createPaginator($qb, $page);
    }

    private function createPaginator(Query $query, int $page): Pagerfanta
    {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(Messages::NUM_ITEMS);
        $paginator->setCurrentPage($page);
        return $paginator;
    }

    public function unreadCount($userId)
    {
        return $this->createQueryBuilder('m')
            ->select('_from.id as from_id', 'COUNT(_from.id) as count')
            ->innerJoin('m.from', '_from')
            ->where('m.to = :userid')->setParameter('userid', $userId)
            ->andWhere('m.readAt IS NULL')
            ->groupBy('_from.id')
            ->getQuery()
            ->getArrayResult();
    }

    public function readAllFrom($from, $to)
    {
        $date = new \DateTime();
        $this->createQueryBuilder('m')
            ->update('App:Messages', 'm')
            ->set('m.readAt', ':date')->setParameter('date', $date->format('Y-m-d H:i:s'))
            ->where('m.from = :from')->setParameter('from', $from)
            ->andWhere('m.to = :to')->setParameter('to', $to)
            ->getQuery()
            ->execute();
    }
}
