<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
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

        $user = User::where('login_usu', $credentials['username'])
            ->where('status_usu', 'ATI')
            ->first();

        if (!$user || !$userAccountService->verifyPassword($user, $credentials['password'])) {
            return back()
                ->withErrors(['username' => 'Usuario ou senha invalidos.'])
                ->withInput($request->only('username'));
        }

        Auth::login($user);
        $request->session()->regenerate();
        $userAccountService->linkImportedRecords($user);

        if ($user->requiresInitialPasswordChange()) {
            return redirect()->route('password.force');
        }

        $userAccountService->touchAccess($user);

        return redirect()->intended(route('dashboard'));
    }

    public function showForcePasswordForm()
    {
        return view('auth.force-password');
    }

    public function updateForcedPassword(Request $request, UserAccountService $userAccountService)
    {
        $data = $request->validate([
            'password' => 'required|string|min:4|confirmed',
        ]);

        /** @var \App\User $user */
        $user = Auth::user();

        $userAccountService->updatePassword($user, $data['password']);
        $userAccountService->touchAccess($user);

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
