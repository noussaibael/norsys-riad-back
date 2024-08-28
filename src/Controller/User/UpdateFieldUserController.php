<?php

namespace App\Controller\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UpdateFieldUserController extends AbstractController
{
    private UserService $userService;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(UserService $userService, UserRepository $userRepository,
                                EntityManagerInterface $entityManager,
                                SerializerInterface $serializer
    )
    {
        $this->userService = $userService;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    #[Route('/update-user/{id}', name: 'update_user_field', methods: ['PUT'])]
    public function updateUser(Request $request, $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the current user is authorized to update this user
        if ($user !== $this->getUser()) {
            throw new AccessDeniedException('You do not have permission to edit this user.');
        }

        $data = json_decode($request->getContent(), true);

        // Log incoming data
        error_log(print_r($data, true));

        // Validate and update fields
        foreach ($data as $field => $value) {
            if (property_exists($user, $field)) {
                $setter = 'set' . ucfirst($field);
                if (method_exists($user, $setter)) {
                    $user->$setter($value);
                }
            }
        }

        // Log the user object before flush
        error_log(print_r($user, true));

        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException | ORMException $e) {
            return new JsonResponse(['message' => 'Failed to update user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($this->serializer->normalize($user), Response::HTTP_OK);
    }

}
