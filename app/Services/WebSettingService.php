<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class WebSettingService
{
    public function settings(): array
    {
        $stored = [];

        if (File::exists($this->settingsPath())) {
            $decoded = json_decode((string) File::get($this->settingsPath()), true);
            $stored = is_array($decoded) ? $decoded : [];
        }

        return array_merge($this->defaults(), $stored);
    }

    public function update(array $data, ?UploadedFile $logo = null): array
    {
        $settings = array_merge($this->settings(), [
            'web_name' => $data['web_name'],
            'theme' => $data['theme'],
        ]);

        if (! empty($data['remove_logo'])) {
            $this->deleteLogo($settings['logo_path'] ?? null);
            $settings['logo_path'] = null;
        }

        if ($logo) {
            $this->deleteLogo($settings['logo_path'] ?? null);
            $settings['logo_path'] = $this->storeLogo($logo);
        }

        File::ensureDirectoryExists(dirname($this->settingsPath()));
        File::put($this->settingsPath(), json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $settings;
    }

    public function themePresets(): array
    {
        return [
            'amber' => [
                'label' => 'Amber',
                'vars' => [
                    '--bg' => '#fff8f1',
                    '--bg-deep' => '#f1e1d0',
                    '--surface' => 'rgba(255, 252, 247, 0.84)',
                    '--surface-strong' => '#fffdfa',
                    '--line' => 'rgba(170, 117, 51, 0.16)',
                    '--text' => '#2d2118',
                    '--muted' => '#6c594c',
                    '--primary' => '#d97706',
                    '--primary-deep' => '#b45309',
                    '--success' => '#e7f5e8',
                    '--shadow' => '0 24px 60px rgba(124, 80, 27, 0.16)',
                ],
            ],
            'ocean' => [
                'label' => 'Ocean',
                'vars' => [
                    '--bg' => '#f2fbff',
                    '--bg-deep' => '#d7eef5',
                    '--surface' => 'rgba(248, 254, 255, 0.88)',
                    '--surface-strong' => '#ffffff',
                    '--line' => 'rgba(55, 118, 148, 0.16)',
                    '--text' => '#18333f',
                    '--muted' => '#5d7784',
                    '--primary' => '#0f9ec7',
                    '--primary-deep' => '#0b7594',
                    '--success' => '#e2f7ef',
                    '--shadow' => '0 24px 60px rgba(23, 85, 109, 0.14)',
                ],
            ],
            'forest' => [
                'label' => 'Forest',
                'vars' => [
                    '--bg' => '#f5fbf4',
                    '--bg-deep' => '#dcebd8',
                    '--surface' => 'rgba(251, 255, 250, 0.88)',
                    '--surface-strong' => '#ffffff',
                    '--line' => 'rgba(67, 112, 76, 0.15)',
                    '--text' => '#1d3121',
                    '--muted' => '#5d7060',
                    '--primary' => '#2f855a',
                    '--primary-deep' => '#236443',
                    '--success' => '#e2f7e8',
                    '--shadow' => '0 24px 60px rgba(40, 80, 48, 0.14)',
                ],
            ],
            'ruby' => [
                'label' => 'Ruby',
                'vars' => [
                    '--bg' => '#fff7f7',
                    '--bg-deep' => '#f2dada',
                    '--surface' => 'rgba(255, 252, 252, 0.88)',
                    '--surface-strong' => '#ffffff',
                    '--line' => 'rgba(149, 81, 81, 0.16)',
                    '--text' => '#3f2020',
                    '--muted' => '#7f6161',
                    '--primary' => '#d14d72',
                    '--primary-deep' => '#a43a58',
                    '--success' => '#e7f5e8',
                    '--shadow' => '0 24px 60px rgba(120, 63, 63, 0.14)',
                ],
            ],
        ];
    }

    public function themeVariables(): array
    {
        $settings = $this->settings();
        $presets = $this->themePresets();

        return $presets[$settings['theme']]['vars'] ?? $presets['amber']['vars'];
    }

    public function logoUrl(): ?string
    {
        $path = $this->settings()['logo_path'] ?? null;

        if (! $path) {
            return null;
        }

        return asset(ltrim(str_replace('\\', '/', $path), '/'));
    }

    public function brandMarkText(): string
    {
        $name = trim((string) $this->settings()['web_name']);
        $parts = preg_split('/\s+/', $name) ?: [];
        $mark = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $mark .= strtoupper(substr($part, 0, 1));
        }

        return $mark !== '' ? $mark : 'PKL';
    }

    private function defaults(): array
    {
        return [
            'web_name' => (string) config('app.name', 'PKL Monitor'),
            'logo_path' => null,
            'theme' => 'amber',
        ];
    }

    private function settingsPath(): string
    {
        return storage_path('app/settings/web-settings.json');
    }

    private function storeLogo(UploadedFile $logo): string
    {
        $directory = public_path('uploads/web-settings');
        File::ensureDirectoryExists($directory);

        $filename = 'logo_' . now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $logo->getClientOriginalExtension();
        $logo->move($directory, $filename);

        return 'uploads/web-settings/' . $filename;
    }

    private function deleteLogo(?string $path): void
    {
        if (! $path) {
            return;
        }

        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}
