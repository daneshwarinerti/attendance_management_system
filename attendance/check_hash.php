<?php
$password = '12345';
$hash = '$2y$10$uKNXb0a3DwDcxvj6u9OiSOpwS35wHEzOAjYOEsyZ9i5apcUquTbVO'; // hash used for most students

if (password_verify($password, $hash)) {
    echo "Password is valid!";
} else {
    echo "Password failed!";
}
?>
