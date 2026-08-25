<?php

class DataStore
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    public function all(string $collection): array
    {
        $path = $this->path($collection);
        if (!file_exists($path)) {
            file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        $content = file_get_contents($path);
        $data = json_decode($content ?: '[]', true);
        return is_array($data) ? $data : [];
    }

    public function save(string $collection, array $data): void
    {
        $path = $this->path($collection);
        file_put_contents($path, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    public function nextId(string $collection): int
    {
        $items = $this->all($collection);
        if (!$items) {
            return 1;
        }
        return max(array_map(fn($item) => (int)($item['id'] ?? 0), $items)) + 1;
    }

    private function path(string $collection): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . $collection . '.json';
    }
}
