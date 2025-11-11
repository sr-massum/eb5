<?php
$error_code = isset($_GET['code']) ? $_GET['code'] : '404';
$error_file = "errors/{$error_code}.php";

if (file_exists($error_file)) {
    include $error_file;
} else {
    http_response_code(404);
    echo "Error page not found";
}
?>