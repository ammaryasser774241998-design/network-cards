<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PhoneExclusion;

class PhoneExclusionService
{
    public function excludes(?string $phone, ?Customer $customer = null): bool
    {
        $numbers = collect([$phone, $customer?->phone_number])
            ->filter()
            ->map(fn (string $number) => $this->normalize($number))
            ->filter(fn (string $number) => $number !== '')
            ->unique()
            ->all();

        if ($numbers === []) {
            return false;
        }

        return PhoneExclusion::query()->whereIn('phone_number', $numbers)->exists();
    }

    public function normalize(string $phone): string
    {
        return (string) preg_replace('/\D+/', '', $phone);
    }
}
