<?php
declare(strict_types=1);

namespace CoreCart\Admin\Controller;

use CoreCart\System\Engine\Container;
use CoreCart\System\Engine\Database;
use CoreCart\Catalog\Model\ProductModel;

/**
 * Admin Product Controller
 *
 * Uses DI Container to access Database and models.
 */
class ProductController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Show the product list page.
     */
    public function index(): void
    {
        $db = $this->container->get(Database::class);
        $productModel = new ProductModel($db);

        $products = $productModel->getProducts(['limit' => 20]);

        include DIR_ADMIN . '/View/product/list.php';
    }
}
