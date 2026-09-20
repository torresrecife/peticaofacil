<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use \Illuminate\Foundation\Auth\ThrottlesLogins;

    public function username()
    {
        return 'username';
    }

    public function __construct()
    {
        $this->middleware('guest')->except([
            'logout',
            'showForcePasswordForm',
            'updateForcedPassword',
        ]);
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request, UserAccountService $userAccountService)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        $user = User::where('login_usu', $credentials['username'])
            ->where('status_usu', 'ATI')
            ->first();

        if (!$user || !$userAccountService->verifyPassword($user, $credentials['password'])
            || ($user->temporary_password_expires_at && $user->temporary_password_expires_at->isPast())) {
            $this->incrementLoginAttempts($request);
            return back()
                ->withErrors(['username' => 'Usuario ou senha invalidos. Se o acesso temporario expirou, solicite uma nova senha ao responsavel.'])
                ->withInput($request->only('username'));
        }

        Auth::login($user);
        $this->clearLoginAttempts($request);
        $request->session()->regenerate();
        $request->session()->put('password_hash', $user->getAuthPassword());
        $userAccountService->linkImportedRecords($user);

        if ($user->requiresInitialPasswordChange()) {
            return redirect()->route('password.force');
        }

        $userAccountService->touchAccess($user);

        return redirect()->intended(route('dashboard'));
    }

    public function showForcePasswordForm()
    {
        if (!Auth::user()->requiresInitialPasswordChange()) {
            return redirect()->route('dashboard');
        }
        return view('auth.force-password');
    }

    public function updateForcedPassword(Request $request, UserAccountService $userAccountService)
    {
        abort_unless(Auth::user()->requiresInitialPasswordChange(), 403);
        $data = $request->validate([
            'password' => ['required', 'string', 'min:12', 'max:64', 'confirmed', function ($attribute, $value, $fail) use ($userAccountService) {
                if (!preg_match('/\p{Lu}/u', $value) || !preg_match('/\p{Ll}/u', $value)
                    || !preg_match('/[0-9]/', $value) || !preg_match('/[\p{P}\p{S}]/u', $value)) {
                    $fail('A senha deve conter letra maiuscula, letra minuscula, numero e simbolo. Espacos nao contam como simbolo.');
                }
                if (strlen($value) > 72) {
                    $fail('A senha deve ter no maximo 72 bytes.');
                } elseif ($userAccountService->verifyPassword(Auth::user(), $value)) {
                    $fail('Escolha uma senha diferente da senha temporaria.');
                } elseif (preg_match('/^(.)\1+$/u', $value)) {
                    $fail('Escolha uma frase-senha que nao seja formada por um unico caractere repetido.');
                }
            }],
        ], ['password.min' => 'A senha deve ter pelo menos 12 caracteres.']);

        /** @var \App\User $user */
        $user = Auth::user();

        $userAccountService->updatePassword($user, $data['password']);
        $userAccountService->touchAccess($user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->put('password_hash', $user->getAuthPassword());

        return redirect()->route('dashboard')->with('status', 'Senha atualizada com sucesso.');
    }

    public function logout(Request $request)
    {
        return $this->performLogout($request);
    }

    protected function performLogout(Request $request)
    {
        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

}
