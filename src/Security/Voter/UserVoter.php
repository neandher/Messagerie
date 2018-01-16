<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    const TALK_TO = 'talk_to';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::TALK_TO]) && $subject instanceof User;
    }

    protected function voteOnAttribute($attribute, $to, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        switch ($attribute) {
            case self::TALK_TO:
                return $user->getId() != $to->getId();
                break;
        }

        return false;
    }
}
