<?php

namespace App\Service;

use App\Entity\ActivityMedia;
use Doctrine\ORM\EntityManagerInterface;

class ActivityMediaService
{

    public function __construct(private EntityManagerInterface $entityManager, private string $directory)
    {
    }

    public function delete(ActivityMedia $activityMedia)
    {
        // TODO: non utilizzato. Potrebbe essere utilizzato da un job che periodicamente elimini tutte i media non utilizzati
        // da nessun Activity
        $filename = $this->buildActivityMediaPath($activityMedia->getFilename());
        unlink($filename);

        $this->entityManager->remove($activityMedia);
        $this->entityManager->flush();
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function buildActivityMediaPath(string $filename): string
    {
        return rtrim($this->directory, '/') . '/' . $filename;
    }
}
