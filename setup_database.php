<?php
/**
 * Toolify — Database Setup Script
 * Run once: php setup_database.php
 * Creates tables and seeds initial data
 */

require_once __DIR__ . '/db_config.php';

echo "=== Toolify Database Setup ===\n\n";

$db = getDB();

// ─── Create Tables ────────────────────────────────────────────

echo "Creating tables...\n";

$db->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        price REAL NOT NULL,
        imgUrl TEXT NOT NULL,
        description TEXT NOT NULL,
        stock INTEGER DEFAULT 100,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NULL,
        customer_name TEXT NOT NULL,
        customer_phone TEXT NOT NULL,
        customer_address TEXT NOT NULL,
        total_amount REAL NOT NULL,
        status TEXT DEFAULT 'Pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
");


$db->exec("
    CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        price REAL NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

echo "  ✓ Tables created successfully.\n\n";

// ─── Seed Products ────────────────────────────────────────────

echo "Seeding products...\n";

$existingProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();

if ($existingProducts == 0) {
    $products = [
        [
            'name' => 'ProArc 200 Welding Machine',
            'category' => 'welding',
            'price' => 18999,
            'imgUrl' => '../assets/welding_machine.png',
            'description' => 'Heavy-duty inverter welder for professional use.'
        ],
        [
            'name' => 'Impact Drill Machine 13mm',
            'category' => 'power-tools',
            'price' => 4250,
            'imgUrl' => '../assets/impact_drill.png',
            'description' => 'Variable speed reversible impact drill for concrete and steel.'
        ],
        [
            'name' => 'Titan Angle Grinder 900W',
            'category' => 'power-tools',
            'price' => 3499,
            'imgUrl' => '../assets/angle_grinder.png',
            'description' => 'High-speed grinder for cutting and polishing metal.'
        ],
        [
            'name' => 'Auto-Darkening Helmet',
            'category' => 'safety',
            'price' => 1499,
            'imgUrl' => '../assets/welding_helmet.png',
            'description' => 'Solar-powered helmet with adjustable sensitivity.'
        ],
        [
            'name' => 'Diamond Cutting Discs (10pk)',
            'category' => 'cutting',
            'price' => 499,
            'imgUrl' => '../assets/cutting_discs.png',
            'description' => 'Durable blades for cutting stainless steel and iron.'
        ],
        [
            'name' => 'Forged Steel Hammer',
            'category' => 'power-tools',
            'price' => 650,
            'imgUrl' => 'https://images.unsplash.com/photo-1586864387967-d02ef85d93e8?auto=format&fit=crop&w=500&q=60',
            'description' => 'Ergonomic grip with shock reduction technology.'
        ],
        [
            'name' => 'Leather Welding Gloves',
            'category' => 'safety',
            'price' => 450,
            'imgUrl' => 'https://images.unsplash.com/photo-1616423640778-28d1b53229bd?auto=format&fit=crop&w=500&q=60',
            'description' => 'Heat resistant gauntlets for heavy welding.'
        ],
        [
            'name' => 'Adjustable Wrench Set',
            'category' => 'power-tools',
            'price' => 1800,
            'imgUrl' => 'https://images.unsplash.com/photo-1533090161767-32687720681f?auto=format&fit=crop&w=500&q=60',
            'description' => 'Chrome vanadium steel wrenches, sizes 6-12 inch.'
        ],
        [
            'name' => 'Welding Electrodes E6013',
            'category' => 'welding',
            'price' => 850,
            'imgUrl' => 'https://images.unsplash.com/photo-1581093583449-ed25213444e4?auto=format&fit=crop&w=500&q=60',
            'description' => 'General purpose rods (2.5mm) for mild steel welding.'
        ],
        [
            'name' => 'Circular Saw 1200W',
            'category' => 'power-tools',
            'price' => 6800,
            'imgUrl' => 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?auto=format&fit=crop&w=500&q=60',
            'description' => 'Precision cutting for wood and soft metals.'
        ]
    ];


    $stmt = $db->prepare("
        INSERT INTO products (name, category, price, imgUrl, description)
        VALUES (:name, :category, :price, :imgUrl, :description)
    ");

    foreach ($products as $product) {
        $stmt->execute($product);
        echo "  + {$product['name']}\n";
    }
    echo "  ✓ " . count($products) . " products seeded.\n\n";
} else {
    echo "  ⓘ Products already exist ({$existingProducts} found). Skipping seed.\n\n";
}

// ─── Seed Admin User ──────────────────────────────────────────

echo "Creating admin user...\n";

$existingAdmin = $db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();

if ($existingAdmin == 0) {
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO admin_users (username, password) VALUES (:username, :password)");
    $stmt->execute(['username' => 'admin', 'password' => $hashedPassword]);
    echo "  ✓ Admin user created (username: admin / password: admin123)\n\n";
} else {
    echo "  ⓘ Admin user already exists. Skipping.\n\n";
}

echo "=== Setup Complete! ===\n";
echo "Run the server with: php -S 127.0.0.1:8000\n";
echo "Then open: http://127.0.0.1:8000/templates/index.html\n";
echo "Admin panel: http://127.0.0.1:8000/admin/\n";
?>
