<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - CoreCart Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; margin: 0; }
        .sidebar {
            background-color: #272d3b;
            min-height: 100vh;
            color: #fff;
            padding-top: 20px;
        }
        .sidebar a { color: #a6b1cf; text-decoration: none; display: block; padding: 10px 20px; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: rgba(255,255,255,0.08); }
        .sidebar h5 { padding: 0 20px 15px; color: #fff; font-weight: 700; }
        .topbar { background: #fff; padding: 12px 24px; border-bottom: 1px solid #e3e6f0; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .badge-status-on { background-color: #198754; }
        .badge-status-off { background-color: #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h5><i class="fa-solid fa-bolt"></i> CoreCart</h5>
                <a href="#"><i class="fa-solid fa-gauge me-2"></i> Dashboard</a>
                <a href="#" class="active"><i class="fa-solid fa-box me-2"></i> Products</a>
                <a href="#"><i class="fa-solid fa-tags me-2"></i> Categories</a>
                <a href="#"><i class="fa-solid fa-cart-shopping me-2"></i> Orders</a>
                <a href="#"><i class="fa-solid fa-users me-2"></i> Customers</a>
                <a href="#"><i class="fa-solid fa-puzzle-piece me-2"></i> Extensions</a>
                <a href="#"><i class="fa-solid fa-gear me-2"></i> Settings</a>
            </div>

            <!-- Main content -->
            <div class="col-md-10">
                <!-- Top bar -->
                <div class="topbar d-flex justify-content-between align-items-center">
                    <span class="text-muted">Admin / Products</span>
                    <span><i class="fa-solid fa-user-circle me-1"></i> Administrator</span>
                </div>

                <div class="p-4">
                    <!-- Page header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">Product List</h4>
                        <button class="btn btn-primary">
                            <i class="fa-solid fa-plus me-1"></i> Add Product
                        </button>
                    </div>

                    <!-- Products table -->
                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Model</th>
                                        <th width="100">Price</th>
                                        <th width="80">Qty</th>
                                        <th width="100">Status</th>
                                        <th width="120">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= (int) $product['product_id'] ?></td>
                                        <td><i class="fa-regular fa-image fa-2x text-muted"></i></td>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><code><?= htmlspecialchars($product['model']) ?></code></td>
                                        <td>$<?= number_format((float) $product['price'], 2) ?></td>
                                        <td><?= (int) $product['quantity'] ?></td>
                                        <td>
                                            <?php if ($product['status']): ?>
                                                <span class="badge badge-status-on">Enabled</span>
                                            <?php else: ?>
                                                <span class="badge badge-status-off">Disabled</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No products found.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
