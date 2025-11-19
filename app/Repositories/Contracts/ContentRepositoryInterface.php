<?php

namespace App\Repositories\Contracts;

interface ContentRepositoryInterface
{
    public function create(array $data);

    public function update(string $id, array $data);

    public function delete(string $id): bool;

    public function find(string $id);

    public function all(array $filters = []);
}
