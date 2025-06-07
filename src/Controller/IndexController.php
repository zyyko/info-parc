<?php

namespace App\Controller;

use App\Entity\DeviceAssignment;
use App\Repository\DeviceAssignmentRepository;
use App\Repository\DeviceRepository;
use App\Repository\IncidentRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    private DeviceRepository $deviceRepository;
    private IncidentRepository $incidentRepository;
    private DeviceAssignmentRepository $deviceAssignmentRepository;

    public function __construct(DeviceRepository $deviceRepository, IncidentRepository $incidentRepository, DeviceAssignmentRepository $deviceAssignmentRepository)
    {
        $this->deviceRepository = $deviceRepository;
        $this->incidentRepository = $incidentRepository;
        $this->deviceAssignmentRepository = $deviceAssignmentRepository;
    }

    #[Route('/', name: 'main_page')]
    public function index(): Response
    {
        $now = new \DateTime();
        $oneWeekAgo = (clone $now)->modify('-7 days');

        $totalDevices = $this->deviceRepository->count([]);
        $devicesLastWeek = $this->deviceRepository->countDevicesBetween($oneWeekAgo, $now);

        $totalIncidents = $this->incidentRepository->count([]);
        $incidentsLastWeek = $this->incidentRepository->countIncidentsLastWeek($oneWeekAgo, $now);

        $devicesToReplaceCount = $this->deviceRepository->countDevicesToReplaceSoon();


        $workingCount   = $this->deviceRepository->countByStatus('active');
        $inactiveCount    = $this->deviceRepository->countByStatus('inactive');
        $repairingCount = $this->deviceRepository->countByStatus('repairing');
        $assignedCount   = $this->deviceAssignmentRepository->countAssignedDevices();
        $unassignedCount = $this->deviceRepository->countUnassignedDevices();


        // chart pourcentage !
        $percentagesDevices = $this->deviceRepository->getTypePercentages();


        //incidents 
        $lastTenIncidents = $this->incidentRepository->findLastTenIncidents();
        return $this->render('index.html.twig', [
            'totalDevices' => $totalDevices,
            'devicesLastWeek' => $devicesLastWeek,
            'totalIncidents' => $totalIncidents,
            'incidentsLastWeek' => $incidentsLastWeek,
            'devicesToReplaceCount' => $devicesToReplaceCount,
            'workingCount'   => $workingCount,
            'inactiveCount'    => $inactiveCount,
            'repairingCount' => $repairingCount,
            'assignedCount'  => $assignedCount,
            'unassignedCount' => $unassignedCount,
            'percentagesDevices' => $percentagesDevices,
            'lastTenIncidents' => $lastTenIncidents
        ]);
    }
}
