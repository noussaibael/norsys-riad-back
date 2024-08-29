<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class ReservationController extends AbstractController
{
    private $reservationRepository;
    private $roomRepository;
    private $entityManager;

    public function __construct(
        ReservationRepository $reservationRepository,
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->roomRepository = $roomRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/reservations", name="create_reservation", methods={"POST"})
     */
    public function createReservation(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $roomId = $data['room_id'] ?? null;
        $startDate = isset($data['start_date']) ? new \DateTime($data['start_date']) : null;
        $endDate = isset($data['end_date']) ? new \DateTime($data['end_date']) : null;

        if (!$roomId || !$startDate || !$endDate) {
            return new JsonResponse(['error' => 'Invalid input'], Response::HTTP_BAD_REQUEST);
        }

        // Fetch the room entity
        $room = $this->roomRepository->find($roomId);

        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        // Check room availability
        $isAvailable = $this->reservationRepository->isRoomAvailableForDates($roomId, $startDate, $endDate);

        if (!$isAvailable) {
            return new JsonResponse(['error' => 'Room is not available for the selected dates'], Response::HTTP_CONFLICT);
        }

        // If available, create the reservation
        $reservation = new Reservation();
        $reservation->setFirstname($data['firstname']);
        $reservation->setLastname($data['lastname']);
        $reservation->setEmail($data['email']);
        $reservation->setTel($data['tel']);
        $reservation->setStartDate($startDate);
        $reservation->setEndDate($endDate);
        $reservation->setTotalPrice($data['total_price']);
        $reservation->setDiscount($data['discount']);
        $reservation->setRoom($room);
        $reservation->setUser($this->getUser());

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Reservation created successfully'], Response::HTTP_CREATED);
    }
}
