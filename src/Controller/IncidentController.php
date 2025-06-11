<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceAssignment;
use App\Entity\Incident;
use App\Entity\User;
use App\Repository\IncidentRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class IncidentController extends AbstractController
{
    #[Route('/incidents', name: 'app_incidents')]
    public function index(Request $request, IncidentRepository $incidentRepository): Response
    {
        $status = $request->query->get('status');

        $queryBuilder = $incidentRepository->createQueryBuilder('i')
            ->orderBy('i.created_at', 'DESC');

        if ($status) {
            $queryBuilder->andWhere('i.status = :status')
                ->setParameter('status', $status);
        }

        $incidents = $queryBuilder->getQuery()->getResult();

        return $this->render('incident/index.html.twig', [
            'incidents' => $incidents,
        ]);
    }

    #[Route('/incident/{id}', name: 'incident_show')]
    public function show(Incident $incident): Response
    {
        return $this->render('incident/show.html.twig', [
            'incident' => $incident,
        ]);
    }

    #[Route('/incident/{id}/update', name: 'incident_update', methods: ['POST'])]
    public function updateIncident(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // incident by id
        $incident = $entityManager->getRepository(Incident::class)->find($id);

        if (!$incident) {
            throw $this->createNotFoundException('Incident not found');
        }

        // if already closed
        if ($incident->getStatus() === 'closed') {
            $this->addFlash('error', 'Cet incident est fermé et ne peut plus être modifié.');
            return $this->redirectToRoute('incident_show', ['id' => $id]);
        }

        // Get form data
        $incidentStatus = $request->request->get('incident_status');
        $deviceStatus = $request->request->get('device_status');
        $resolution = $request->request->get('resolution');

        // Update incident status
        if ($incidentStatus && in_array($incidentStatus, ['in_progress', 'closed'])) {
            $incident->setStatus($incidentStatus);

            // If closing the incident, add resolution and set close date
            if ($incidentStatus === 'closed') {
                if (!empty($resolution)) {
                    $incident->setResolution($resolution);
                }
                //$incident->setClosedDate(new \DateTime());
            }
        }

        if ($deviceStatus && in_array($deviceStatus, ['active', 'inactive', 'repairing'])) {
            $device = $incident->getDevice();
            if ($device) {
                $device->setStatus($deviceStatus);
            }
        }

        try {
            $entityManager->flush();

            $this->addFlash('success', 'L\'incident a été mis à jour avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur s\'est produite lors de la mise à jour.');
        }

        // Redirect back to incident details
        return $this->redirectToRoute('incident_show', ['id' => $id]);
    }

    #[Route('/incident/{id}', name: 'incident_show', methods: ['GET'])]
    public function showIncident(int $id, EntityManagerInterface $entityManager): Response
    {
        $incident = $entityManager->getRepository(Incident::class)->find($id);

        if (!$incident) {
            throw $this->createNotFoundException('Incident not found');
        }

        return $this->render('incident/show.html.twig', [
            'incident' => $incident,
        ]);
    }

    #[Route('/incidents', name: 'incident_list', methods: ['GET'])]
    public function listIncidents(EntityManagerInterface $entityManager): Response
    {
        $incidents = $entityManager->getRepository(Incident::class)->findAll();

        return $this->render('incident/list.html.twig', [
            'incidents' => $incidents,
        ]);
    }

    #[Route('/signaler', name: 'report_incident', methods: ['GET'])]
    public function showReportForm(): Response
    {
        return $this->render('incident/report.html.twig');
    }

    #[Route('/api/user-devices', name: 'api_user_devices', methods: ['POST'])]
    public function getUserDevices(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? null;

            if (!$email) {
                return new JsonResponse(['error' => 'Email is required'], 400);
            }

            // Find user by email
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                return new JsonResponse(['error' => 'User not found for email: ' . $email], 404);
            }

            // Get device assignments for this user
            $deviceAssignments = $entityManager->getRepository(DeviceAssignment::class)
                ->findBy(['user' => $user]);

            $deviceData = [];
            foreach ($deviceAssignments as $assignment) {
                $device = $assignment->getDevice();
                try {
                    $deviceData[] = [
                        'id' => $device->getId(),
                        'type' => $device->getType(),
                        'serialNumber' => $device->getSerialNumber(),
                        'status' => $device->getStatus(),
                        'image' => $device->getDeviceImage() ?? 'default-device.png'
                    ];
                } catch (\Exception $e) {
                    error_log('Error processing device: ' . $e->getMessage());
                    continue;
                }
            }

            return new JsonResponse([
                'user' => [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail()
                ],
                'devices' => $deviceData
            ]);
        } catch (\Exception $e) {
            error_log('Error in getUserDevices: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/create-incident', name: 'api_create_incident', methods: ['POST'])]
    public function createIncident(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $userEmail = $data['userEmail'] ?? null;
            $deviceId = $data['deviceId'] ?? null;
            $description = $data['description'] ?? null;

            // Validation
            if (!$userEmail || !$deviceId || !$description) {
                return new JsonResponse(['error' => 'Missing required fields'], 400);
            }

            // Find user and device
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
            $device = $entityManager->getRepository(Device::class)->find($deviceId);

            if (!$user || !$device) {
                return new JsonResponse(['error' => 'User or device not found'], 404);
            }

            // Verify device is assigned to user
            $assignment = $entityManager->getRepository(DeviceAssignment::class)
                ->findOneBy([
                    'user' => $user,
                    'device' => $device
                ]);

            if (!$assignment) {
                return new JsonResponse(['error' => 'Device does not belong to user'], 403);
            }

            // Generate tracking number
            $trackingNumber = $this->generateTrackingNumber($entityManager);

            // Create incident
            $incident = new Incident();
            $incident->setReportedBy($user);
            $incident->setDevice($device);
            $incident->setDescription($description);
            $incident->setTrackingId($trackingNumber);
            $incident->setReportDate(new \DateTime());
            $incident->setStatus('in_progress');
            $incident->setCreatedAt(new \DateTime());
            $incident->setUpdatedAt(new \DateTime());

            $entityManager->persist($incident);
            $entityManager->flush();


            return new JsonResponse([
                'success' => true,
                'trackingNumber' => $trackingNumber,
                'incidentId' => $incident->getId(),
                'message' => 'Incident created successfully'
            ]);
        } catch (\Exception $e) {
            error_log('Error creating incident: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Failed to create incident: ' . $e->getMessage(),
                'details' => $e->getMessage(), // Show actual error in response
                'trace' => $e->getTraceAsString() // 🔍 full stack trace

            ], 500);
        }
    }

    private function generateTrackingNumber(EntityManagerInterface $entityManager): string
    {
        do {
            // Generate tracking number: TRK- + timestamp(5 digits) + random(3 digits)
            $timestamp = substr((string)time(), -5);
            $random = str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $trackingNumber = 'TRK-' . $timestamp . $random;

            // Check if tracking number already exists
            $existing = $entityManager->getRepository(Incident::class)
                ->findOneBy(['trackingId' => $trackingNumber]);
        } while ($existing !== null);

        return $trackingNumber;
    }

    #[Route('/track/{trackingId}', name: 'track_incident', methods: ['GET'])]
    public function trackIncident(string $trackingId, EntityManagerInterface $entityManager): Response
    {
        $incident = $entityManager->getRepository(Incident::class)
            ->findOneBy(['trackingId' => $trackingId]);

        if (!$incident) {
            throw $this->createNotFoundException('Incident not found with tracking ID: ' . $trackingId);
        }

        return $this->render('incident/track.html.twig', [
            'incident' => $incident,
        ]);
    }
}
