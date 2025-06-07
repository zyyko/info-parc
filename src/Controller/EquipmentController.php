<?php

namespace App\Controller;

use App\Entity\Device;
use App\Repository\DeviceRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function profile(Device $device, UserRepository $userRepo): Response
    {
        return $this->render('equipment/device.profile.html.twig', [
            'device' => $device,
            'users' => $userRepo->findAll(),
        ]);
    }
}
