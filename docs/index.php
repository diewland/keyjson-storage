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

    // [POST] write value TODO update logic
    else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = $_POST['code'] ?? null;
        $score = $_POST['score'] ?? null;
        $secret = $_POST['secret'] ?? null;
        $stime = $_POST['screen_time'] ?? 0;

        $board = $_POST['board'] ?? '<NOTFOUND>';
        $cfg = $config[$board] ?? null;
        $board_secret = ($cfg === null) ? null : $cfg['secret'];

        if ($cfg === null) {
          $resp['message'] = 'board not found';
        }
        else if (($code === null) || ($score === null) || ($secret === null)) {
          $resp['message'] = 'invalid input';
        }
        else if ($secret != $board_secret) {
          $resp['message'] = 'invalid secret';
        }
        else {
            // find code
            $stmt = $conn->prepare("SELECT `plays`, `score`, `hi_score`, `screen_time` FROM `scores` WHERE `board` = ? AND `code` = ? LIMIT 1;");
            $stmt->execute([ $board, $code ]);
            $row = $stmt->fetch();

            if ($row === false) { // add new code
              $stmt = $conn->prepare("INSERT INTO `scores` (`board`, `code`, `plays`, `score`, `hi_score`, `screen_time`) VALUES (?, ?, ?, ?, ?, ?);");
              $stmt->execute([ $board, $code, 1, $score, $score, $stime ]);
            }
            else { // update existing code
              $plays = $row['plays'] + 1;
              $new_score = $row['score'] + $score;
              $hi_score = $score > $row['hi_score'] ? $score : $row['hi_score'];
              $new_stime = $row['screen_time'] + $stime;
              $stmt = $conn->prepare("UPDATE `scores` SET `plays` = ?, `score` = ?, `hi_score` = ?, `screen_time` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE `board` = ? AND `code` = ?;");
              $stmt->execute([ $plays, $new_score, $hi_score, $new_stime, $board, $code ]);
            }
            // stamp success
            $resp['success'] = true;
        }
    }

    // return json
    header("Content-Type: application/json");
    echo json_encode($resp);
?>
