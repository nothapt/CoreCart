<?php
declare(strict_types=1);

namespace CoreCart\Tests\Integration;

use CoreCart\System\Repository\CustomerRepository;
use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Repository\OrderRepository;
use CoreCart\System\Repository\CartRepository;
use CoreCart\System\Service\OrderService;
use CoreCart\System\Service\CartService;
use CoreCart\System\Dto\OrderCreateDTO;
use CoreCart\System\Dto\CartAddDTO;
use CoreCart\System\Infrastructure\OrderStatus;
use CoreCart\Tests\Helper\TestDatabase;
use PHPUnit\Framework\TestCase;

class OrderLifecycleTest extends TestCase
{
    private TestDatabase $db;
    private OrderService $orderService;
    private CartService $cartService;
    private ProductRepository $productRepo;

    protected function setUp(): void
    {
        $this->db = new TestDatabase();
        $this->productRepo = new ProductRepository($this->db);
        $orderRepo = new OrderRepository($this->db);
        $cartRepo = new CartRepository($this->db);
        $customerRepo = new CustomerRepository($this->db);
        $addressRepo = new \CoreCart\System\Repository\AddressRepository($this->db);
        $customerService = new \CoreCart\System\Service\CustomerService($customerRepo, $addressRepo);
        $this->cartService = new CartService($cartRepo, $this->productRepo, $customerService);
        $this->orderService = new OrderService($orderRepo);

        $this->seedProducts();
    }

    private function seedProducts(): void
    {
        $this->db->execute(
            "INSERT INTO cc_product (product_id, model, price, quantity, status) VALUES (1, 'SKU-001', '25.0000', 100, 1)"
        );
        $this->db->execute(
            "INSERT INTO cc_product_description (product_id, language_id, name, description) VALUES (1, 1, 'Test Product', 'A test product')"
        );
        $this->db->execute(
            "INSERT INTO cc_product (product_id, model, price, quantity, status) VALUES (2, 'SKU-002', '50.0000', 5, 1)"
        );
        $this->db->execute(
            "INSERT INTO cc_product_description (product_id, language_id, name, description) VALUES (2, 1, 'Low Stock Product', 'Limited stock')"
        );
    }

    private function createDto(): OrderCreateDTO
    {
        return new OrderCreateDTO(
            customerEmail: 'test@example.com',
            customerPhone: '+1234567890',
            shippingFirstname: 'John',
            shippingLastname: 'Doe',
            shippingAddress1: '123 Main St',
            shippingCity: 'New York',
            shippingPostcode: '10001',
            shippingCountry: 'US',
            shippingZone: 'NY',
        );
    }

    public function testGuestCheckoutDeductsStock(): void
    {
        $sessionId = 'test_session_001';

        $this->cartService->addItem($sessionId, new CartAddDTO(productId: 1, quantity: 3));

        $dto = $this->createDto();
        $orderId = $this->orderService->createOrder($sessionId, $dto);

        $this->assertGreaterThan(0, $orderId);

        $order = $this->orderService->getOrder($orderId);
        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::Pending->value, $order->status);

        $stock = $this->db->query("SELECT quantity FROM cc_product WHERE product_id = 1");
        $this->assertEquals(97, (int) $stock[0]['quantity']);
    }

    public function testInsufficientStockRejected(): void
    {
        $sessionId = 'test_session_002';

        $this->expectException(\RuntimeException::class);
        $this->cartService->addItem($sessionId, new CartAddDTO(productId: 2, quantity: 10));
    }

    public function testOrderStatusTransition(): void
    {
        $sessionId = 'test_session_003';
        $this->cartService->addItem($sessionId, new CartAddDTO(productId: 1, quantity: 1));

        $orderId = $this->orderService->createOrder($sessionId, $this->createDto());

        $result = $this->orderService->transitionStatus($orderId, OrderStatus::Processing, 'Confirmed');
        $this->assertTrue($result);

        $order = $this->orderService->getOrder($orderId);
        $this->assertEquals(OrderStatus::Processing->value, $order->status);
    }

    public function testInvalidStatusTransitionRejected(): void
    {
        $sessionId = 'test_session_004';
        $this->cartService->addItem($sessionId, new CartAddDTO(productId: 1, quantity: 1));

        $orderId = $this->orderService->createOrder($sessionId, $this->createDto());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot transition');
        $this->orderService->transitionStatus($orderId, OrderStatus::Delivered, 'Skip ahead');
    }

    public function testCancelOrderReturnsStock(): void
    {
        $sessionId = 'test_session_005';
        $this->cartService->addItem($sessionId, new CartAddDTO(productId: 1, quantity: 5));

        $orderId = $this->orderService->createOrder($sessionId, $this->createDto());

        $stockBefore = $this->db->query("SELECT quantity FROM cc_product WHERE product_id = 1");
        $this->assertEquals(95, (int) $stockBefore[0]['quantity']);

        $this->orderService->transitionStatus($orderId, OrderStatus::Cancelled, 'Customer cancelled');

        $stockAfter = $this->db->query("SELECT quantity FROM cc_product WHERE product_id = 1");
        $this->assertEquals(100, (int) $stockAfter[0]['quantity']);
    }

    public function testOrderHistoryRecorded(): void
    {
        $sessionId = 'test_session_006';
        $this->cartService->addItem($sessionId, new CartAddDTO(productId: 1, quantity: 1));

        $orderId = $this->orderService->createOrder($sessionId, $this->createDto());

        $this->orderService->transitionStatus($orderId, OrderStatus::Processing, 'Processing now');
        $this->orderService->transitionStatus($orderId, OrderStatus::Shipped, 'Shipped via FedEx');

        $history = $this->orderService->getHistory($orderId);
        $this->assertCount(2, $history);
        $this->assertEquals(OrderStatus::Shipped->value, (int) $history[0]['status']);
        $this->assertEquals('Shipped via FedEx', $history[0]['comment']);
        $this->assertEquals(OrderStatus::Processing->value, (int) $history[1]['status']);
    }

    public function testFullOrderLifecycle(): void
    {
        $sessionId = 'test_session_007';
        $this->cartService->addItem($sessionId, new CartAddDTO(productId: 1, quantity: 2));

        $orderId = $this->orderService->createOrder($sessionId, $this->createDto());

        $this->orderService->transitionStatus($orderId, OrderStatus::Processing);
        $this->orderService->transitionStatus($orderId, OrderStatus::Shipped);
        $this->orderService->transitionStatus($orderId, OrderStatus::Delivered);

        $order = $this->orderService->getOrder($orderId);
        $this->assertEquals(OrderStatus::Delivered->value, $order->status);

        $this->expectException(\RuntimeException::class);
        $this->orderService->transitionStatus($orderId, OrderStatus::Cancelled);
    }

    public function testCustomerOrdersRetrieval(): void
    {
        $sessionId = 'test_session_008';
        $this->cartService->addItem($sessionId, new CartAddDTO(productId: 1, quantity: 1));

        $dto = new OrderCreateDTO(
            customerId: 42,
            customerEmail: 'cust42@example.com',
            shippingFirstname: 'C',
            shippingLastname: 'U',
            shippingAddress1: '1 St',
            shippingCity: 'City',
            shippingPostcode: '11111',
            shippingCountry: 'US',
            shippingZone: 'ST',
        );

        $orderId = $this->orderService->createOrder($sessionId, $dto);
        $result = $this->orderService->getCustomerOrders(42);

        $this->assertCount(1, $result['orders']);
    }
}
