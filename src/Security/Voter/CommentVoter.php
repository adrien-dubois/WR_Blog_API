<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class CommentVoter extends Voter
{
    private $security;

    // Construct the Security to get the current user
    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    
    // Declare the constant of the methods we want vote to
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE])
            && $subject instanceof \App\Entity\Comment;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // If the user is the Admin, always allow access
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var Comment $comment */
        $comment = $subject;

        // check if the comment has a creator
        if(null === $comment->getUser()) return false;

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($comment, $user);
                break;
            case self::DELETE:
                return $this->canDelete($comment, $user);
                break;
        }

        return false;
    }

    // Check if we are the owner of the comment
    // If true, we can edit or delete it

    private function canEdit(Comment $comment, $user)
    {
        return $user === $comment->getUser();
    }
    private function canDelete(Comment $comment, $user)
    {
        return $user === $comment->getUser();
    }
}
