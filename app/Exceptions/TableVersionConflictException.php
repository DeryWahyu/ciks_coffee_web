<?php

namespace App\Exceptions;

use App\Models\CoffeeTable;
use RuntimeException;

class TableVersionConflictException extends RuntimeException
{
    public function __construct(public readonly CoffeeTable $coffeeTable)
    {
        parent::__construct('Data meja telah diperbarui oleh pengguna lain. Muat ulang data meja lalu coba lagi.');
    }
}
