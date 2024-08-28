<?php
// src/Controller/User/ImageUploadController.php

namespace App\Controller\User;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use App\Entity\User;

class UserImageController extends AbstractController
{
    private Security $security;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    public function __construct(Security $security, EntityManagerInterface $em, UserRepository $userRepository)
    {
        $this->security = $security;
        $this->em = $em;
        $this->userRepository = $userRepository;
    }

    #[Route('/upload-image', name: 'upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('image');

        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/images/User';
        $fileName = md5(uniqid()) . '.' . $file->guessExtension();

        try {
            $file->move($uploadsDirectory, $fileName);
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Failed to upload image'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $fileUrl = '/images/User/' . $fileName;

        // Get the current user
        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        // Update the user's imageUrl property
        $user->setImageUrl($fileUrl);
        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse(['fileUrl' => $fileUrl], Response::HTTP_OK);
    }

}
