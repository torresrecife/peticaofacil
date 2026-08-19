<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Services\UserAccountService;
use App\Setor;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::with('setor')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('nome_usu', 'like', '%' . $search . '%')
                        ->orWhere('login_usu', 'like', '%' . $search . '%')
                        ->orWhere('email_usu', 'like', '%' . $search . '%');

                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->orderBy('id')
            ->paginate(20)
            ->appends($request->only('q'));

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

        return view('admin.users.index', compact('users', 'search'));
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

        app(UserAccountService::class)->create($this->normalizeData($data), md5($data['password']));

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
        $data = $this->validateData($request, $user);

        app(UserAccountService::class)->update(
            $user,
            $this->normalizeData($data),
            !empty($data['password']) ? md5($data['password']) : null
        );

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario atualizado.');
    }

    protected function validateData(Request $request, User $user = null)
    {
        $passwordRule = $user ? 'nullable|string|min:4|confirmed' : 'required|string|min:4|confirmed';
        $appUserId = $user ? $user->id : null;

        $rules = [
            'nome_usu' => 'required|string|max:50',
            'login_usu' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'login_usu')->ignore($appUserId),
            ],
            'email_usu' => 'nullable|email|max:50',
            'nivel_usu' => ['required', Rule::in(['ADM', 'GER', 'USU'])],
            'status_usu' => ['required', Rule::in(['ATI', 'INA'])],
            'id_setor' => 'nullable|integer',
            'cliente_ids' => 'nullable|array',
            'cliente_ids.*' => 'integer',
            'password' => $passwordRule,
        ];

        return $request->validate($rules);
    }

    protected function normalizeData(array $data)
    {
        $data['id_cliente'] = !empty($data['cliente_ids']) ? implode(',', $data['cliente_ids']) : '0';

        return $data;
    }
}
