<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Handle the incoming request and prevent stale browser caching.
     */
    public function handle(Request $request, \Closure $next)
    {
        $response = parent::handle($request, $next);

        // Always prevent stale browser caching on Inertia responses and admin/dashboard requests
        if ($request->header('X-Inertia') || $request->is('admin*', 'dashboard*', 'lms/admin*', 'lms/student*')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'username' => $request->user()->username,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'roles' => $request->user()->roles,
                    'is_active' => (bool) $request->user()->is_active,
                ] : null,
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
            'settings' => fn() => \Illuminate\Support\Facades\Cache::remember('shared_website_settings', 3600, fn() => \Modules\Website\Models\WebsiteSetting::first()),
        ];
    }
}
