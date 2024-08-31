<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PDFファイルの保存処理
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        // PDFファイルをdataフォルダに保存
        $pdfFileName = basename($_FILES['pdf_file']['name']);
        $pdfFilePath = 'data/' . $pdfFileName;
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdfFilePath)) {
            // POSTリクエストからentryデータを取得
            if (!empty($_POST['entry'])) {
                $entry = rtrim($_POST['entry'], "\n") ;

                // data.txt に保存
                file_put_contents('data/data.txt', $entry, FILE_APPEND);
            } else {
                echo "エントリデータが見つかりませんでした。";
            }
        } else {
            echo "PDFファイルの保存に失敗しました。";
        }
    } else {
        echo "PDFファイルのアップロードに失敗しました。";
    }
}
?>
