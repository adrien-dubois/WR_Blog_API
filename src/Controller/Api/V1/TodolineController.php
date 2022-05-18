<?php

namespace App\Controller\Api\V1;

use App\Entity\Todoline;
use App\Repository\TodolineRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1/todoline", name="app_api_v1_todoline_", requirements={"id"="\d+"})
 */
class TodolineController extends AbstractController
{
    private $security;

    public function __construct(Security $security){
        $this->security = $security;
    }

    /**
     *
     *
     * @Route("/", name="findall", methods={"GET"})
     * @param TodolineRepository $repository
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    public function findAll(TodolineRepository $repository, userRepository $userRepository): JsonResponse
    {
        // first get current user
        $currentUser = $this->security->getUser()->getUserIdentifier();
        $user = $userRepository->findOneBy(array('email' => $currentUser));

        $todoline = $repository->findBy(['user'=>$user]);

        if(!$todoline){
            return $this->json(['errors' => ['message' => 'Vous n\'avez pas encore de todos']], 400);
        }

        return $this->json($todoline, 200, [], [
            'groups' => 'todoline'
        ]);
    }

    /**
     *
     * @Route("/", name="add", methods={"POST"})
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function create(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em): JsonResponse
    {
        $jsonData = $request->getContent();

        $todoline = $serializer->deserialize($jsonData, Todoline::class, 'json');

        $errors = $validator->validate($todoline);

        if(count($errors) > 0) {
            return $this->json($errors, 400);
        }

        $user = $this->security->getUser();
        $todoline->setUser($user);

        $em->persist($todoline);
        $em->flush();

        return $this->json($todoline, 201, [], [
            'groups' => 'todoline'
        ]);
    }
}
