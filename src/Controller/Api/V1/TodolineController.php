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
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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
     * Method to take just one todo by it ID
     * 
     * @Route("/{id}", name="findid", methods={"GET"})
     *
     * @param integer $id
     * @param TodolineRepository $repository
     * @return JsonResponse
     */
    public function findId(int $id, TodolineRepository $repository): JsonResponse
    {
        $todoline = $repository->find($id);

        if(!$todoline){
            return $this->json([
                'error' => "Cette tâche n'existe pas."
            ], 404
        );
        }
        
        $this->denyAccessUnlessGranted('read', $todoline, "Seul le créateur de cette todolist peut y accéder");


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

    /**
     * Method tu update a task
     * 
     * @Route("/{id}", name="update", methods={"PUT", "PATCH"})
     *
     * @param Request $request
     * @param Todoline $todoline
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function update(Request $request, Todoline $todoline, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $todoline, "Seul le créteur de cette todolist peut y accéder.");

        $jsonData = $request->getContent();

        if(!$todoline) {
            return $this->json([
                'errors' => ['message' => 'Seul le créateur de cette todolist peut y accéder']
            ], 404);
        }

        $serializer->deserialize($jsonData, Todoline::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $todoline]);

        $em->flush();

        return $this->json(["message" => "La tâche a bien été modifiée"], 200, [], [
            'groups' => 'todoline'
        ]);
    }

    /**
     * Method to delete one task
     * 
     * @Route("/{id}", name="delete", methods={"DELETE"})
     *
     * @param Todoline $todoline
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function delete(Todoline $todoline, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('delete', $todoline, "Seul l'auteur de cette tâche peut la supprimer.");

        if (!$todoline) {
            return $this->json([
                'error' => "Cette tâche n'existe pas."
            ], 404);
        }


        $em->remove($todoline);
        $em->flush();

        return $this->json([
        'message' => 'La tâche a bien été supprimée'
    ], 200);

    }
}
