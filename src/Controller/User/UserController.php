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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;


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

        // Get the token from the Authorization header
        $authorizationHeader = $request->headers->get('Authorization');
        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return new JsonResponse(['error' => 'Authorization header is required'], Response::HTTP_BAD_REQUEST);
        }

        // Extract the token from the header
        $token = str_replace('Bearer ', '', $authorizationHeader);

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
                'id' => $user->getId(), // Include the user ID in the response
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getSecondname(),
                'cin' => $user->getCin(),
                'adresse' => $user->getAddress(),
                'Telephone' => $user->getTele(),
                'image_url' => $user->getImageUrl(),
                'password'=>$user->getPassword()// Include the image URL in the response
            ]);

        } catch (JWTDecodeFailureException $e) {
            return new JsonResponse(['error' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }
    }
    #[Route('/user-id', name: 'get_user_id', methods: ['GET'])]
    public function getUserId(TokenStorageInterface $tokenStorage): JsonResponse
    {
        $token = $tokenStorage->getToken();

        if (!$token || !$token->getUser() instanceof UserInterface) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $token->getUser();
        return new JsonResponse(data: ['user_id' => $user->getId()]);
    }

}
