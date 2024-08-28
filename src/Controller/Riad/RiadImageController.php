<?php
namespace App\Controller\Riad;

use App\Entity\Riad;
use App\Entity\RiadImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RiadImageController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/riiad_images', name: 'upload_riad_image', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $imageFiles = $request->files->get('imageFiles'); // This will be an array of UploadedFile objects
        $roomId = $request->request->get('riiad'); // Note the correct key name for 'room'

        if (!$imageFiles || !is_array($imageFiles)) {
            return new Response('No files provided', Response::HTTP_BAD_REQUEST);
        }

        if (!$roomId) {
            return new Response('Riad ID is required', Response::HTTP_BAD_REQUEST);
        }

        $room = $this->em->getRepository(Riad::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Riad not found');
        }

        foreach ($imageFiles as $imageFile) {
            if ($imageFile instanceof UploadedFile) {
                $roomImage = new RiadImage();
                $roomImage->setRiad($room);
                $roomImage->setImageFile($imageFile);

                $this->em->persist($roomImage);
            } else {
                // Handle non-UploadedFile objects if needed
                return new Response('Invalid file type', Response::HTTP_BAD_REQUEST);
            }
        }

        $this->em->flush();

        return new Response('Images uploaded successfully', Response::HTTP_OK);
    }
}

