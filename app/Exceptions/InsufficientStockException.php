<?php

namespace App\Exceptions;

class InsufficientStockException extends BusinessRuleException
{
    /** @param list<array{item: string, required: string, available: string}> $shortages */
    public static function for(array $shortages): self
    {
        $names = collect($shortages)->pluck('item')->implode(', ');

        return new self("Not enough stock to fulfil this order: {$names}.", $shortages, 'stock');
    }
}
