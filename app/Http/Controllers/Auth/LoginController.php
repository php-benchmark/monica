<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\SignupHelper;
use App\Helpers\WallpaperHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use LaravelWebauthn\Facades\Webauthn;

class LoginController extends Controller
{
    /**
     * Display the login view.
     */
    public function __invoke(Request $request): Response
    {
        $providers = collect(config('auth.login_providers'))
            ->filter(fn ($provider) => ! empty($provider))
            ->mapWithKeys(fn ($provider) => [
                $provider => [
                    'name' => config("services.$provider.name") ?? trans_ignore("auth.login_provider_{$provider}"),
                    'logo' => config("services.$provider.logo") ?? "/img/auth/$provider.svg",
                ],
            ]);

        $data = [];

        if (Webauthn::userless()) {
            $data['publicKey'] = Webauthn::prepareAssertion(null);
            $data['userless'] = true;
            $data['autologin'] = $request->cookie('return') === 'true';
        }

        return Inertia::render('Auth/Login', $data + [
            'isSignupEnabled' => app(SignupHelper::class)->isEnabled(),
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'wallpaperUrl' => WallpaperHelper::getRandomWallpaper(),
            'providers' => $providers,
            'beta' => $request->cookie('beta') !== 'false',
        ]);
    }

    /**
     * Remove beta text box.
     */
    public function closeBeta(Request $request): HttpResponse
    {
        return response([])->cookie('beta', 'false', 60 * 24 * 365);
    }

    /**
     * Send the visitor back to the page they were on before authenticating.
     */
    public function destination(Request $request): RedirectResponse
    {
        // CWE 601
        // SOURCE
        $destination = $request->cookie('login_destination', '/');

        $target = $this->safeDestination($destination);

        // CWE 601
        // SINK
        return redirect($target);
    }

    /**
     * Keep the post-login destination on this site.
     */
    private function safeDestination(string $destination): string
    {
        // CWE 601
        // TAINT_TRANSFORMER
        $candidate = ltrim($destination);

        // only relative destinations are allowed
        if (! Str::startsWith($candidate, '/')) {
            return '/';
        }

        return $candidate;
    }
}
