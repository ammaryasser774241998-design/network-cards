<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class PaymentMessageParser
{
    public function matchesConfiguredSender(string $sender): bool
    {
        foreach (SystemSetting::read('wallet_rules', []) as $rule) {
            if (empty($rule['sender_pattern'])) {
                continue;
            }

            $matches = preg_match($rule['sender_pattern'], $sender);
            if ($matches === false) {
                Log::error('Invalid wallet sender pattern in system settings.');

                continue;
            }
            if ($matches === 1) {
                return true;
            }
        }

        return false;
    }

    public function parse(string $sender, string $message): ?array
    {
        $rules = SystemSetting::read('wallet_rules', []);

        foreach ($rules as $rule) {
            if (empty($rule['sender_pattern']) || empty($rule['amount_pattern'])) {
                continue;
            }

            $senderMatches = preg_match($rule['sender_pattern'], $sender);
            if ($senderMatches === false) {
                Log::error('Invalid wallet sender pattern in system settings.');

                continue;
            }
            if ($senderMatches !== 1) {
                continue;
            }

            $amountMatches = preg_match($rule['amount_pattern'], $message, $matches);
            if ($amountMatches === false) {
                Log::error('Invalid wallet amount pattern in system settings.');

                continue;
            }
            if ($amountMatches !== 1 || empty($matches[1])) {
                return null;
            }

            $amount = preg_replace('/[^\d.]/', '', str_replace(',', '', $matches[1]));
            if (! is_numeric($amount) || (float) $amount <= 0) {
                return null;
            }

            $reference = null;
            if (! empty($rule['reference_pattern'])) {
                $referenceMatches = preg_match($rule['reference_pattern'], $message, $referenceParts);
                if ($referenceMatches === false) {
                    Log::error('Invalid wallet reference pattern in system settings.');

                    continue;
                }
                $reference = $referenceMatches === 1 ? ($referenceParts[1] ?? null) : null;
            }

            return [
                'wallet_name' => $rule['name'] ?? $sender,
                'amount' => (float) $amount,
                'reference' => $reference,
                'customer_phone' => $this->capture($rule['customer_phone_pattern'] ?? null, $message),
                'customer_name' => $this->capture($rule['customer_name_pattern'] ?? null, $message),
            ];
        }

        return null;
    }

    private function capture(?string $pattern, string $message): ?string
    {
        if (! $pattern) {
            return null;
        }

        $matched = preg_match($pattern, $message, $matches);
        if ($matched === false) {
            Log::error('Invalid customer matching pattern in system settings.');

            return null;
        }

        return $matched === 1 ? trim($matches[1] ?? '') ?: null : null;
    }
}
