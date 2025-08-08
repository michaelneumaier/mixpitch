<?php

namespace App\Services;

use App\Models\User;

class BrandingResolver
{
    public function forProducer(User $producer): array
    {
        return [
            'logo_url' => $producer->brand_logo_url ?? null,
            'primary' => $producer->brand_primary ?? '#1f2937',
            'secondary' => $producer->brand_secondary ?? '#4f46e5',
            'text' => $producer->brand_text ?? '#111827',
            'brand_display' => trim(($producer->name ?: '')),
            'invite_subject' => $producer->invite_email_subject ?? null,
            'invite_body' => $producer->invite_email_body ?? null,
        ];
    }
}


