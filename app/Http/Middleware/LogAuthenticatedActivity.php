<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAuthenticatedActivity
{
    public function __construct(private readonly ActivityLogService $activityLog)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user() || ! $request->route()) {
            return $response;
        }

        $meta = (array) $request->attributes->get('activity_log', []);

        $this->activityLog->log([
            'user' => $request->user(),
            'module_key' => $meta['module_key'] ?? $this->resolveModuleKey($request),
            'action' => $meta['action'] ?? $this->resolveAction($request),
            'description' => $meta['description'] ?? $this->resolveDescription($request, $response),
            'route_name' => $request->route()->getName(),
            'http_method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'location_label' => $meta['location_label'] ?? null,
            'subject_type' => $meta['subject_type'] ?? null,
            'subject_id' => $meta['subject_id'] ?? null,
            'properties' => array_merge([
                'query' => $this->sanitize($request->query()),
                'input' => $this->sanitize($request->except([
                    '_token',
                    '_method',
                    'password',
                    'password_confirmation',
                    'image',
                    'history',
                ])),
            ], $meta['properties'] ?? []),
        ]);

        return $response;
    }

    private function resolveModuleKey(Request $request): ?string
    {
        if ($request->route('module')) {
            return (string) $request->route('module');
        }

        $name = (string) $request->route()->getName();

        return match (true) {
            str_starts_with($name, 'siswa.absensi') => 'absensi',
            str_starts_with($name, 'siswa.agenda') => 'agenda',
            str_starts_with($name, 'agenda.review') => 'agenda',
            str_starts_with($name, 'attendance.qr') => 'attendance-qr',
            str_starts_with($name, 'activity-log') => 'activity-log',
            str_starts_with($name, 'manage-access') => 'manage-access',
            default => null,
        };
    }

    private function resolveAction(Request $request): string
    {
        $routeName = (string) $request->route()->getName();

        return $routeName !== ''
            ? str_replace('.', '_', $routeName)
            : strtolower($request->method()) . '_' . str_replace('/', '_', trim($request->path(), '/'));
    }

    private function resolveDescription(Request $request, Response $response): string
    {
        $routeName = $request->route()->getName() ?? $request->path();

        return sprintf(
            '%s %s menghasilkan status %s.',
            strtoupper($request->method()),
            $routeName,
            $response->getStatusCode()
        );
    }

    private function sanitize(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);
                continue;
            }

            if (is_string($value) && strlen($value) > 300) {
                $clean[$key] = substr($value, 0, 300) . '...';
                continue;
            }

            $clean[$key] = $value;
        }

        return $clean;
    }
}
