<?php

namespace App\Controller\User;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class DeleteUploadImageController extends AbstractController
{
    private UserRepository $userRepository;
    private EntityManagerInterface $em;

    public function __construct( EntityManagerInterface $em, UserRepository $userRepository)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
    }
    #[Route('/delete-image/{id}', name: 'delete_image', methods: ['DELETE'])]
    public function deleteImage(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Assuming you have a method to delete the image and update the user
        $user->setImageUrl(null); // Remove image URL
        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
