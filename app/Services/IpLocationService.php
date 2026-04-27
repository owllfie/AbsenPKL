<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IpLocationService
{
    public function lookup(?string $ip): array
    {
        if (! $ip) {
            return [
                'ip' => null,
                'label' => 'IP tidak tersedia',
                'provider' => 'system',
                'details' => [],
            ];
        }

        if ($this->isLocalIp($ip)) {
            return [
                'ip' => $ip,
                'label' => 'Jaringan lokal / private IP',
                'provider' => 'system',
                'details' => ['note' => 'Lokasi detail tidak tersedia untuk private IP.'],
            ];
        }

        try {
            $response = Http::acceptJson()
                ->timeout(6)
                ->get(rtrim((string) config('services.ip_geolocation.endpoint', 'https://ipwho.is/'), '/') . '/' . $ip);

            if (! $response->successful()) {
                return $this->fallback($ip);
            }

            $data = $response->json();

            if (($data['success'] ?? true) === false) {
                return $this->fallback($ip, $data);
            }

            $parts = array_filter([
                $data['city'] ?? null,
                $data['region'] ?? ($data['regionName'] ?? null),
                $data['country'] ?? null,
            ]);

            return [
                'ip' => $ip,
                'label' => $parts ? implode(', ', $parts) : 'Lokasi tidak diketahui',
                'provider' => 'ip_geolocation',
                'details' => $data,
            ];
        } catch (\Throwable) {
            return $this->fallback($ip);
        }
    }

    private function fallback(string $ip, array $details = []): array
    {
        return [
            'ip' => $ip,
            'label' => 'Lokasi tidak dapat ditentukan',
            'provider' => 'fallback',
            'details' => $details,
        ];
    }

    private function isLocalIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
