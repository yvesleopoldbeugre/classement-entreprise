<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TypeEvenement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Evenement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        // Pseudo public et nom auto-générés si non fournis (inscription rapide).
        $base = $data['name'] ?? Str::before($data['email'], '@');
        $data['pseudo_public'] ??= User::pseudoUnique($base);
        $data['name'] ??= $data['pseudo_public'];

        $user = User::create($data);

        Auth::login($user);
        $request->session()->regenerate();

        // Inscription rapide via le modal (AJAX) → réponse JSON, sinon redirection.
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect' => route('classement.index')]);
        }

        return redirect()->route('classement.index')
            ->with('success', 'Bienvenue '.$user->pseudo_public.' ! Votre compte est créé.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        // Authentification + anti-force brute (voir LoginRequest::authenticate).
        $request->authenticate();

        $request->session()->regenerate();
        Evenement::log(TypeEvenement::Connexion, null, ['user_id' => Auth::id()]);

        return redirect()->intended(route('classement.index'))
            ->with('success', 'Content de vous revoir !');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('classement.index');
    }
}
