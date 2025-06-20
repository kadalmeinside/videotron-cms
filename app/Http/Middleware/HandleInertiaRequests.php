<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\Models\Media;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'), // KIRIM PERMISSIONS
                ] : null,
            ],
            'pending_media_count' => fn () => $request->user() && $request->user()->can('approve_media')
                ? Media::where('is_approved', false)->count()
                : 0,
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [ // Pastikan flash message di-handle
                'message' => fn () => $request->session()->get('message'),
                'type' => fn () => $request->session()->get('type'),
            ],
            'app_settings' => function () {
                return Cache::rememberForever('app_settings', function () {
                    return Setting::all()->pluck('value', 'key');
                });
            },
        ]);
    }
}
