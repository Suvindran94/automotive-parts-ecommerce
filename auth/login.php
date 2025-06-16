<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $db = new Database();
    $db->query("SELECT * FROM users WHERE username = :username OR email = :username");
    $db->bind(':username', $username);
    $user = $db->single();

    if ($user && password_verify($password, $user->password)) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['email'] = $user->email;
        $_SESSION['role'] = $user->role;
        $_SESSION['full_name'] = $user->full_name;

        echo '<script>
            Swal.fire({
                title: "Login Successful",
                text: "You are now logged in.",
                icon: "success",
                confirmButtonText: "Continue"
            }).then(() => {
                window.location.href = "' . ($user->role === 'admin' ? BASE_URL . 'admin/dashboard.php' : BASE_URL) . '";
            });
        </script>';
        exit();
    } else {
        $error = "Invalid username or password";
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Login</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form id="loginForm" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                <div class="mt-3 text-center">
                    <p>Don't have an account? <a href="<?php echo BASE_URL; ?>auth/register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>