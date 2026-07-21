<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\Setor;
use App\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('dashboard', [
            'userCount' => User::count(),
            'setorCount' => Setor::count(),
            'clienteCount' => Cliente::count(),
            'activeUserCount' => User::active()->count(),
        ]);
    }
}
