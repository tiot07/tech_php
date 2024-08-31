<?php
$file = fopen('data/data.txt', 'r'); // ファイルを開く

echo '<table border="1">'; // 表の開始と枠線の設定

// ヘッダー行を追加
echo '<tr>';
echo '<th>日時</th>';
echo '<th>名前</th>';
echo '<th>メールアドレス</th>';
echo '<th>ファイル名</th>';
echo '<th>質問内容</th>';
echo '<th>回答</th>';
echo '</tr>';

// ファイル内容を1行ずつ読み込んで出力
while (($columns = fgetcsv($file)) !== FALSE) {
    echo '<tr>'; // 表の行を開始
    foreach ($columns as $index => $column) {
        $column = htmlspecialchars($column);
        if ($index == 3) { // ファイル名の列
            echo '<td><a href="data/' . $column . '" target="_blank">' . $column . '</a></td>';
        } else {
            echo '<td>' . $column . '</td>'; // 各データをセルとして表示
        }
    }
    echo '</tr>'; // 表の行を終了
}

echo '</table>'; // 表の終了

fclose($file); // ファイルを閉じる
?>
