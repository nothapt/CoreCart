<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

/**
 * Abstract Model base class
 *
 * All models extend this class to get access to the database.
 * This keeps the pattern consistent: every model receives
 * the Database instance through constructor injection.
 *
 * Usage:
 *   class ProductModel extends Model { ... }
 *   $model = new ProductModel($db);
 */
abstract class Model
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }
}
