<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class CustomerMessageDeliveryService
{
    public function send(string $phone, string $message): bool
    {
        Log::warning('Customer message delivery is unavailable because no messaging provider is configured.');

        return false;
    }
}
