<?php

class AuthController
{
    private DataStore $store;

    public function __construct(DataStore $store)
    {
        $this->store = $store;
    }

    public function register(array $body): never
    {
        $name = trim((string)($body['nombre'] ?? ''));
        $email = strtolower(trim((string)($body['correo'] ?? '')));
        $password = (string)($body['contrasena'] ?? '');

        if ($name === '' || $email === '' || $password === '') {
            Response::json(['mensaje' => 'Nombre, correo y contraseña son obligatorios'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['mensaje' => 'Correo no válido'], 422);
        }

        if (strlen($password) < 6) {
            Response::json(['mensaje' => 'La contraseña debe tener mínimo 6 caracteres'], 422);
        }

        $users = $this->store->all('users');
        foreach ($users as $user) {
            if (($user['email'] ?? '') === $email) {
                Response::json(['mensaje' => 'El correo ya está registrado'], 409);
            }
        }

        $newUser = [
            'id' => $this->store->nextId('users'),
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'usuario'
        ];
        $users[] = $newUser;
        $this->store->save('users', $users);
        unset($newUser['password']);

        Response::json(['mensaje' => 'Usuario registrado correctamente', 'usuario' => $newUser], 201);
    }

    public function login(array $body): never
    {
        $email = strtolower(trim((string)($body['correo'] ?? '')));
        $password = (string)($body['contrasena'] ?? '');

        foreach ($this->store->all('users') as $user) {
            if (($user['email'] ?? '') === $email && password_verify($password, $user['password'] ?? '')) {
                $sessions = array_values(array_filter(
                    $this->store->all('sessions'),
                    fn($session) => (int)$session['user_id'] !== (int)$user['id']
                ));
                $token = bin2hex(random_bytes(32));
                $sessions[] = [
                    'id' => $this->store->nextId('sessions'),
                    'user_id' => (int)$user['id'],
                    'token' => $token,
                    'created_at' => date('c')
                ];
                $this->store->save('sessions', $sessions);
                unset($user['password']);
                Response::json([
                    'mensaje' => 'Autenticación satisfactoria',
                    'token' => $token,
                    'usuario' => $user
                ]);
            }
        }

        Response::json(['mensaje' => 'Error en la autenticación'], 401);
    }
}
