<?php
require_once 'php/config.php';

$menuItems = [];
$dbError = null;
try {
    $stmt = $pdo->query("SELECT id, name, price, image_url, options_text FROM menu_items WHERE is_active = 1 ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $options = array_filter(array_map('trim', explode('|', $row['options_text'] ?? 'Regular')));
        $menuItems[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'image_url' => $row['image_url'],
            'options' => array_values($options),
        ];
    }
} catch (Throwable $e) {
    $dbError = 'DB connection failed';
    // JSON fallback if requested
    $wantJson = (isset($_GET['format']) && $_GET['format'] === 'json') ||
                (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    if ($wantJson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $dbError]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodies - Order Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body { background-color: #f8f9fa; }
        .menu-item-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Foodies</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Navigation items will be dynamically inserted here by app.js -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="btn btn-outline-danger btn-sm" href="admin/register_admin.php">Register Admin</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <div class="row">
            <?php if (empty($menuItems)): ?>
                <p class="text-center text-muted">No menu items are available at the moment. Please check back later.</p>
            <?php else: ?>
                <?php foreach ($menuItems as $item): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <!-- Image loaded from database 'image_url' field -->
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>" style="height: 200px; object-fit: cover;">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="card-text fw-bold">Rs. <?php echo number_format($item['price'], 2); ?></p>
                                
                                <div class="mt-auto">
                                    <div class="mb-2">
                                        <label for="options-<?php echo $item['id']; ?>" class="form-label small">Choose Option:</label>
                                        <select id="options-<?php echo $item['id']; ?>" class="form-select form-select-sm">
                                            <?php foreach ($item['options'] as $option): ?>
                                                <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button 
                                        class="btn btn-primary w-100 add-to-cart"
                                        data-id="<?php echo $item['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                        data-price="<?php echo $item['price']; ?>"
                                        data-options-id="options-<?php echo $item['id']; ?>">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="app.js"></script>
</body>
</html>