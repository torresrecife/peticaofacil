<?php

namespace App\Http\Controllers\Admin;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Services\UserAccountService;
use App\Setor;
use App\User;
use App\Policies\UserPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::with('setor')
            ->when($request->user()->nivel_usu !== 'ADM', function ($query) use ($request) {
                $policy = new UserPolicy();
                $ids = User::where('nivel_usu', 'USU')
                    ->where('id_setor', $request->user()->id_setor)
                    ->get(['id', 'nivel_usu', 'id_cliente', 'id_setor'])
                    ->filter(function ($target) use ($policy, $request) {
                        return $policy->update($request->user(), $target);
                    })->pluck('id');
                $query->whereIn('id', $ids);
            })
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
        $this->authorize('create', User::class);
        return view('admin.users.form', [
            'user' => new User(['status_usu' => 'ATI', 'nivel_usu' => 'USU',
                'id_setor' => auth()->user()->nivel_usu === 'GER' ? auth()->user()->id_setor : null]),
            'setores' => $this->availableSectors(),
            'clientes' => $this->availableClients(),
            'selectedClients' => [],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $data = $this->validateData($request);

        $temporaryPassword = bin2hex(random_bytes(12));
        $user = app(UserAccountService::class)->create($this->normalizeData($data), $temporaryPassword);

        return $this->temporaryCredentials($user, $temporaryPassword);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('admin.users.form', [
            'user' => $user,
            'setores' => $this->availableSectors(),
            'clientes' => $this->availableClients(),
            'selectedClients' => $user->client_ids,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $data = $this->validateData($request, $user);

        $temporaryPassword = !empty($data['reset_password']) ? bin2hex(random_bytes(12)) : null;
        app(UserAccountService::class)->update(
            $user,
            $this->normalizeData($data),
            $temporaryPassword
        );

        if ($temporaryPassword !== null) {
            return $this->temporaryCredentials($user, $temporaryPassword);
        }

        return redirect()->route('admin.usuarios.index')->with('status', 'Usuario atualizado.');
    }

    protected function validateData(Request $request, User $user = null)
    {
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
            'reset_password' => 'sometimes|boolean',
        ];

        if ($request->user()->nivel_usu !== 'ADM') {
            $rules['nivel_usu'] = ['required', Rule::in(['USU'])];
            $rules['id_setor'] = ['required', 'integer',
                Rule::in([$request->user()->id_setor]), Rule::exists('setores', 'id_setor')];
            $rules['cliente_ids'] = 'required|array|min:1';
            $rules['cliente_ids.*'] = [
                'required', 'integer', 'distinct',
                Rule::in((new UserPolicy())->managedClientIds($request->user())),
                Rule::exists('clientes', 'cliente_id'),
            ];
        }

        return $request->validate($rules);
    }

    protected function availableSectors()
    {
        return Setor::when(auth()->user()->nivel_usu !== 'ADM', function ($query) {
            $query->where('id_setor', auth()->user()->id_setor);
        })->orderBy('nome_setor')->get();
    }

    protected function availableClients()
    {
        return Cliente::active()
            ->when(auth()->user()->nivel_usu !== 'ADM', function ($query) {
                $query->whereIn('cliente_id', (new UserPolicy())->managedClientIds(auth()->user()));
            })->orderBy('cliente_name')->get();
    }

    protected function normalizeData(array $data)
    {
        $data['id_cliente'] = !empty($data['cliente_ids']) ? implode(',', $data['cliente_ids']) : '0';

        return $data;
    }

    protected function temporaryCredentials(User $user, $temporaryPassword)
    {
        return response()->view('admin.users.temporary-password', compact('user', 'temporaryPassword'))
            ->header('Cache-Control', 'no-store, private, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
