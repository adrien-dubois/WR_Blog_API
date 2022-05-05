<?php

namespace App\Controller\Api\V1;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/v1/register", name="register_")
 */
class RegisterController extends AbstractController
{

    /**
     * Method to register a new user
     * 
     * @Route("/", name="user", methods={"POST"})
     *
     * @param Request $request
     * @param UserPasswordHasherInterface $hasher
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param MailerInterface $mailer
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function register(Request $request, UserPasswordHasherInterface $hasher, SerializerInterface $serializer, ValidatorInterface $validator, MailerInterface $mailer, EntityManagerInterface $em): Response
    {
        // Get the datas first
        $jsonData = $request->getContent();

        $user = $serializer->deserialize($jsonData, User::class, 'json');

        $errors = $validator->validate($user);

        $password = $user->getPassword();

        $user->setPassword(
            $hasher->hashPassword(
                $user,
                $password
            )
        );

        $otp = rand(100000, 999999);

        $user->setActivationToken(md5(uniqid()));
        $user->setOtp($otp);

        if(count($errors) > 0) {
            return $this->json($errors, 400);
        }

        $em->persist($user);
        $em->flush();

        $email = (new TemplatedEmail())
                ->from('admin@white-umbrella.fr')
                ->to($user->getEmail())
                ->subject("Validation de votre inscription sur White Rabbit's Blog")
                ->htmlTemplate('emails/confirmation.html.twig')
                ->context(compact('user'));
        
                $mailer->send($email);

        return $this->json($user, 201);
    }

    /**
     * Undocumented function
     * 
     * @Route("/activation", name="activation", methods={"POST"})
     *
     * @return void
     */
    public function activation(){

    }
}
