<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Setor;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('setor')->orderBy('id_usu')->paginate(20);
        $clientMap = Cliente::active()->orderBy('cliente_name')->get()->keyBy('cliente_id');

        foreach ($users as $user) {
            $names = [];
            foreach ($user->client_ids as $clientId) {
                if (isset($clientMap[$clientId])) {
                    $names[] = $clientMap[$clientId]->cliente_name;
                }
            }
            $user->client_labels = $names;
        }

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.form', [
            'user' => new User(['status_usu' => 'ATI', 'nivel_usu' => 'USU']),
            'setores' => Setor::orderBy('nome_setor')->get(),
            'clientes' => Cliente::active()->orderBy('cliente_name')->get(),
            'selectedClients' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $user = new User();
        $this->fillUser($user, $data);
        $user->senha_usu = md5($data['password']);
        $user->data_cad = now();
        $user->save();

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario criado.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'user' => $user,
            'setores' => Setor::orderBy('nome_setor')->get(),
            'clientes' => Cliente::active()->orderBy('cliente_name')->get(),
            'selectedClients' => $user->client_ids,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateData($request, $user->id_usu);

        $this->fillUser($user, $data);
        if (!empty($data['password'])) {
            $user->senha_usu = md5($data['password']);
        }
        $user->save();

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario atualizado.');
    }

    protected function validateData(Request $request, $userId = null)
    {
        $passwordRule = $userId ? 'nullable|string|min:4|confirmed' : 'required|string|min:4|confirmed';

        return $request->validate([
            'nome_usu' => 'required|string|max:50',
            'login_usu' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tp_usu_tb', 'login_usu')->ignore($userId, 'id_usu'),
            ],
            'email_usu' => 'nullable|email|max:50',
            'nivel_usu' => ['required', Rule::in(['ADM', 'GER', 'USU'])],
            'status_usu' => ['required', Rule::in(['ATI', 'INA'])],
            'id_setor' => 'nullable|integer',
            'cliente_ids' => 'nullable|array',
            'cliente_ids.*' => 'integer',
            'password' => $passwordRule,
        ]);
    }

    protected function fillUser(User $user, array $data)
    {
        $user->nome_usu = $data['nome_usu'];
        $user->login_usu = $data['login_usu'];
        $user->email_usu = $data['email_usu'] ?? null;
        $user->nivel_usu = $data['nivel_usu'];
        $user->status_usu = $data['status_usu'];
        $user->id_setor = $data['id_setor'] ?: null;
        $user->id_cliente = !empty($data['cliente_ids']) ? implode(',', $data['cliente_ids']) : '0';
    }
}
