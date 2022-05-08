<?php

namespace App\Controller\Api\V1;

use App\Entity\Post;
use App\Form\ImageType;
use App\Repository\PostRepository;
use App\Service\UploadedBase64File;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1/post", name="app_api_v1_post_", requirements={"id"="\d+"})
 */
class PostController extends AbstractController
{

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Method to get all the posts
     * 
     * @Route("/", name="read_all", methods={"GET"})
     *
     * @param PostRepository $repository
     * @return JsonResponse
     */
    public function readAll(PostRepository $repository): JsonResponse
    {
        // We get all the posts in DB and return it in Json
        $posts = $repository->findAll();

        // This entry to the serializer will transform objects into Json, by searching only properties tagged by the "groups" name
        return $this->json($posts, 200, [], [
            'groups' => 'post'
        ]);
    }

    /**
     * Method to get one single post by its Id
     *
     * @Route("/{id}", name="read_one", methods={"GET"})
     * 
     * @param integer $id
     * @param PostRepository $repository
     * @return JsonResponse
     */
    public function readSingle(int $id, PostRepository $repository): JsonResponse
    {
        // We get an post by its ID
        $post = $repository->find($id);


        // If the post does not exists, we display 404
        if(!$post){
            return $this->json([
                'error' => 'L\'article numéro ' . $id . ' n\'existe pas'
            ], 404
            );
        }

        // We return the result with Json format
        return $this->json($post, 200, [], [
            'groups' => 'post'
        ]);
    }


    /**
     * Endpoint for creating a new post
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

        // We transform the json in object
        // First argument : datas to deserialize
        // Second : The type of object we want
        // Last : Start type

        /** @var Post $post */
        $post = $serializer->deserialize($jsonData, Post::class, 'json');

        // We validate the datas stucked in $announce on criterias of annotations' Entity @assert
        $errors = $validator->validate($post);

        // If the errors array is not empty, we return an error code 400 that is a Bad Request
        if (count($errors) > 0) {
            return $this->json($errors, 400);
        }
        // get the user teacher which is posting an announce for the voter, to check if we are the creator of it
        $user = $this->security->getUser();
        $post->setUser($user);

        // Decode the json request to get the image part into an array
        $data = json_decode($request->getContent(), true);
        if(isset($data['pictures'])) {
            // Send it to the Uploader service to cut the code, get a uniq name 
            $imageFile = new UploadedBase64File($data['pictures']['value'], $data['pictures']['name']);
            // create a form dedicated to pictures
            $form =$this->createForm(ImageType::class, $post,['csrf_protection' => false]);
            //Submit form and set pic
            $form->submit(['imageFile' => $imageFile]);
            $post->setPicture($imageFile);
        }

        $em->persist($post);
        $em->flush();

        return $this->json($post, 201, [], [
            'groups' => 'post'
        ]);
    }

    /**
     * Update an article by its ID only if it's the creator
     * 
     * @Route("/{id}", name="update", methods={"PUT","PATCH"})
     *
     * @param Post $post
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function update(Post $post, Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        // This method will allows to access to the update method, with the voter logic
        $this->denyAccessUnlessGranted('edit', $post, "Seul l'auteur de cet article peut le modifier.");

        $jsonData = $request->getContent();

        if(!$post){
            return $this->json([
                'errors' => ['message'=>'Cet article n\'existe pas']
            ], 404
            );
        }

        $serializer->deserialize($jsonData, Post::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE=>$post]);

        // Decode the json request to get the image part into an array
        $data = json_decode($request->getContent(), true);
        if(isset($data['pictures'])) {
            // Send it to the Uploader service to cut the code, get a uniq name 
            $imageFile = new UploadedBase64File($data['pictures']['value'], $data['pictures']['name']);
            // create a form dedicated to pictures
            $form =$this->createForm(ImageType::class, $post,['csrf_protection' => false]);
            //Submit form and set pic
            $form->submit(['imageFile' => $imageFile]);
            $post->setPicture($imageFile);
        }

        $em->flush();

        return $this->json(["message" => "L'article a bien été modifié"], 200, [], [
            'groups' => 'post'
        ]);
    }
}