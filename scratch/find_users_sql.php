<?php
$f = file_get_contents('database_schema_and_data.sql');
$pos = strpos($f, "INSERT INTO `users`");
if ($pos !== false) {
    echo substr($f, $pos, 1000);
} else {
    echo "Not found";
}
