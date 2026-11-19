<?php
require __DIR__ . '/../includes/app.php';

$register = $register ?? false;
$error = '';
$email = '';
$username = '';
$next = input($_GET, 'next') === 'admin';
if ($signedIn = currentUser()) {
    redirect($next && isAdmin($signedIn) ? '/views/admin' : '/views/profile');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyPost();
    $email = input($_POST, 'email');
    $username = input($_POST, 'username');
    $password = $_POST['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || !is_string($password) || $password === '' || strlen($password) > 72 || str_contains($password, "\0")) {
        $error = 'Enter a valid email and password.';
    } elseif ($register) {
        if ($username === '' || mb_strlen($username) > 50 || strlen($password) < 8) {
            $error = 'Enter a username up to 50 characters and a password of 8–72 bytes.';
        } else {
            $locked = (int) query("SELECT GET_LOCK('mma_organization_register', 5)")->fetchColumn() === 1;
            if (!$locked) {
                $error = 'Registration is busy. Try again shortly.';
            } else {
                try {
                    if (query('SELECT user_id FROM users WHERE email = ?', [$email])->fetch()) {
                        $error = 'That email address already has an account.';
                    } else {
                        query('INSERT INTO users (username, email, password) VALUES (?, ?, ?)', [$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
                        $_SESSION['notice'] = 'Account created. Sign in to continue.';
                    }
                } catch (PDOException $exception) {
                    error_log($exception->getMessage());
                    $error = 'Your account could not be created. Check the details and try again.';
                } finally {
                    query("SELECT RELEASE_LOCK('mma_organization_register')");
                }
                if (!$error) {
                    redirect('/routes/login');
                }
            }
        }
    } elseif (loginThrottle('check')) {
        $error = 'Too many failed sign-in attempts. Wait 15 minutes and try again.';
    } else {
        $account = authenticateAccount($email, $password);
        if ($account) {
            loginThrottle('reset');
            session_regenerate_id(true);
            $_SESSION = ['user_id' => $account['user_id']];
            redirect($next && isAdmin($account) ? '/views/admin' : '/');
        }
        loginThrottle('fail');
        $error = 'Email or password is incorrect.';
    }
}
$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);
$title = $register ? 'Create an account' : 'Sign in';
pageStart($title, $register ? 'Register' : 'Account');
?>
<main class="shell section auth-shell">
    <div class="panel auth-panel">
        <h1><?= h($title) ?></h1>
        <?php if ($error): ?><p class="alert error" role="alert"><?= h($error) ?></p><?php endif; ?>
        <?php if ($notice): ?><p class="alert" role="status"><?= h($notice) ?></p><?php endif; ?>
        <form method="post" class="stack">
            <?php csrfField(); ?>
            <?php if ($register): ?><label>Username<input name="username" value="<?= h($username) ?>" maxlength="50" autocomplete="username" required></label><?php endif; ?>
            <label>Email<input type="email" name="email" value="<?= h($email) ?>" maxlength="100" autocomplete="email" required></label>
            <label>Password<input type="password" name="password" autocomplete="<?= $register ? 'new-password' : 'current-password' ?>" <?= $register ? 'minlength="8" maxlength="72"' : '' ?> required></label>
            <button class="button button-primary"><?= h($title) ?></button>
        </form>
        <p><a class="text-link" href="<?= url($register ? '/routes/login' : '/views/register') ?>"><?= $register ? 'Already have an account? Sign in' : 'Create an account' ?></a></p>
    </div>
</main>
<?php pageEnd(); ?>
