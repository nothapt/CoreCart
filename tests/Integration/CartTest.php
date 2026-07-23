<?php
declare(strict_types=1);

namespace CoreCart\Tests\Integration;

use CoreCart\System\Repository\ProductRepository;
use CoreCart\System\Repository\CartRepository;
use CoreCart\System\Service\CartService;
use CoreCart\System\Service\CustomerService;
use CoreCart\System\Dto\CartAddDTO;
use CoreCart\Tests\Helper\TestDatabase;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    private TestDatabase $db;
    private CartService $cartService;

    protected function setUp(): void
    {
        $this->db = new TestDatabase();
        $productRepo = new ProductRepository($this->db);
        $cartRepo = new CartRepository($this->db);
        $customerRepo = new \CoreCart\System\Repository\CustomerRepository($this->db);
        $addressRepo = new \CoreCart\System\Repository\AddressRepository($this->db);
        $customerService = new CustomerService($customerRepo, $addressRepo);
        $this->cartService = new CartService($cartRepo, $productRepo, $customerService);

        $this->db->execute(
            "INSERT INTO cc_product (product_id, model, price, quantity, status) VALUES (1, 'A', '10.0000', 50, 1)"
        );
        $this->db->execute(
            "INSERT INTO cc_product_description (product_id, language_id, name, description) VALUES (1, 1, 'Widget', '')"
        );
        $this->db->execute(
            "INSERT INTO cc_product (product_id, model, price, quantity, status) VALUES (2, 'B', '20.0000', 30, 1)"
        );
        $this->db->execute(
            "INSERT INTO cc_product_description (product_id, language_id, name, description) VALUES (2, 1, 'Gadget', '')"
        );
    }

    public function testAddItemToGuestCart(): void
    {
        $this->cartService->addItem('sess1', new CartAddDTO(productId: 1, quantity: 2));

        $cart = $this->cartService->getCart('sess1');
        $this->assertCount(1, $cart['items']);
        $this->assertEquals('20.0000', $cart['total']);
        $this->assertEquals(2, $cart['count']);
    }

    public function testAddMultipleProducts(): void
    {
        $this->cartService->addItem('sess2', new CartAddDTO(productId: 1, quantity: 1));
        $this->cartService->addItem('sess2', new CartAddDTO(productId: 2, quantity: 3));

        $cart = $this->cartService->getCart('sess2');
        $this->assertCount(2, $cart['items']);
        $this->assertEquals('70.0000', $cart['total']);
        $this->assertEquals(4, $cart['count']);
    }

    public function testRemoveItem(): void
    {
        $this->cartService->addItem('sess3', new CartAddDTO(productId: 1, quantity: 2));
        $this->cartService->addItem('sess3', new CartAddDTO(productId: 2, quantity: 1));

        $this->cartService->removeItem(1, 'sess3');

        $cart = $this->cartService->getCart('sess3');
        $this->assertCount(1, $cart['items']);
    }

    public function testClearCart(): void
    {
        $this->cartService->addItem('sess4', new CartAddDTO(productId: 1, quantity: 1));
        $this->cartService->addItem('sess4', new CartAddDTO(productId: 2, quantity: 1));

        $this->cartService->clearCart('sess4');

        $cart = $this->cartService->getCart('sess4');
        $this->assertEmpty($cart['items']);
    }

    public function testQuantityExceedsStockRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->cartService->addItem('sess5', new CartAddDTO(productId: 1, quantity: 100));
    }

    public function testGuestCartSeparation(): void
    {
        $this->cartService->addItem('sess_a', new CartAddDTO(productId: 1, quantity: 1));
        $this->cartService->addItem('sess_b', new CartAddDTO(productId: 2, quantity: 2));

        $cartA = $this->cartService->getCart('sess_a');
        $cartB = $this->cartService->getCart('sess_b');

        $this->assertCount(1, $cartA['items']);
        $this->assertCount(1, $cartB['items']);
    }
}
