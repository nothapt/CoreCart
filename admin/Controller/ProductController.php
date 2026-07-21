<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

/**
 * Admin Product Controller
 *
 * Handles listing, creating, editing, and deleting products.
 * This is a demo controller to show how the admin panel works.
 */
class ProductController
{
    /**
     * Show the product list page.
     */
    public function index(): void
    {
        // TODO: Fetch products from database
        // $db = new \CoreCart\System\Engine\Database();
        // $products = $db->query("SELECT * FROM product ORDER BY product_id DESC");

        $products = [
            [
                'product_id' => 1,
                'name' => 'CorePhone 15 Pro',
                'model' => 'CP-15-PRO',
                'price' => '999.00',
                'quantity' => 25,
                'status' => 1,
            ],
            [
                'product_id' => 2,
                'name' => 'CoreBook Laptop 14"',
                'model' => 'CB-14',
                'price' => '1299.00',
                'quantity' => 12,
                'status' => 1,
            ],
        ];

        include DIR_ADMIN . '/View/product/list.php';
    }
}
