<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerificationEmailController extends Controller
{
    /** Valide le lien signé et marque l'email comme vérifié. */
    public function verifier(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('compte.securite')->with('info', 'Votre email est déjà vérifié.');
        }

        $request->fulfill(); // marque vérifié + déclenche l'événement Verified

        return redirect()->route('compte.securite')
            ->with('success', 'Email vérifié ✅ Vos avis comptent désormais davantage.');
    }

    /** Renvoie l'email de vérification. */
    public function renvoyer(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('info', 'Votre email est déjà vérifié.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Email de vérification renvoyé. Consultez votre boîte mail.');
    }
}
