<?php
define('BASE_PATH', 'C:/xampp/htdocs/landingflow');
define('STORAGE_PATH', BASE_PATH . '/storage');
require BASE_PATH . '/config/loader.php';
require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/core/Autoloader.php';
App\Core\Autoloader::register();
$db = App\Core\Database::getInstance()->getConnection();
$db->exec("
  CREATE TABLE IF NOT EXISTS receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    service_description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    receipt_date DATE NOT NULL,
    pdf_path VARCHAR(500) DEFAULT NULL,
    emailed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "Table 'receipts' created.\n";
echo "Next receipt number: " . (($db->query("SELECT MAX(CAST(SUBSTRING(receipt_number,4) AS UNSIGNED)) as m FROM receipts")->fetchColumn() ?: 0) + 1) . "\n";
