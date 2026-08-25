<?php

class ProgressController
{
    private DataStore $store;
    private Auth $auth;

    public function __construct(DataStore $store, Auth $auth)
    {
        $this->store = $store;
        $this->auth = $auth;
    }

    public function index(): never
    {
        $user = $this->auth->requireUser();
        $progress = array_values(array_filter(
            $this->store->all('progress'),
            fn($item) => (int)$item['user_id'] === (int)$user['id']
        ));
        Response::json(['progreso' => $progress]);
    }

    public function save(array $body): never
    {
        $user = $this->auth->requireUser();
        $contentId = (int)($body['contenido_id'] ?? 0);
        $second = max(0, (int)($body['segundo'] ?? 0));
        $items = $this->store->all('progress');

        foreach ($items as $index => $item) {
            if ((int)$item['user_id'] === (int)$user['id'] && (int)$item['content_id'] === $contentId) {
                $items[$index]['second'] = $second;
                $items[$index]['updated_at'] = date('c');
                $this->store->save('progress', $items);
                Response::json(['mensaje' => 'Progreso actualizado', 'progreso' => $items[$index]]);
            }
        }

        $progress = [
            'id' => $this->store->nextId('progress'),
            'user_id' => (int)$user['id'],
            'content_id' => $contentId,
            'second' => $second,
            'updated_at' => date('c')
        ];
        $items[] = $progress;
        $this->store->save('progress', $items);
        Response::json(['mensaje' => 'Progreso guardado', 'progreso' => $progress], 201);
    }

    public function delete(int $id): never
    {
        $user = $this->auth->requireUser();
        $items = $this->store->all('progress');
        $filtered = array_values(array_filter(
            $items,
            fn($item) => !((int)$item['id'] === $id && (int)$item['user_id'] === (int)$user['id'])
        ));
        if (count($filtered) === count($items)) {
            Response::json(['mensaje' => 'Registro de progreso no encontrado'], 404);
        }
        $this->store->save('progress', $filtered);
        Response::json(['mensaje' => 'Progreso eliminado']);
    }
}
