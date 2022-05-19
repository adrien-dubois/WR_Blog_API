<?php

namespace App\Security\Voter;

use App\Entity\Todoline;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class TodolineVoter extends Voter
{
    public const EDIT = 'edit';
    public const READ = 'read';
    public const DELETE = 'delete';
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        
        return in_array($attribute, [self::EDIT, self::READ, self::DELETE])
            && $subject instanceof \App\Entity\Todoline;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        if($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Todoline $todoline */
        $todoline = $subject;

        if(null === $todoline->getUser()) return false;

        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($todoline, $user);
                break;
            case self::READ:
                return $this->canRead($todoline, $user);
                break;
            case self::DELETE:
                return $this->canDelete($todoline, $user);
                break;
        }

        return false;
    }

    private function canEdit(Todoline $todoline, $user)
    {
        return $user === $todoline->getUser();
    }
    
    private function canDelete(Todoline $todoline, $user)
    {
        return $user === $todoline->getUser();
    }
    
    private function canRead(Todoline $todoline, $user)
    {
        return $user === $todoline->getUser();
    }
}
