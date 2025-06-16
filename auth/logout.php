<?php
require_once __DIR__ . '/../config/constants.php';

session_start();
session_unset();
session_destroy();

echo '<script>
    Swal.fire({
        title: "Logged Out",
        text: "You have been successfully logged out.",
        icon: "success",
        confirmButtonText: "Continue"
    }).then(() => {
        window.location.href = "' . BASE_URL . '";
    });
</script>';
exit();
?>