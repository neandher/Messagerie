<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UserRepository extends ServiceEntityRepository
{
    /**
     * @var MessagesRepository
     */
    private $messagesRepository;

    /**
     * UserRepository constructor.
     * @param RegistryInterface $registry
     * @param MessagesRepository $messagesRepository
     */
    public function __construct(RegistryInterface $registry, MessagesRepository $messagesRepository)
    {
        parent::__construct($registry, User::class);
        $this->messagesRepository = $messagesRepository;
    }

    public function findOthers($autUserId)
    {
        $qb =  $this->createQueryBuilder('u')
            ->where('u.id != :uid')->setParameter('uid', $autUserId)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();

        $unread = $this->messagesRepository->unreadCount($autUserId);
        /** @var User $user */
        foreach ($qb as $user){
            foreach ($unread as $uItem){
                if($uItem['from_id'] == $user->getId()){
                    $user->setUnread($uItem);
                }
            }
        }

        return $qb;
    }
}
