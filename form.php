<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDFからテキストを抽出して質問する</title>
    <!-- PDF.js のライブラリを読み込む -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 20px;
            color: #333;
        }
        h3 {
            color: #007BFF;
            margin-bottom: 15px;
        }
        form {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            background-color: #fdfdfd;
        }
        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        pre {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 5px;
            font-size: 14px;
            overflow-x: auto;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3>PDFファイルからテキストを抽出して質問する</h3>
        <form id="pdfForm" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="pdfInput">PDFファイル:</label>
                <input type="file" id="pdfInput" name="pdf_file">
            </div>
            <button type="button" onclick="extractText()">テキストを抽出</button>
        </form>

        <h3>抽出されたテキスト:</h3>
        <pre id="output"></pre>

        <h3>質問を入力してください:</h3>
        <form id="questionForm">
            <div class="form-group">
                <label for="nameInput">名前:</label>
                <input type="text" id="nameInput" value="デフォルト名">
            </div>
            <div class="form-group">
                <label for="emailInput">メールアドレス:</label>
                <input type="email" id="emailInput" value="example@example.com">
            </div>
            <div class="form-group">
                <label for="questionInput">質問内容:</label>
                <input type="text" id="questionInput" value="このテキストを要約して">
            </div>
            <button type="button" onclick="askQuestion()">質問を送信</button>
        </form>

        <h3>AIからの回答:</h3>
        <pre id="answerOutput"></pre>

        <form id="redirectForm" action="read.php" method="post">
            <input type="hidden" id="newEntry" name="new_entry">
            <button type="submit">Readページに移動</button>
        </form>
    </div>

    <script>
        function htmlspecialchars(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
        let extractedText = '';
        let fileName = '';

        async function extractText() {
            const input = document.getElementById('pdfInput');
            if (input.files.length === 0) {
                alert('PDFファイルを選択してください');
                return;
            }

            const file = input.files[0];
            fileName = file.name; // ファイル名を取得
            const arrayBuffer = await file.arrayBuffer();

            const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            let fullText = '';

            for (let i = 0; i < pdf.numPages; i++) {
                const page = await pdf.getPage(i + 1);
                const textContent = await page.getTextContent();

                textContent.items.forEach(item => {
                    fullText += item.str + ' ';
                });
                fullText += '\n';
            }

            extractedText = fullText;
            document.getElementById('output').innerText = fullText;
        }

        async function askQuestion() {
            const name = document.getElementById('nameInput').value;
            const email = document.getElementById('emailInput').value;
            const question = document.getElementById('questionInput').value;

            if (name === '' || email === '' || question === '') {
                alert('全ての項目を入力してください');
                return;
            }

            // AIセッションを作成し、システムプロンプトを設定する
            const session = await ai.assistant.create({
                systemPrompt: `あなたはユーザからの質問に答えるエージェントです。以下のテキストについて質問があります: ${extractedText}`
            });

            // 質問をAIに送信して応答を取得する
            const answer = await session.prompt(question);
            document.getElementById('answerOutput').innerText = answer;

            // 現在の日時を日本時間で取得（秒まで）
            const now = new Date().toLocaleString('ja-JP', { timeZone: 'Asia/Tokyo' });

            // 特殊文字をエスケープ
            const escapedQuestion = htmlspecialchars(question).replace(/\n/g, "\\n");
            let escapedAnswer = htmlspecialchars(answer).replace(/\n/g, "\\n");

            // カンマを含むフィールドをダブルクォーテーションで囲む
            if (escapedAnswer.includes(',')) {
                escapedAnswer = `"${escapedAnswer}"`;
            }

            // 抽出テキストを削除したエントリを作成
            const entry = `${now},${name},${email},${fileName},${escapedQuestion},${escapedAnswer}\n`;

            // ファイルに書き込むためにサーバー側にPOSTリクエストを送信
            const formData = new FormData();
            formData.append('pdf_file', document.getElementById('pdfInput').files[0]);
            formData.append('entry', entry);

            fetch('save_data.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (!response.ok) {
                    alert("エラーが発生しました。");
                } else {
                    alert("データが正常に保存されました。");
                }
            });

            // hidden inputにデータをセットしてread.phpに遷移
            document.getElementById('newEntry').value = entry;
        }
    </script>
</body>
</html>
