<?php

namespace App\Dto;

final class ZabbixAlert
{
    public function __construct(
        public readonly string $subject,
        public readonly string $message,
    ) {
    }
}
