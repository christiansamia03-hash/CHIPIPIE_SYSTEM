<?php
$hash = '$2y$10$U1uvrE4Gvkk5MRnW6V3xl.NIbiMAROFm2kfLwnklrpmBNT0JrNAxK';
$words = ['admin123', 'Admin123', 'Admin123!', 'superadmin', 'superadmin123', 'Superadmin123', 'Superadmin123!', 'SuperAdmin123!', 'password', 'root', '12345678', 'ChipiPie123!', 'admin', 'SuperAdmin'];
foreach ($words as $w) {
    if (password_verify($w, $hash)) {
        echo "MATCH: $w\n";
    }
}
