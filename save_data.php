<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['entry'])) {
        // CSV形式で保存
        file_put_contents('data/data.txt', $data['entry'], FILE_APPEND);
    }
}
?>
