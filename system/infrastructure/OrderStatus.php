<?php
declare(strict_types=1);

namespace CoreCart\System\Infrastructure;

enum OrderStatus: int
{
    case Pending = 0;
    case Processing = 1;
    case Shipped = 2;
    case Delivered = 3;
    case Cancelled = 9;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Returns the list of valid status transitions from this status.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered],
            self::Delivered => [],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public static function fromInt(int $value): self
    {
        return self::tryFrom($value) ?? self::Pending;
    }
}
