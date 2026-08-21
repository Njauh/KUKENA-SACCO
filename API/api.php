<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/db.php";

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'status') {
    respond(["success" => true, "message" => "Kukena SACCO database connection is working."]);
}

if ($method !== 'POST') {
    respond(["success" => false, "message" => "POST request required."], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(["success" => false, "message" => "Invalid JSON request."], 400);
}

$action = $input['action'] ?? '';

try {
    if ($action === 'register') {
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if ($name === '' || $phone === '' || $email === '' || $password === '') {
            respond(["success" => false, "message" => "All registration fields are required."], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(["success" => false, "message" => "Enter a valid email address."], 422);
        }
        if (strlen($password) < 6) {
            respond(["success" => false, "message" => "Password must contain at least 6 characters."], 422);
        }

        $check = $conn->prepare("SELECT accounts_id FROM accounts WHERE email = ? OR phone = ? LIMIT 1");
        $check->execute([$email, $phone]);
        if ($check->fetch()) {
            respond(["success" => false, "message" => "An account with that email or phone already exists."], 409);
        }

        $conn->beginTransaction();

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $account = $conn->prepare("INSERT INTO accounts (full_name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, 'Active')");
        $account->execute([$name, $email, $phone, $hash]);
        $accountId = (int)$conn->lastInsertId();

        // national_id is required by the supplied SQL schema, so use a temporary unique value.
        // Add a National ID field to the form later if you want to collect the real value.
        $nationalId = 'PENDING-' . $accountId;
        $customer = $conn->prepare("INSERT INTO customer (account_id, national_id, full_name, phone, email, status) VALUES (?, ?, ?, ?, ?, 'Active')");
        $customer->execute([$accountId, $nationalId, $name, $phone, $email]);

        $conn->commit();

        respond([
            "success" => true,
            "message" => "Account registered successfully.",
            "user" => [
                "account_id" => $accountId,
                "name" => $name,
                "phone" => $phone,
                "email" => $email
            ]
        ]);
    }

    if ($action === 'login') {
        $identifier = trim($input['identifier'] ?? '');
        $password = $input['password'] ?? '';

        if ($identifier === '' || $password === '') {
            respond(["success" => false, "message" => "Enter your email/phone and password."], 422);
        }

        $stmt = $conn->prepare("SELECT accounts_id, full_name, email, phone, password_hash, status FROM accounts WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $account = $stmt->fetch();

        if (!$account || $account['status'] !== 'Active' || !password_verify($password, $account['password_hash'])) {
            respond(["success" => false, "message" => "Invalid login details or inactive account."], 401);
        }

        respond([
            "success" => true,
            "message" => "Login successful.",
            "user" => [
                "account_id" => (int)$account['accounts_id'],
                "name" => $account['full_name'],
                "phone" => $account['phone'],
                "email" => $account['email']
            ]
        ]);
    }

    respond(["success" => false, "message" => "Unknown API action."], 400);

} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log($e->getMessage());
    respond(["success" => false, "message" => "A server/database error occurred."], 500);
}
?>
