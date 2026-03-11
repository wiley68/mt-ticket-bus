<?php

declare(strict_types=1);

session_start();

// Малък helper за HTML escape
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Четене на конфигурация (вкл. админ паролата) от /home/avalonbg/configtickets.ini
$ini_path = '/home/avalonbg/configtickets.ini';
$ini = @parse_ini_file($ini_path, true, INI_SCANNER_TYPED);
if (!is_array($ini)) {
    echo 'Config error: cannot read ' . h($ini_path);
    exit;
}
$admin_password = '';
if (isset($ini['admin']) && is_array($ini['admin']) && isset($ini['admin']['password'])) {
    $admin_password = (string) $ini['admin']['password'];
}
if ($admin_password === '') {
    echo 'Config error: admin password is not configured in [admin] section.';
    exit;
}

// Обработка на logout – преди guard-а
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $_SESSION = array();
    if (session_id() !== '' || isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? false)
        );
    }
    session_destroy();
    header('Location: ' . strtok((string)($_SERVER['REQUEST_URI'] ?? ''), '?'));
    exit;
}

// Проста защита с парола (ползва паролата от ini файла)
if (!isset($_SESSION['pb_admin_logged_in']) || $_SESSION['pb_admin_logged_in'] !== true) {
    $error = '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['password'])) {
        $pass = (string) ($_POST['password'] ?? '');
        if ($pass === $admin_password) {
            $_SESSION['pb_admin_logged_in'] = true;
            header('Location: ' . strtok((string)($_SERVER['REQUEST_URI'] ?? ''), '?'));
            exit;
        }
        $error = 'Невалидна парола.';
    }
?>
    <!DOCTYPE html>
    <html lang="bg">

    <head>
        <meta charset="UTF-8">
        <title>Licenses Admin</title>
        <style>
            body {
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background: #f3f4f6;
                margin: 0;
                padding: 40px;
            }

            .box {
                max-width: 420px;
                margin: 0 auto;
                background: #fff;
                border-radius: 8px;
                padding: 24px;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
            }

            h1 {
                margin: 0 0 16px;
                font-size: 20px;
            }

            label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
            }

            input[type="password"] {
                width: 100%;
                padding: 8px 10px;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                font-size: 14px;
            }

            button {
                margin-top: 14px;
                padding: 8px 16px;
                border-radius: 4px;
                border: 1px solid #0f766e;
                background: #0f766e;
                color: #fff;
                cursor: pointer;
                font-size: 14px;
            }

            button:hover {
                background: #115e59;
            }

            .error {
                margin-top: 10px;
                color: #b91c1c;
                font-size: 13px;
            }
        </style>
    </head>

    <body>
        <div class="box">
            <h1>Licenses Admin</h1>
            <form method="post">
                <label for="password">Парола</label>
                <input type="password" id="password" name="password" autocomplete="off">
                <button type="submit">Влизане</button>
                <?php if ($error !== ''): ?>
                    <div class="error"><?php echo h($error); ?></div>
                <?php endif; ?>
            </form>
        </div>
    </body>

    </html>
<?php
    exit;
}

// Четене на DB конфигурация от вече заредения ini
if (empty($ini['database']) || !is_array($ini['database'])) {
    echo 'Config error: cannot read database section.';
    exit;
}

$db_user = isset($ini['database']['username']) ? (string) $ini['database']['username'] : '';
$db_pass = isset($ini['database']['password']) ? (string) $ini['database']['password'] : '';
$db_name = isset($ini['database']['dbname']) ? (string) $ini['database']['dbname'] : '';
if ($db_user === '' || $db_name === '') {
    echo 'Config error: missing DB credentials.';
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=' . $db_name . ';charset=utf8mb4',
        $db_user,
        $db_pass,
        array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        )
    );
} catch (Throwable $e) {
    echo 'DB connection failed.';
    exit;
}

$table = 'license_activations';
$message = '';
$error = '';

// Обработка на действия: нов лиценз / stop / reset / delete
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $new_key = trim((string) ($_POST['new_license_key'] ?? ''));
        $plan    = 'pro';
        if ($new_key === '') {
            $error = 'Моля въведи лицензен ключ.';
        } elseif (!preg_match('/^[A-Za-z0-9._\\-]{3,128}$/', $new_key)) {
            $error = 'Невалиден формат на лицензен ключ.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO {$table} (license_key_hash, domain_hash, site_url, plan, status, activated_at, last_check_at) VALUES (SHA2(?, 256), NULL, NULL, ?, 'active', NULL, NULL)");
                $stmt->execute(array($new_key, $plan));
                $message = 'Нов лиценз е създаден.';
            } catch (Throwable $e) {
                $error = 'Грешка при създаване на лиценз (възможно е да съществува вече).';
            }
        }
    } elseif ($action === 'stop') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE {$table} SET status = 'revoked' WHERE id = ?");
                $stmt->execute(array($id));
                $message = 'Лицензът е спрян.';
            } catch (Throwable $e) {
                $error = 'Грешка при спиране на лиценз.';
            }
        }
    } elseif ($action === 'reset') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE {$table} SET domain_hash = NULL, site_url = NULL, activated_at = NULL, last_check_at = NULL, status = 'active' WHERE id = ?");
                $stmt->execute(array($id));
                $message = 'Лицензът е ресетнат.';
            } catch (Throwable $e) {
                $error = 'Грешка при ресет на лиценз.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = ?");
                $stmt->execute(array($id));
                $message = 'Лицензът е изтрит.';
            } catch (Throwable $e) {
                $error = 'Грешка при изтриване на лиценз.';
            }
        }
    }
}

// Зареждане на списъка с лицензи
try {
    $stmt = $pdo->query("SELECT id, license_key_hash, domain_hash, site_url, plan, status, activated_at, last_check_at, created_at, updated_at FROM {$table} ORDER BY id DESC");
    $licenses = $stmt->fetchAll();
} catch (Throwable $e) {
    echo 'DB query failed.';
    exit;
}

?>
<!DOCTYPE html>
<html lang="bg">

<head>
    <meta charset="UTF-8">
    <title>Licenses Admin</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 32px;
        }

        h1 {
            margin: 0;
            font-size: 22px;
        }

        .wrap {
            max-width: 1000px;
            margin: 0 auto;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
            margin-bottom: 24px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        label {
            font-weight: 600;
            font-size: 14px;
        }

        input[type="text"] {
            padding: 6px 8px;
            border-radius: 4px;
            border: 1px solid #cbd5e1;
            font-size: 14px;
            min-width: 260px;
        }

        button {
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #0f766e;
            background: #0f766e;
            color: #fff;
            cursor: pointer;
            font-size: 13px;
        }

        button:hover {
            background: #115e59;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 8px 6px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .col-hash {
            max-width: 220px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-revoked {
            background: #fee2e2;
            color: #b91c1c;
        }

        .plan-pro {
            color: #1d4ed8;
            font-weight: 600;
        }

        .messages {
            margin-bottom: 10px;
            font-size: 13px;
        }

        .msg-ok {
            color: #166534;
        }

        .msg-err {
            color: #b91c1c;
        }

        form.inline {
            display: inline;
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="top-bar">
            <h1>Licenses Admin</h1>
            <form method="post">
                <input type="hidden" name="action" value="logout">
                <button type="submit">Изход</button>
            </form>
        </div>

        <div class="card">
            <?php if ($message !== '' || $error !== ''): ?>
                <div class="messages">
                    <?php if ($message !== ''): ?>
                        <div class="msg-ok"><?php echo h($message); ?></div>
                    <?php endif; ?>
                    <?php if ($error !== ''): ?>
                        <div class="msg-err"><?php echo h($error); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <label for="new_license_key">Нов лицензен ключ</label>
                    <input type="text" id="new_license_key" name="new_license_key" autocomplete="off" placeholder="напр. MTBUS-ABC123-XYZ789">
                    <button type="submit">Нов акаунт</button>
                </div>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Site URL</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Activated</th>
                        <th>Last check</th>
                        <th>Управление</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($licenses)): ?>
                        <tr>
                            <td colspan="9">Няма лицензи.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($licenses as $lic): ?>
                            <?php
                            $key_hash_full = (string) $lic['license_key_hash'];
                            $dom_hash_full = (string) $lic['domain_hash'];
                            ?>
                            <tr>
                                <td><?php echo (int) $lic['id']; ?></td>
                                <td><?php echo h((string) $lic['site_url']); ?></td>
                                <td><span class="plan-<?php echo h($lic['plan']); ?>"><?php echo h($lic['plan']); ?></span></td>
                                <td>
                                    <?php if ($lic['status'] === 'active'): ?>
                                        <span class="status-badge status-active">active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-revoked">revoked</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo h((string) $lic['activated_at']); ?></td>
                                <td><?php echo h((string) $lic['last_check_at']); ?></td>
                                <td>
                                    <form method="post" class="inline" onsubmit="return confirm('Спиране на този лиценз?');">
                                        <input type="hidden" name="action" value="stop">
                                        <input type="hidden" name="id" value="<?php echo (int) $lic['id']; ?>">
                                        <button type="submit">Спиране</button>
                                    </form>
                                    <form method="post" class="inline" onsubmit="return confirm('Ресет на домейна за този лиценз?');">
                                        <input type="hidden" name="action" value="reset">
                                        <input type="hidden" name="id" value="<?php echo (int) $lic['id']; ?>">
                                        <button type="submit">Reset</button>
                                    </form>
                                    <form method="post" class="inline" onsubmit="return confirm('Наистина ли искаш да изтриеш този лиценз?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $lic['id']; ?>">
                                        <button type="submit">Изтрий</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>