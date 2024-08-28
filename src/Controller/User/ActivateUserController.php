<?php

// src/Controller/User/ArchiveUserController.php

namespace App\Controller\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ActivateUserController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(User $data): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data->setArchived(false);
        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'message' => 'User Activated successfully.'
        ]);
    }
}
