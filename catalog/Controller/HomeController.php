<?php
declare(strict_types=1);

namespace CoreCart\Catalog\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Database;
use CoreCart\Catalog\Model\ProductModel;

/**
 * Frontend Home Controller
 *
 * Uses DI Container to get Database, then ProductModel.
 * Returns JSON for now (GUI comes later).
 */
class HomeController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index(): void
    {
        $db = $this->container->get(Database::class);
        $productModel = new ProductModel($db);

        $products = $productModel->getProducts(['limit' => 5]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'success',
            'engine'  => 'CoreCart MVP',
            'data'    => $products,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
