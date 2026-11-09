<?php

namespace App\Service;

use App\Model\StoredAlert;

/**
 * PHP-FPM не держит список между запросами, в отличие от процесса Axum.
 * Храним алерты в JSON-файле под flock.
 */
final class AlertRepository
{
    public function __construct(
        private readonly string $storagePath,
    ) {
    }

    public function append(string $subject, string $message, string $status): StoredAlert
    {
        return $this->mutate(function (array $alerts) use ($subject, $message, $status): array {
            $id = $alerts === [] ? 1 : (max(array_map(static fn (StoredAlert $a) => $a->id, $alerts)) + 1);
            $alerts[] = new StoredAlert(
                $id,
                $subject,
                $message,
                new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
                $status,
            );

            return $alerts;
        });
    }

    /** @return list<StoredAlert> newest first */
    public function newestFirst(): array
    {
        $alerts = $this->readUnlocked();
        usort($alerts, static fn (StoredAlert $a, StoredAlert $b) => $b->receivedAt <=> $a->receivedAt);

        return $alerts;
    }

    /**
     * @param callable(list<StoredAlert>): list<StoredAlert> $fn
     */
    private function mutate(callable $fn): StoredAlert
    {
        $this->ensureFile();
        $fp = fopen($this->storagePath, 'c+');
        if ($fp === false) {
            throw new \RuntimeException('Cannot open alert storage');
        }

        try {
            if (!flock($fp, LOCK_EX)) {
                throw new \RuntimeException('Cannot lock alert storage');
            }

            $raw = stream_get_contents($fp);
            $alerts = $this->decode($raw === false ? '' : $raw);
            $before = count($alerts);
            $alerts = $fn($alerts);
            if (count($alerts) !== $before + 1) {
                throw new \RuntimeException('Alert was not appended');
            }

            $payload = json_encode(
                array_map(static fn (StoredAlert $a) => $a->toArray(), $alerts),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
            );
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $payload);
            fflush($fp);
            flock($fp, LOCK_UN);

            return $alerts[array_key_last($alerts)];
        } finally {
            fclose($fp);
        }
    }

    /** @return list<StoredAlert> */
    private function readUnlocked(): array
    {
        if (!is_file($this->storagePath)) {
            return [];
        }
        $raw = file_get_contents($this->storagePath);

        return $this->decode($raw === false ? '' : $raw);
    }

    /** @return list<StoredAlert> */
    private function decode(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        /** @var list<array{id:int,subject:string,message:string,received_at:string,status:string}> $rows */
        $rows = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return array_map(StoredAlert::fromArray(...), $rows);
    }

    private function ensureFile(): void
    {
        $dir = dirname($this->storagePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create '.$dir);
        }
    }
}
