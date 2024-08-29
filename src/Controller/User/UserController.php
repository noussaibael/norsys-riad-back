<?php

// src/Controller/UserController.php

namespace App\Controller\User;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private JWTTokenManagerInterface $jwtTokenManager;
    private UserRepository $userRepository;

    public function __construct(JWTTokenManagerInterface $jwtTokenManager, UserRepository $userRepository)
    {
        $this->jwtTokenManager = $jwtTokenManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/user_info', name: 'user-info', methods: ['GET'])]
    public function getUserInfo(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization');
        $token = str_replace('Bearer ', '', $authHeader);

        if (!$token) {
            return new JsonResponse(['error' => 'Token is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Decode the JWT token
            $data = $this->jwtTokenManager->parse($token);

            // Retrieve username from the decoded token
            $username = $data['username'] ?? null;

            if (!$username) {
                return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
            }

            // Fetch user details from the database using UserRepository
            $user = $this->userRepository->findOneBy(['username' => $username]);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            // Return user details
            return new JsonResponse([
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getSecondname(),
                'Cin' => $user->getCin(),
                'adresse' => $user->getAddress(),
                'Telephone' => $user->getTele(),


            ]);

        } catch (JWTDecodeFailureException $e) {
            return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }
    }
}
