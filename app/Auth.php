<?php

class Auth
{
    private DataStore $store;

    public function __construct(DataStore $store)
    {
        $this->store = $store;
    }

    public function user(): ?array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            return null;
        }
        $token = $matches[1];
        foreach ($this->store->all('sessions') as $session) {
            if (($session['token'] ?? '') === $token) {
                foreach ($this->store->all('users') as $user) {
                    if ((int)$user['id'] === (int)$session['user_id']) {
                        unset($user['password']);
                        return $user;
                    }
                }
            }
        }
        return null;
    }

    public function requireUser(): array
    {
        $user = $this->user();
        if (!$user) {
            Response::json(['mensaje' => 'No autorizado'], 401);
        }
        return $user;
    }
}
