<?php

class ContentController
{
    private DataStore $store;

    public function __construct(DataStore $store)
    {
        $this->store = $store;
    }

    public function index(): never
    {
        $items = $this->store->all('contents');
        $search = strtolower(trim((string)($_GET['buscar'] ?? '')));
        $type = strtolower(trim((string)($_GET['tipo'] ?? '')));
        $genre = strtolower(trim((string)($_GET['genero'] ?? '')));

        if ($search !== '') {
            $items = array_filter($items, fn($item) => str_contains(strtolower($item['title'] ?? ''), $search));
        }
        if ($type !== '') {
            $items = array_filter($items, fn($item) => strtolower($item['type'] ?? '') === $type);
        }
        if ($genre !== '') {
            $items = array_filter($items, fn($item) => strtolower($item['genre'] ?? '') === $genre);
        }

        Response::json(['contenidos' => array_values($items)]);
    }

    public function show(int $id): never
    {
        foreach ($this->store->all('contents') as $item) {
            if ((int)$item['id'] === $id) {
                Response::json(['contenido' => $item]);
            }
        }
        Response::json(['mensaje' => 'Contenido no encontrado'], 404);
    }

    public function create(array $body): never
    {
        $title = trim((string)($body['titulo'] ?? ''));
        $type = strtolower(trim((string)($body['tipo'] ?? '')));
        $genre = trim((string)($body['genero'] ?? ''));
        $year = (int)($body['anio'] ?? 0);
        $duration = (int)($body['duracion_minutos'] ?? 0);
        $description = trim((string)($body['descripcion'] ?? ''));

        if ($title === '' || !in_array($type, ['pelicula', 'serie'], true) || $genre === '' || $year < 1900 || $duration < 1) {
            Response::json(['mensaje' => 'Datos del contenido incompletos o inválidos'], 422);
        }

        $items = $this->store->all('contents');
        $item = [
            'id' => $this->store->nextId('contents'),
            'title' => $title,
            'type' => $type,
            'genre' => $genre,
            'year' => $year,
            'duration_minutes' => $duration,
            'description' => $description
        ];
        $items[] = $item;
        $this->store->save('contents', $items);
        Response::json(['mensaje' => 'Contenido creado', 'contenido' => $item], 201);
    }

    public function update(int $id, array $body): never
    {
        $items = $this->store->all('contents');
        foreach ($items as $index => $item) {
            if ((int)$item['id'] === $id) {
                $items[$index]['title'] = trim((string)($body['titulo'] ?? $item['title']));
                $items[$index]['type'] = strtolower(trim((string)($body['tipo'] ?? $item['type'])));
                $items[$index]['genre'] = trim((string)($body['genero'] ?? $item['genre']));
                $items[$index]['year'] = (int)($body['anio'] ?? $item['year']);
                $items[$index]['duration_minutes'] = (int)($body['duracion_minutos'] ?? $item['duration_minutes']);
                $items[$index]['description'] = trim((string)($body['descripcion'] ?? $item['description']));
                $this->store->save('contents', $items);
                Response::json(['mensaje' => 'Contenido actualizado', 'contenido' => $items[$index]]);
            }
        }
        Response::json(['mensaje' => 'Contenido no encontrado'], 404);
    }

    public function delete(int $id): never
    {
        $items = $this->store->all('contents');
        $filtered = array_values(array_filter($items, fn($item) => (int)$item['id'] !== $id));
        if (count($filtered) === count($items)) {
            Response::json(['mensaje' => 'Contenido no encontrado'], 404);
        }
        $this->store->save('contents', $filtered);
        Response::json(['mensaje' => 'Contenido eliminado']);
    }
}
