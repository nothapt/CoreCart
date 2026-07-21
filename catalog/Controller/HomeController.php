<?php
declare(strict_types=1);

namespace CoreCart\Catalog\Controller;

use CoreCart\System\Engine\Database;
use CoreCart\Catalog\Model\ProductModel;

/**
 * Frontend Home Controller
 *
 * Handles the storefront landing page.
 * Returns JSON for now (GUI comes later).
 */
class HomeController
{
    public function index(): void
    {
        $db = new Database();
        $productModel = new ProductModel($db);

        $products = $productModel->getProducts(['limit' => 5]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'success',
            'message' => 'Welcome to CoreCart!',
            'products' => $products,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
