<?php
if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "' . BASE_URL . 'auth/login.php";</script>';
    exit();
}
?>