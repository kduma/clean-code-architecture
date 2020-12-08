<?php
declare(strict_types=1);

namespace App\Controller;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class Controller extends AbstractController
{
    private EntityManagerInterface $entityManager;

    /**
     * Controller constructor.
     *
     * @param $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    function index(): JsonResponse
    {
        return new JsonResponse('ReallyDirty API v1.0');
    }

    function getDoctor(Request $request): JsonResponse
    {
        $id = $request->get('id');
        $doctor = $this->getDoctorById((int) $id);

        if (!$doctor) {
            return new JsonResponse([], 404);
        }
        
        return new JsonResponse([
            'id' => $doctor->getId(),
            'firstName' => $doctor->getFirstName(),
            'lastName' => $doctor->getLastName(),
            'specialization' => $doctor->getSpecialization(),
        ]);
    }

    function postDoctor(Request $request): JsonResponse
    {
        $doctor = $this->createDoctorFromRequest($request);
        $this->saveDoctor($doctor);

        return new JsonResponse(['id' => $doctor->getId()]);
    }

    function getSlots(int $doctorId): JsonResponse
    {
        $doctor = $this->getDoctorById((int) $doctorId);

        if (!$doctor) {
            return new JsonResponse([], 404);
        }
        
        $response = $this->getSlotsForDoctor($doctor);

        return new JsonResponse($response);
    }

    function postSlots(int $doctorId, Request $request): JsonResponse
    {
        $doctor = $this->getDoctorById((int) $doctorId);

        if (!$doctor) {
            return new JsonResponse([], 404);
        }
        
        $slot = $this->createSlotFromRequest($request);
        
        $slot->setDoctor($doctor);
        $this->saveSlot($slot);

        return new JsonResponse(['id' => $slot->getId()]);
    }

    /**
     * @param $id
     *
     * @return DoctorEntity|null
     * @throws NonUniqueResultException
     */
    private function getDoctorById($id): ?DoctorEntity
    {
        return $this->entityManager->createQueryBuilder()
            ->select('doctor')
            ->from(DoctorEntity::class, 'doctor')
            ->where('doctor.id=:id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param DoctorEntity $doctor
     *
     * @return array
     */
    private function getSlotsForDoctor(DoctorEntity $doctor): array
    {
        $slots = $doctor->slots();

        $response = [];
        
        foreach ($slots as $slot) {
            $response[] = [
                'id'        => $slot->getId(),
                'day'       => $slot->getDay()->format('Y-m-d'),
                'from_hour' => $slot->getFromHour(),
                'duration'  => $slot->getDuration()
            ];
        }
        
        return $response;
    }

    /**
     * @param Request $request
     *
     * @return DoctorEntity
     */
    private function createDoctorFromRequest(Request $request): DoctorEntity
    {
        $doctor = new DoctorEntity();
        
        $doctor->setFirstName($request->get('firstName'));
        $doctor->setLastName($request->get('lastName'));
        $doctor->setSpecialization($request->get('specialization'));
        
        return $doctor;
    }

    /**
     * @param Request $request
     *
     * @return SlotEntity
     * @throws Exception
     */
    private function createSlotFromRequest(Request $request): SlotEntity
    {
        $slot = new SlotEntity();
        
        $slot->setDay(new DateTime($request->get('day')));
        $slot->setDuration((int)$request->get('duration'));
        $slot->setFromHour($request->get('from_hour'));
        
        return $slot;
    }

    /**
     * @param SlotEntity $slot
     *
     * @return SlotEntity
     */
    private function saveSlot(SlotEntity $slot): SlotEntity
    {
        $this->entityManager->persist($slot);
        $this->entityManager->flush();
        
        return $slot;
    }

    /**
     * @param DoctorEntity $doctor
     *
     * @return DoctorEntity
     */
    private function saveDoctor(DoctorEntity $doctor): DoctorEntity
    {
        $this->entityManager->persist($doctor);
        $this->entityManager->flush();
        
        return $doctor;
    }

}
