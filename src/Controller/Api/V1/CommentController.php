<?php

namespace App\Controller\Api\V1;

use App\Entity\Comment;
use App\Repository\CommentRepository;
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
 * @Route("/api/v1/comment", name="app_api_v1_comment_", requirements={"id"="\d+"})
 */
class CommentController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Endpoint to get all comments
     * 
     * @Route("/", name="read_all", methods={"GET"})
     *
     * @param CommentRepository $repository
     * @return JsonResponse
     */
    public function readAll(CommentRepository $repository): JsonResponse
    {
        // We get all the comments in DB and return it in Json
        $comments = $repository->findAll();

        // This entry to the serializer will transform objects into Json, by searching only properties tagged by the "groups" name
        return $this->json($comments, 200, [], [
            'groups' => 'comment'
        ]);
    }

    /**
     * Endpoint for having a single comment
     * 
     * @Route("/{id}", name="read_one", methods={"GET"})
     *
     * @param integer $id
     * @param CommentRepository $repository
     * @return JsonResponse
     */
    public function readSingle(int $id, CommentRepository $repository): JsonResponse
    {
        // We get an comment by its ID
        $comment = $repository->find($id);


        // If the comment does not exists, we display 404
        if(!$comment){
            return $this->json([
                'error' => 'Le comentaire demandé n\'existe pas'
            ], 404
            );
        }

        // We return the result with Json format
        return $this->json($comment, 200, [], [
            'groups' => 'comment'
        ]);
    }

    /**
     * Endpoint to create a new comment
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
        // we take back the JSON
        $jsonData = $request->getContent();

        /** @var Comment $comment */
        $comment = $serializer->deserialize($jsonData, Comment::class, 'json');

        // We validate the datas stucked in $announce on criterias of annotations' Entity @assert
        $errors = $validator->validate($comment);

        // If the errors array is not empty, we return an error code 400 that is a Bad Request
        if (count($errors) > 0) {
            return $this->json($errors, 400);
        }
        // get the user which is posting a comment for the voter, to check if we are the creator of it
        $user = $this->security->getUser();
        $comment->setUser($user);

        $em->persist($comment);
        $em->flush();

        return $this->json($comment, 201, [], [
            'groups' => 'comment'
        ]);
    }


    /**
     * Update a comment only if we are the author
     * 
     * @Route("/{id}", name="update", methods={"PUT","PATCH"})
     *
     * @param Comment $comment
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function update(Comment $comment, Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        // This method will allows to access to the update method, with the voter logic
        $this->denyAccessUnlessGranted('edit', $comment, "Seul l'auteur de ce commentaire peut le modifier.");

        $jsonData = $request->getContent();

        if(!$comment){
            return $this->json([
                'errors' => ['message'=>'Ce commentaire n\'existe pas']
            ], 404
            );
        }

        $serializer->deserialize($jsonData, Comment::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE=>$comment]);

        $em->flush();

        return $this->json(["message" => "Le commentaire a bien été modifié"], 200, [], [
            'groups' => 'comment'
        ]);
    }

    /**
     * Delete a comment only if we made it
     * 
     * @Route("/{id}", name="delete", methods={"DELETE"})
     *
     * @param Comment $comment
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function delete(Comment $comment, EntityManagerInterface $em): JsonResponse
    {
        // This protection will check by the voter if we are allowed to delete this comment
        $this->denyAccessUnlessGranted('delete', $comment, "Seul l'auteur de ce commentaire peut le supprimer.");

        if(!$comment){
            return $this->json([
                'error' => 'Ce commentaire n\'existe pas.'
            ], 404);
        }

        $em->remove($comment);
        $em->flush();

        return $this->json([
            'message' => 'Le commentaire a bien été supprimé'
        ], 200);
    }

}
