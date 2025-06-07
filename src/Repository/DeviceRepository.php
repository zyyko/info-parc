<?php

namespace App\Repository;

use App\Entity\Device;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Device>
 */
class DeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Device::class);
    }

    //    /**
    //     * @return Device[] Returns an array of Device objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Device
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    // src/Repository/DeviceRepository.php

    public function countDevicesBetween(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.created_at BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDevicesToReplaceSoon(): int
    {
        $fourYearsAgo = (new \DateTime())->modify('-4 years');

        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.fabrication_date <= :fourYearsAgo')
            ->setParameter('fourYearsAgo', $fourYearsAgo)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnassignedDevices(): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->leftJoin('App\Entity\DeviceAssignment', 'da', 'WITH', 'da.device = d')
            ->where('da.id IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTypePercentages(): array
    {
        $total = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.status != :inactive')
            ->setParameter('inactive', 'inactive')
            ->getQuery()
            ->getSingleScalarResult();

        if ($total == 0) {
            return [];
        }

        // Get count per type
        $counts = $this->createQueryBuilder('d')
            ->select('d.type, COUNT(d.id) AS count')
            ->where('d.status != :inactive')
            ->setParameter('inactive', 'inactive')
            ->groupBy('d.type')
            ->getQuery()
            ->getResult();

        $percentages = [];
        foreach ($counts as $row) {
            $percentages[$row['type']] = round(($row['count'] / $total) * 100, 2);
        }

        return $percentages;
    }
}
