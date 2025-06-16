<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo '<script>window.location.href = "' . BASE_URL . 'auth/login.php";</script>';
    exit();
}
?>