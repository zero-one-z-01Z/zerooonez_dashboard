<?php

namespace App\Dashboard\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if ($this->guard()->check()) {
            return redirect()->route($this->routeName('home'));
        }

        return view((string) config('dashboard.auth.login_view', 'dashboard::auth.login'), [
            'loginUrl' => route($this->routeName('login')),
            'field' => (string) config('dashboard.auth.login_field', 'email'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $field = (string) config('dashboard.auth.login_field', 'email');
        $rules = $field === 'email' ? ['required', 'email'] : ['required', 'string'];
        $validated = $request->validate([
            $field => $rules,
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! $this->guard()->attempt([
            $field => $validated[$field],
            'password' => $validated['password'],
        ], (bool) ($validated['remember'] ?? false))) {
            throw ValidationException::withMessages([
                $field => __('dashboard::auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route($this->routeName('home')));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($this->routeName('login'));
    }

    private function guard(): \Illuminate\Contracts\Auth\StatefulGuard
    {
        return Auth::guard((string) config('dashboard.auth.guard', 'web'));
    }

    private function routeName(string $name): string
    {
        $prefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');

        return ($prefix === '' ? '' : $prefix.'.').$name;
    }
}
