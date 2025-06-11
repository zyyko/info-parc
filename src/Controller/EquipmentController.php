<?php

namespace App\Controller;

use App\Entity\Device;
use App\Entity\DeviceAssignment;
use App\Entity\DeviceSoftware;
use App\Entity\Hardware;
use App\Entity\Software;
use App\Entity\User;
use App\Repository\DeviceRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EquipmentController extends AbstractController
{
    #[Route('/equipements/appareils', name: 'app_appareils')]
    public function index(DeviceRepository $deviceRepository): Response
    {
        $devices = $deviceRepository->findAll();

        return $this->render('equipment/devices.html.twig', [
            'devices' => $devices,
        ]);
    }

    #[Route('/equipements/appareil/{id}', name: 'profile_appareil')]
    public function profile(Device $device, UserRepository $userRepo, EntityManagerInterface $em): Response
    {

        $assignedUser = $device->getAssignment()?->getUser();
        $deviceSoftware = $em->getRepository(DeviceSoftware::class)->findBy(['device' => $device]);

        $allSoftware = $em->getRepository(Software::class)->findAll();

        // Get IDs of software already installed on this device
        $installedSoftwareIds = array_map(function ($deviceSoft) {
            return $deviceSoft->getSoftware()->getId();
        }, $deviceSoftware);

        // Filter out already installed software
        $availableSoftware = array_filter($allSoftware, function ($software) use ($installedSoftwareIds) {
            return !in_array($software->getId(), $installedSoftwareIds);
        });

        return $this->render('equipment/device.profile.html.twig', [
            'device' => $device,
            'users' => $userRepo->findAll(),
            'assignedUser' => $assignedUser,
            'deviceSoftware' => $deviceSoftware,
            'availableSoftware' => $availableSoftware,

        ]);
    }

    #[Route('/device/{id}/assign', name: 'device_assign', methods: ['POST'])]
    public function assignDevice(Device $device, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->request->get('userId');

        if (!$userId) {
            $this->addFlash('error', 'Aucun utilisateur sélectionné.');
            return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
        }

        $user = $em->getRepository(User::class)->find($userId);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
        }

        // Check if device is already assigned (no returned_date means still assigned)
        $existingAssignment = $device->getAssignment();
        if ($existingAssignment && !$existingAssignment->getReturnedDate()) {
            $this->addFlash('error', 'Cet appareil est déjà assigné à un utilisateur.');
            return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
        }

        // Create new assignment
        $assignment = new DeviceAssignment();
        $assignment->setDevice($device);
        $assignment->setUser($user);
        $assignment->setAssignedDate(new \DateTime());
        $assignment->setCreatedAt(new \DateTime());
        $assignment->setUpdatedAt(new \DateTime());

        $em->persist($assignment);
        $em->flush();

        $this->addFlash('success', 'Appareil assigné avec succès à ' . $user->getFirstName() . '.');

        return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
    }

    #[Route('/device/{id}/unassign', name: 'device_unassign', methods: ['POST'])]
    public function unassignDevice(Device $device, EntityManagerInterface $em): Response
    {
        $assignment = $device->getAssignment();

        if (!$assignment) {
            $this->addFlash('error', 'Cet appareil n\'est pas assigné.');
            return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
        }

        $userName = $assignment->getUser()->getFirstName();

        $em->remove($assignment);
        $em->flush();

        $this->addFlash('success', 'Appareil désassigné avec succès de ' . $userName . '.');

        return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
    }

    // install software route

    #[Route('/equipements/appareil/{id}/install-software', name: 'device_install_software', methods: ['POST'])]
    public function installSoftware(Device $device, Request $request, EntityManagerInterface $em): Response
    {
        // backend validation 
        if (strtolower($device->getStatus()) === 'repairing' || strtolower($device->getStatus()) === 'inactive') {
            $this->addFlash('error', 'Impossible d\'installer le logiciel. L\'appareil doit être actif.');
            return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
        }

        $softwareId = $request->request->get('softwareId');

        if (!$softwareId) {
            $this->addFlash('error', 'Veuillez sélectionner un logiciel à installer.');
            return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
        }

        $software = $em->getRepository(Software::class)->find($softwareId);

        if (!$software) {
            $this->addFlash('error', 'Logiciel introuvable.');
            return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
        }

        // Check if software is already installed on this device
        $existingInstallation = $em->getRepository(DeviceSoftware::class)->findOneBy([
            'device' => $device,
            'software' => $software
        ]);

        if ($existingInstallation) {
            $this->addFlash('warning', 'Ce logiciel est déjà installé sur cet appareil.');
            return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
        }

        // Create new DeviceSoftware installation
        $deviceSoftware = new DeviceSoftware();
        $deviceSoftware->setDevice($device);
        $deviceSoftware->setSoftware($software);
        $deviceSoftware->setInstallationDate(new \DateTime());
        $deviceSoftware->setCreatedAt(new \DateTime());
        $deviceSoftware->setUpdatedAt(new \DateTime());

        $em->persist($deviceSoftware);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Le logiciel "%s" a été installé avec succès sur %s.',
            $software->getName(),
            $device->getDeviceName()
        ));

        return $this->redirectToRoute('profile_appareil', ['id' => $device->getId()]);
    }

    #[Route('/equipements/appareil/{id}/download-report', name: 'device_download_report')]
    public function downloadReport(Device $device, EntityManagerInterface $em): Response
    {
        $assignedUser = $device->getAssignment()?->getUser();
        $deviceSoftware = $em->getRepository(DeviceSoftware::class)->findBy(['device' => $device]);

        // Calculate device health
        $now = new \DateTime();
        $fabrication = $device->getFabricationDate();
        $renewal = $device->getRenewalDate();

        $total = $renewal->getTimestamp() - $fabrication->getTimestamp();
        $elapsed = $now->getTimestamp() - $fabrication->getTimestamp();
        $rawPercent = (1 - ($elapsed / $total)) * 100;
        $healthPercent = max(floor($rawPercent), 0);

        // Generate PDF content
        $html = $this->renderView('equipment/device_report.html.twig', [
            'device' => $device,
            'assignedUser' => $assignedUser,
            'deviceSoftware' => $deviceSoftware,
            'healthPercent' => $healthPercent,
            'generatedAt' => $now
        ]);

        // Create PDF using mPDF (you'll need to install: composer require mpdf/mpdf)
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);

        $mpdf->WriteHTML($html);

        $filename = sprintf(
            'rapport_appareil_%s_%s.pdf',
            $device->getSerialNumber(),
            $now->format('Y-m-d')
        );

        // Return PDF as download
        $response = new Response($mpdf->Output('', 'S'));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }


    #[Route('/logiciels/{id}/download-report', name: 'software_download_report')]
    public function downloadSoftwareReport(Software $software, EntityManagerInterface $em): Response
    {
        // Get all devices using this software
        $deviceSoftware = $em->getRepository(DeviceSoftware::class)->findBy(['software' => $software]);

        // Calculate license status
        $now = new \DateTime();
        $licenseStatus = null;
        $progress = null;

        if ($software->getLicenseExpiry()) {
            $expiry = $software->getLicenseExpiry();
            $daysLeft = $now->diff($expiry)->days;
            $totalDays = 365; // Assume 1 year license period
            $progress = round(($daysLeft / $totalDays) * 100);
            $progress = min(max($progress, 0), 100);

            $licenseStatus = [
                'daysLeft' => $daysLeft,
                'isExpired' => $now > $expiry,
                'progress' => $progress
            ];
        }

        // Generate PDF content
        $html = $this->renderView('equipment/software_report.html.twig', [
            'software' => $software,
            'deviceSoftware' => $deviceSoftware,
            'deviceCount' => count($deviceSoftware),
            'licenseStatus' => $licenseStatus,
            'generatedAt' => $now
        ]);

        // Configure mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);

        // Add PDF metadata
        $mpdf->SetTitle('Rapport Logiciel - ' . $software->getName());
        $mpdf->SetAuthor('Système de Gestion');

        $mpdf->WriteHTML($html);

        $filename = sprintf(
            'rapport_logiciel_%s_%s.pdf',
            $software->getName(),
            $now->format('Y-m-d')
        );

        // Return PDF as download
        $response = new Response($mpdf->Output('', 'S'));
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }


    #[Route('/equipements/logiciels', name: 'software_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $softwares = $em->getRepository(Software::class)->findAll();

        $softwareData = [];
        foreach ($softwares as $software) {
            $deviceCount = $em->getRepository(DeviceSoftware::class)->count(['software' => $software]);

            $softwareData[] = [
                'software' => $software,
                'deviceCount' => $deviceCount
            ];
        }

        return $this->render('equipment/softwares.html.twig', [
            'softwareData' => $softwareData,
        ]);
    }

    #[Route('/equipements/materiels', name: 'equipments_hardware')]
    public function hardwareList(EntityManagerInterface $em): Response
    {
        $hardwares = $em->getRepository(Hardware::class)->findAll();

        return $this->render('equipment/hardwares.html.twig', [
            'hardwares' => $hardwares,
        ]);
    }

    #[Route('/equipements/ajouter', name: 'equipments_add')]

    public function addEquipment()
    {
        return $this->render("equipment/add.equipment.html.twig");
    }

    #[Route('/equipements/store', name: 'equipments_store', methods: ['POST'])]
    public function storeEquipment(Request $request, EntityManagerInterface $entityManager): Response
    {
        $equipmentType = $request->request->get('equipment_type');

        //dd($equipmentType);

        if (!$equipmentType) {
            $this->addFlash('error', 'Type d\'équipement requis');
            return $this->redirectToRoute('equipments_add');
        }

        try {
            switch ($equipmentType) {
                case 'device':
                    $this->storeDevice($request, $entityManager);
                    break;
                case 'software':
                    $this->storeSoftware($request, $entityManager);
                    break;
                case 'hardware':
                    $this->storeHardware($request, $entityManager);
                    break;
                default:
                    throw new \InvalidArgumentException('Type d\'équipement invalide');
            }

            $this->addFlash('success', 'Équipement ajouté avec succès');
            switch ($equipmentType) {
                case 'software':
                    return $this->redirectToRoute('software_list'); // /equipements/logiciels
                case 'device':
                    return $this->redirectToRoute('app_appareils'); // /equipements/appareils
                case 'hardware':
                    return $this->redirectToRoute('equipments_hardware');
                default:
                    return $this->redirectToRoute('equipments_add');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'ajout: ' . $e->getMessage());
            return $this->redirectToRoute('equipments_add');
        }
    }

    private function storeDevice(Request $request, EntityManagerInterface $entityManager): void
    {

        $deviceName = $request->request->get('device_name');
        $deviceType = $request->request->get('device_type');
        $serialNumber = $request->request->get('serial_number');

        if (!$deviceName || !$deviceType || !$serialNumber) {
            throw new \InvalidArgumentException('Tous les champs obligatoires doivent être remplis');
        }

        $device = new Device();
        $device->setDeviceName($deviceName);
        $device->setType($deviceType);
        $device->setSerialNumber($serialNumber);
        $device->setStatus($request->request->get('status', 'active'));

        // Handle dates
        if ($fabricationDate = $request->request->get('fabrication_date')) {
            $device->setFabricationDate(new \DateTime($fabricationDate));
        }

        if ($renewalDate = $request->request->get('renewal_date')) {
            $device->setRenewalDate(new \DateTime($renewalDate));
        }

        // Handle image upload
        $imageFile = $request->files->get('device_image');
        if ($imageFile) {
            $imagePath = $this->handleImageUpload($imageFile, 'devices_photos');
            $device->setDeviceImage($imagePath);
        }

        $entityManager->persist($device);
        $entityManager->flush();
    }

    private function storeSoftware(Request $request, EntityManagerInterface $entityManager): void
    {
        // Validate required fields
        $softwareName = $request->request->get('software_name');

        if (!$softwareName) {
            throw new \InvalidArgumentException('Le nom du logiciel est obligatoire');
        }

        $software = new Software();
        $software->setName($softwareName);
        $software->setVersion($request->request->get('version'));


        $now = new \DateTime();
        $software->setCreatedAt($now);
        $software->setUpdatedAt($now);

        // Handle license expiry date
        if ($licenseExpiry = $request->request->get('license_expiry')) {
            $software->setLicenseExpiry(new \DateTime($licenseExpiry));
        }

        // Handle image upload
        $imageFile = $request->files->get('software_image');
        if ($imageFile) {
            $imagePath = $this->handleImageUpload($imageFile, 'softwares_photos');
            $software->setImage($imagePath);
        }

        $entityManager->persist($software);
        $entityManager->flush();
    }

    private function storeHardware(Request $request, EntityManagerInterface $entityManager): void
    {
        // Validate required fields
        $hardwareTitle = $request->request->get('hardware_title');
        $hardwareType = $request->request->get('hardware_type');

        if (!$hardwareTitle || !$hardwareType) {
            throw new \InvalidArgumentException('Le titre et le type du matériel sont obligatoires');
        }

        $hardware = new Hardware();
        $hardware->setTitle($hardwareTitle);
        $hardware->setType($hardwareType);

        $imageFile = $request->files->get('hardware_image');
        if ($imageFile) {
            $imagePath = $this->handleImageUpload($imageFile, 'hardwares_photos');
            $hardware->setImage($imagePath);
        }

        $entityManager->persist($hardware);
        $entityManager->flush();
    }

    private function handleImageUpload(UploadedFile $file, string $directory): string
    {
        $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/assets/images/' . $directory;

        if (!is_dir($uploadsDirectory)) {
            mkdir($uploadsDirectory, 0777, true);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($uploadsDirectory, $fileName);
        } catch (FileException $e) {
            throw new \RuntimeException('Erreur lors du téléchargement de l\'image');
        }

        return $fileName;
    }

    #[Route('/csrf-token/{id}', name: 'generate_csrf_token')]
    public function generateCsrfToken(string $id): JsonResponse
    {
        $token = $this->container->get('security.csrf.token_manager')
            ->getToken('delete' . $id)
            ->getValue();

        return new JsonResponse(['token' => $token]);
    }

    #[Route('hardware/{id}/delete', name: 'hardware_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Hardware $hardware, EntityManagerInterface $entityManager): Response
    {

        //dd($request->request->get('_token'));

        // Verify CSRF token for security
        if ($this->isCsrfTokenValid('delete' . $hardware->getId(), $request->request->get('_token'))) {

            // If hardware has an associated image, you might want to delete it from filesystem
            if ($hardware->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/assets/images/hardwares_photos/' . $hardware->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Remove the hardware from database
            $entityManager->remove($hardware);
            $entityManager->flush();

            // Add success flash message
            $this->addFlash('success', 'Le matériel "' . $hardware->getTitle() . '" a été supprimé avec succès.');
        } else {
            // Add error flash message for invalid CSRF token
            $this->addFlash('error', 'Token de sécurité invalide. La suppression a échoué.');
        }

        return $this->redirectToRoute('equipments_hardware');
    }
}
