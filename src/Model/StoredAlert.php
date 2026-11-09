<?php

namespace App\Model;

final class StoredAlert
{
    public function __construct(
        public readonly int $id,
        public readonly string $subject,
        public readonly string $message,
        public readonly \DateTimeImmutable $receivedAt,
        public string $status,
    ) {
    }

    /** @param array{id:int,subject:string,message:string,received_at:string,status:string} $row */
    public static function fromArray(array $row): self
    {
        return new self(
            $row['id'],
            $row['subject'],
            $row['message'],
            new \DateTimeImmutable($row['received_at']),
            $row['status'],
        );
    }

    /** @return array{id:int,subject:string,message:string,received_at:string,status:string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'message' => $this->message,
            'received_at' => $this->receivedAt->format(\DateTimeInterface::ATOM),
            'status' => $this->status,
        ];
    }
}
