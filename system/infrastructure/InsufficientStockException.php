<?php
declare(strict_types=1);

namespace CoreCart\System\Infrastructure;

class InsufficientStockException extends \RuntimeException
{
    public function __construct(string $productName, int $requested, int $available)
    {
        parent::__construct(
            "Insufficient stock for '{$productName}': requested {$requested}, available {$available}"
        );
    }
}
