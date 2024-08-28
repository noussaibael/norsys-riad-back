<?php
namespace App\Controller\Room;

use App\Entity\Room;
use App\Entity\RoomImage;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoomImageController extends AbstractController
{
    private EntityManagerInterface $em;
    private RoomRepository  $roomRepository;


    public function __construct(EntityManagerInterface $em, RoomRepository $roomRepository)
    {
        $this->em = $em;
        $this->roomRepository = $roomRepository;
    }

    public function __invoke(Request $request): Response
    {
        $file = $request->files->get('imageFiles'); // Expect 'imageFiles' from form data
        $roomId = $request->request->get('id_riad'); // Retrieve the room ID

        if (!$file instanceof UploadedFile) {
            return new Response('No file uploaded', Response::HTTP_BAD_REQUEST);
        }

        if (!$roomId) {
            return new Response('Room ID is required', Response::HTTP_BAD_REQUEST);
        }

        $room = $this->roomRepository->find($roomId);

        if (!$room) {
            return new Response('Room not found', Response::HTTP_NOT_FOUND);
        }

        $image = new RoomImage();
        $image->setRoom($room);
        $image->setImageFile($file);

        try {
            $this->em->persist($image);
            $this->em->flush();
            return new Response(json_encode(['imageUrl' => $image->getImageUrl()]), Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
        } catch (FileException $e) {
            return new Response('File upload failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/rooom_images', name: 'upload_room_images', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $imageFiles = $request->files->get('imageFiles'); // This will be an array of UploadedFile objects
        $roomId = $request->request->get('rooom'); // Note the correct key name for 'room'

        if (!$imageFiles || !is_array($imageFiles)) {
            return new Response('No files provided', Response::HTTP_BAD_REQUEST);
        }

        if (!$roomId) {
            return new Response('Room ID is required', Response::HTTP_BAD_REQUEST);
        }

        $room = $this->em->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        foreach ($imageFiles as $imageFile) {
            if ($imageFile instanceof UploadedFile) {
                $roomImage = new RoomImage();
                $roomImage->setRoom($room);
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
