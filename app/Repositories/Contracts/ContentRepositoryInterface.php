<?php

namespace App\Repositories\Contracts;

interface ContentRepositoryInterface
{
    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string $id): bool;

    public function find(string $id): mixed;

    public function all(array $filters = []): mixed;
}
