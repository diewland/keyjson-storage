<?php
    // Allow requests from any origin (not recommended for sensitive data)
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    include './config.php';
    include './db.php';

    // craft response template
    $resp = [
      'success' => false,
      'message' => null,
    ];

    // [GET] read value
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $name = $_GET['name'] ?? '<NOTFOUND>';
        $cfg = $config[$name] ?? null;

        if ($cfg === null) {
          $resp['message'] = 'name not found';
        }
        else {
          $sql = "SELECT `value` FROM `storage` WHERE `name` = ? LIMIT 1;";
          $stmt = $conn->prepare($sql);
          $stmt->execute([ $name ]);
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($row === false) {
            $resp['message'] = 'data not found';
          }
          else {
            $v = $row['value'];
            $resp['data'] = ($v !== null) ? json_decode($v) : null;
            $resp['success'] = true;
          }
        }
    }

    // [POST] write value
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $value = $data['value'] ?? null;
        $secret = $data['secret'] ?? null;
        $name = $data['name'] ?? '<NOTFOUND>';
        $cfg = $config[$name] ?? null;
        $cfg_secret = ($cfg === null) ? null : $cfg['secret'];

        if ($cfg === null) {
          $resp['message'] = 'name not found';
        }
        else if (($value === null) || ($secret === null)) {
          $resp['message'] = 'invalid input';
        }
        else if ($secret != $cfg_secret) {
          $resp['message'] = 'invalid secret';
        }
        else {
            // find by name
            $stmt = $conn->prepare("SELECT `value` FROM `storage` WHERE `name` = ? LIMIT 1;");
            $stmt->execute([ $name ]);
            $row = $stmt->fetch();

            if ($row === false) { // add new value
              $stmt = $conn->prepare("INSERT INTO `storage` (`name`, `value`) VALUES (?, ?);");
              $stmt->execute([ $name, json_encode($value) ]);
            }
            else { // replace existing value
              $stmt = $conn->prepare("UPDATE `storage` SET `value` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `name` = ?;");
              $stmt->execute([ json_encode($value), $name ]);
            }
            // stamp success
            $resp['success'] = true;
        }
    }

    // return json
    header("Content-Type: application/json");
    echo json_encode($resp);
?>
