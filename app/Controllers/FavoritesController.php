<?php

class FavoritesController
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
        $favorites = array_values(array_filter(
            $this->store->all('favorites'),
            fn($favorite) => (int)$favorite['user_id'] === (int)$user['id']
        ));
        $contents = $this->store->all('contents');
        $result = [];
        foreach ($favorites as $favorite) {
            foreach ($contents as $content) {
                if ((int)$content['id'] === (int)$favorite['content_id']) {
                    $result[] = ['id' => $favorite['id'], 'contenido' => $content];
                }
            }
        }
        Response::json(['favoritos' => $result]);
    }

    public function create(array $body): never
    {
        $user = $this->auth->requireUser();
        $contentId = (int)($body['contenido_id'] ?? 0);
        $contents = $this->store->all('contents');
        $exists = false;
        foreach ($contents as $content) {
            if ((int)$content['id'] === $contentId) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            Response::json(['mensaje' => 'Contenido no encontrado'], 404);
        }

        $favorites = $this->store->all('favorites');
        foreach ($favorites as $favorite) {
            if ((int)$favorite['user_id'] === (int)$user['id'] && (int)$favorite['content_id'] === $contentId) {
                Response::json(['mensaje' => 'El contenido ya está en favoritos'], 409);
            }
        }

        $favorite = [
            'id' => $this->store->nextId('favorites'),
            'user_id' => (int)$user['id'],
            'content_id' => $contentId
        ];
        $favorites[] = $favorite;
        $this->store->save('favorites', $favorites);
        Response::json(['mensaje' => 'Contenido agregado a favoritos', 'favorito' => $favorite], 201);
    }

    public function delete(int $id): never
    {
        $user = $this->auth->requireUser();
        $favorites = $this->store->all('favorites');
        $filtered = array_values(array_filter(
            $favorites,
            fn($favorite) => !((int)$favorite['id'] === $id && (int)$favorite['user_id'] === (int)$user['id'])
        ));
        if (count($filtered) === count($favorites)) {
            Response::json(['mensaje' => 'Favorito no encontrado'], 404);
        }
        $this->store->save('favorites', $filtered);
        Response::json(['mensaje' => 'Favorito eliminado']);
    }
}
