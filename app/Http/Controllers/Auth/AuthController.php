<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TypeEvenement;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Evenement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('classement.index')
            ->with('success', 'Bienvenue '.$user->pseudo_public.' ! Votre compte est créé.');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);
        }

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
