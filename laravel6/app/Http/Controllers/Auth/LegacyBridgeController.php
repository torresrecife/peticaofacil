<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegacyBridgeController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'uid' => 'required|integer',
            'ts' => 'required|integer',
            'path' => 'required|string',
            'sig' => 'required|string',
        ]);

        if (abs(time() - (int) $data['ts']) > 300) {
            abort(403, 'Link expirado.');
        }

        $path = '/' . ltrim($data['path'], '/');
        $signature = hash_hmac('sha256', $data['uid'] . '|' . $data['ts'] . '|' . $path, $this->bridgeKey());

        if (!hash_equals($signature, $data['sig'])) {
            abort(403, 'Assinatura invalida.');
        }

        $user = User::active()->findOrFail($data['uid']);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect($path);
    }

    protected function bridgeKey()
    {
        return (string) env('LEGACY_BRIDGE_KEY', config('app.key'));
    }
}
