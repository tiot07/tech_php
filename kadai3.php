<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDFからテキストを抽出して質問する</title>
    <!-- PDF.js のライブラリを読み込む -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
</head>
<body>
    <h3>PDFファイルからテキストを抽出して質問する</h3>
    <form id="pdfForm" method="post" enctype="multipart/form-data">
        PDFファイル: <input type="file" id="pdfInput" name="pdf_file"><br><br>
        <button type="button" onclick="extractText()">テキストを抽出</button>
    </form>

    <h3>抽出されたテキスト:</h3>
    <pre id="output"></pre>

    <h3>質問を入力してください:</h3>
    名前: <input type="text" id="nameInput"><br><br>
    メールアドレス: <input type="text" id="emailInput"><br><br>
    質問内容: <input type="text" id="questionInput"><br><br>
    <button type="button" onclick="askQuestion()">質問を送信</button>

    <h3>AIからの回答:</h3>
    <pre id="answerOutput"></pre>

    <form id="redirectForm" action="read.php" method="post">
        <input type="hidden" id="newEntry" name="new_entry">
        <button type="submit">Readページに移動</button>
    </form>

    <script>
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

            // 特殊文字をエスケープ（改行を含む）
            const escapedQuestion = htmlspecialchars(question).replace(/\n/g, "\\n").replace(/,/g, "\\,");
            const escapedAnswer = htmlspecialchars(answer).replace(/\n/g, "\\n").replace(/,/g, "\\,");

            // 質問と回答をCSV形式で保存するためにエントリを作成
            const entry = `${now},${name},${email},${escapedQuestion},${escapedAnswer},${fileName},${extractedText.replace(/\n/g, "\\n").replace(/,/g, "\\,")}\n`;

            // ファイルに書き込むためにサーバー側にPOSTリクエストを送信
            fetch('save_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ entry: entry })
            });

            // hidden inputにデータをセットしてread.phpに遷移
            document.getElementById('newEntry').value = entry;
        }

        function htmlspecialchars(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
</body>
</html>
