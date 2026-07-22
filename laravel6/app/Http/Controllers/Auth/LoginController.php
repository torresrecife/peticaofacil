<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except(['logout', 'logoutBridge']);
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('login_usu', $credentials['username'])
            ->where('status_usu', 'ATI')
            ->first();

        if (!$user || $user->senha_usu !== md5($credentials['password'])) {
            return back()
                ->withErrors(['username' => 'Usuario ou senha invalidos.'])
                ->withInput($request->only('username'));
        }

        Auth::login($user);
        $request->session()->regenerate();

        $user->forceFill([
            'acesso_usu' => now(),
        ])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        return $this->performLogout($request);
    }

    public function logoutBridge(Request $request)
    {
        return $this->performLogout($request);
    }

    protected function performLogout(Request $request)
    {
        $this->purgeLegacySession();

        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function purgeLegacySession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        $legacyName = 'PHPSESSID';
        $legacyId = request()->cookie($legacyName);

        if (!$legacyId) {
            return;
        }

        $previousName = session_name();
        $previousId = session_id();

        @session_name($legacyName);
        @session_id($legacyId);

        if (@session_start()) {
            $_SESSION = [];
            @session_destroy();
        }

        @session_write_close();

        if ($previousName) {
            @session_name($previousName);
        }

        if ($previousId) {
            @session_id($previousId);
        }

        setcookie($legacyName, '', time() - 3600, '/');
    }
}
