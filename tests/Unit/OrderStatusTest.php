<?php
declare(strict_types=1);

namespace CoreCart\Tests\Unit;

use PHPUnit\Framework\TestCase;
use CoreCart\System\Infrastructure\OrderStatus;

class OrderStatusTest extends TestCase
{
    public function testFromInt(): void
    {
        $this->assertSame(OrderStatus::Pending, OrderStatus::fromInt(0));
        $this->assertSame(OrderStatus::Processing, OrderStatus::fromInt(1));
        $this->assertSame(OrderStatus::Shipped, OrderStatus::fromInt(2));
        $this->assertSame(OrderStatus::Delivered, OrderStatus::fromInt(3));
        $this->assertSame(OrderStatus::Cancelled, OrderStatus::fromInt(9));
    }

    public function testFromIntThrowsOnInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order status value: 99');
        OrderStatus::fromInt(99);
    }

    public function testTryFromInt(): void
    {
        $this->assertSame(OrderStatus::Pending, OrderStatus::tryFromInt(0));
        $this->assertSame(OrderStatus::Processing, OrderStatus::tryFromInt(1));
        $this->assertNull(OrderStatus::tryFromInt(99));
    }

    public function testLabel(): void
    {
        $this->assertSame('Pending', OrderStatus::Pending->label());
        $this->assertSame('Processing', OrderStatus::Processing->label());
        $this->assertSame('Shipped', OrderStatus::Shipped->label());
        $this->assertSame('Delivered', OrderStatus::Delivered->label());
        $this->assertSame('Cancelled', OrderStatus::Cancelled->label());
    }

    public function testAllowedTransitions(): void
    {
        $pending = OrderStatus::Pending->allowedTransitions();
        $this->assertCount(2, $pending);
        $this->assertContains(OrderStatus::Processing, $pending);
        $this->assertContains(OrderStatus::Cancelled, $pending);

        $processing = OrderStatus::Processing->allowedTransitions();
        $this->assertCount(2, $processing);
        $this->assertContains(OrderStatus::Shipped, $processing);
        $this->assertContains(OrderStatus::Cancelled, $processing);

        $shipped = OrderStatus::Shipped->allowedTransitions();
        $this->assertCount(1, $shipped);
        $this->assertContains(OrderStatus::Delivered, $shipped);

        $delivered = OrderStatus::Delivered->allowedTransitions();
        $this->assertCount(0, $delivered);

        $cancelled = OrderStatus::Cancelled->allowedTransitions();
        $this->assertCount(0, $cancelled);
    }

    public function testCanTransitionTo(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Processing));
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Delivered));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::Pending));
        $this->assertFalse(OrderStatus::Cancelled->canTransitionTo(OrderStatus::Processing));
    }
}
