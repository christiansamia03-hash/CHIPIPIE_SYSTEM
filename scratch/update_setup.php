<?php
$f = 'c:/xampp/htdocs/CHIPIPIE_SYSTEM/api/setup.php';
$c = file_get_contents($f);
$c = str_replace(
    "\$pdo->exec(\"INSERT IGNORE INTO categories (name) VALUES ('Pies'), ('Drinks'), ('Snacks'), ('Add-ons')\");",
    "// Legacy seed categories removed",
    $c
);
file_put_contents($f, $c);
echo "Updated setup.php\n";
