<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\ParameterNotFoundException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AuthenticationController extends AbstractController
{
    private function getRequiredParameter(
        string $parameterName,
        array $requestBody,
        string $errorMessage
    ) {
        if (!isset($requestBody[$parameterName])) {
            throw new ParameterNotFoundException($errorMessage);
        }
        return $requestBody[$parameterName];
    }


    /**
     * @Route("/authentication", name="app_authentication")
     */
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AuthenticationController.php',
        ]);
    }

    /**
     * @Route("/register", name="register")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $passwordHasher
     * @return JsonResponse
     * @throws ParameterNotFoundException
     */
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $requestBody = $request->request->all();

        $firstName = $this->getRequiredParameter('firstName', $requestBody, 'First name is required');
        $lastName = $this->getRequiredParameter('lastName', $requestBody, 'Last name is required');
        $emailAddress = $this->getRequiredParameter('emailAddress', $requestBody, 'Email address is required');
        $password = $this->getRequiredParameter('password', $requestBody, 'Password is required');

        $user = new User($firstName, $lastName, $emailAddress);

        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->json(
            ['message' => 'Account created successfully',],
            Response::HTTP_CREATED
        );
    }

    /**
     * @Route("/login", name="login")
     * @param Request $request
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $em
     * @param UserPasswordHasherInterface $passwordHasher
     * @return JsonResponse
     * @throws \Exception
     */
    public function login(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $requestBody = $request->request->all();

        $emailAddress = $requestBody['emailAddress'];
        $password = $requestBody['password'];

        $user = $userRepository->findOneBy(['email' => $emailAddress]);

        if (is_null($user)) {
            return $this->json(
                [
                    'error' => 'Invalid login credentials provided'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(
                [
                    'error' => 'Invalid login credentials provided'
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }
        $apiToken = bin2hex(random_bytes(32));
        $user->setApiToken($apiToken);

        $em->persist($user);
        $em->flush();

        return $this->json(
            [
                'token' => $apiToken
            ]
        );
    }

}
