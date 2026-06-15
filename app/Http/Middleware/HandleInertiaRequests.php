<?php

namespace App\Http\Middleware;

use App\Models\User;
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
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $role = $this->resolveRole($user);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'role' => $role,
            ],
            'notifications' => fn () => $user ? [
                'unread_count' => $user->unreadNotifications()->count(),
                'items' => $user->notifications()->limit(10)->get()->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'data' => $n->data,
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at' => $n->created_at?->toIso8601String(),
                ]),
            ] : ['unread_count' => 0, 'items' => []],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'newEmployeeCredentials' => fn () => $request->session()->get('newEmployeeCredentials'),
                'ticket_created' => fn () => $request->session()->get('ticket_created'),
                'draft_validation' => fn () => $request->session()->get('draft_validation'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    private function resolveRole(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if ($user->zaposlen) {
            return $user->zaposlen->role;
        }

        return 'kupac';
    }
}
