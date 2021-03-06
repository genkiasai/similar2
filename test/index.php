<?php
    session_start();
    require ("./common.php");
    ini_set ("display_errors", 0);


    //////////////
    // 比較処理1 //
    //////////////
    if (!empty($_POST["check"])) {
        $text1 = $_POST["check1"];
        $text2 = $_POST["check2"];
        similar_text($text1, $text2, $perc);
    }

    //////////////
    // 比較処理2 //
    //////////////
    if (isset($_POST["check_sub"])) {
        $text3 = preg_replace("#[ \n\r\t]+#um", "", $_POST["check3"]);
        $text4 = preg_replace("#[ \n\r\t]+#um", "", $_POST["check4"]);
        $n3 = get_ngram ($text3, $_POST["ngram"], $substr3);
        $n4 = get_ngram ($text4, $_POST["ngram"], $substr4);
        // 2つのN-gramの配列で同じ要素の物の数
        $cnt = count (array_intersect($substr3, $substr4));
        $perc_sub = ($cnt / $n4)*100;
    }

    /////////////
    // 変換処理 //
    /////////////
    if (!empty($_POST["conversion"])) {
        $text5 = $_POST["check5"];
        // スラッシュで囲わないと上手く動かなかった →　囲うのはスラッシュでなくても良くて、囲う文字のことをデリミタという
        // 角かっこ[]で囲ったものをマッチさせる
        // +記号は連続文字を一つのまとまりとして扱う
        // uはUTF-8として扱う
        // mは改行文字ごとに行頭と行末を判断する。mをつけなければ改行されている文字列があっても先頭文字と末端文字でしか行頭と行末を判断してくれない
        $text6 = preg_replace("#[ \n\t\r]+#um", "", $text5);

        $len = mb_strlen($text6);
        $n = 3;
        if ($len <=0 || $len < $n) return false;
        for ($i = 0; $i < $len; $i++) {
            $substr = mb_substr($text6, $i, $n);
            if (mb_strlen($substr) >= $n) {
                $comp_array[$i] = $substr;
            }
        }
    }

    ////////////////////
    // フォルダ読み込み //
    ///////////////////
    // 指定されたフォルダの.pdfファイルのパスを[dirname][tmp_name]で抽出
    // 抽出したファイルを1つずつ見ていく
    // for $i = 0; $i < count($txt_contents); $i++
    // 0.特定のフォルダ(./pdftotext_escape)に.pdfファイルを[dirname][tmp_name]でコピーさせる
    // 1.「pdftotextのパス -enc Shift-JIS ./pdftotext_escape/.pdfのファイル名」でコマンドをたたいて.txtファイルを作成
    // 2.作成した.txtファイルのテキストを配列($contents)に格納
    // 3.作成した.txtファイルを削除
    // 4.
    // 5.

    // 指定されたフォルダの.pdfファイルのパスを[dirname][tmp_name]で抽出
    $pdf_cnt = 0;
    $pdf_name[] = "";
    $pdf_tmp_name[] = "";
    if (isset($_FILES["dirname"]["tmp_name"])) {
        for ($i = 0; $i < count($_FILES["dirname"]["tmp_name"]); $i++) {
            if (substr($_FILES["dirname"]["name"][$i], -4, 4) === ".pdf") {
                $pdf_name[$pdf_cnt] = $_FILES["dirname"]["name"][$i];
                $pdf_tmp_name[$pdf_cnt] = $_FILES["dirname"]["tmp_name"][$i];
                $pdf_cnt++;
            }
        }

        if ($pdf_cnt > 0) {
            for ($i = 0; $i < count($pdf_tmp_name); $i++) {
                // 0.特定のフォルダ(./pdftotext_escape)に.pdfファイルを[dirname][tmp_name]でコピーさせる
                move_uploaded_file($pdf_tmp_name[$i], "./pdftotext_escape/" . $pdf_name[$i]);
                // 1.「pdftotextのパス -enc Shift-JIS ./pdftotext_escape/.pdfのファイル名」でコマンドをたたいて.txtファイルを作成
                // index.php(このファイル)と同階層にxpdf-tools-win-4.03を置く
                // Macはスラッシュ？
                // $cmd = __DIR__ . "/xpdf-tools-win-4.03/bin64/pdftotext -enc Shift-JIS " . __DIR__ . "/pdftotext_escape/" . $pdf_name[$i];
                // Windowsは￥マーク？
                $cmd = __DIR__ . "\\xpdf-tools-win-4.03\\bin64\\pdftotext -enc Shift-JIS " . __DIR__ . "\\pdftotext_escape\\" . $pdf_name[$i];
                exec ($cmd, $dummy, $result);
                if ($result === 0) {
                    $txt_name = explode(".", $pdf_name[$i])[0] . ".txt";
                    $txt_path = __DIR__ . "\\pdftotext_escape\\" . $txt_name;
                    // 文字化け
                    $file_get_contents = file_get_contents($txt_path);
                    $str = mb_convert_encoding($file_get_contents,"utf-8","sjis"); // シフトJISからUTF-8に変換
                    $contents[$i] = $str;
                }
            }
        }



        // for ($i = 0; $i < $txt_cnt; $i++) {
        //     // $contents = "";
        //     $file_path = $txt_contents[$i];
        //     $zip = new \ZipArchive();
    
        //     if ($zip->open($file_path) === true) {
        //         $xml = $zip->getFromName("word/document.xml");
        //         if ($xml) {
        //             $dom = new \DOMDocument();
        //             $dom->loadXML($xml);
        //             $paragraphs = $dom->getElementsByTagName("p");
        //             foreach ($paragraphs as $p) {
        //                 $texts = $p->getElementsByTagName("t");
        //                 foreach ($texts as $t) {
        //                     $contents[$i] .= $t->nodeValue;
        //                 }
        //             }
        //             $contents[$i] = preg_replace("#[ \n\t\r　]+#um", "", $contents[$i]);
        //         }
        //     }
        // }
    }

    ///////////////////////
    // Zipファイル読み込み //
    //////////////////////
    // $zip_cnt = 0;
    // $zip_contents[] = "";
    // if (isset($_FILES["dirname"]["name"])) {
    //     for ($i = 0; $i < count($_FILES["dirname"]["name"]); $i++) {
    //         if (substr($_FILES["dirname"]["name"][$i], -3, 3) === "zip") {
    //             $zip_contents[$zip_cnt] = $_FILES["dirname"]["tmp_name"][$i];
    //             $zip_cnt++;
    //         }
    //     }
    //     for ($i = 0; $i < $zip_cnt; $i++) {
    //         $file_path = $zip_contents[$i];
    //         $zip = new \ZipArchive();
            
    //         if ($zip->open($file_path) === true) {    
    //         // $docx = $zip->open($file_path);
    //         // if ($docx) {
    //         //     $zip->open($docx);
    //             $zip->open($file_path);
    //             $xml = $zip->getFromName("word/document.xml");
    //             if ($xml) {
    //                 $dom = new \DOMDocument();
    //                 $dom->loadXML($xml);
    //                 $paragraphs = $dom->getElementsByTagName("p");
    //                 foreach ($paragraphs as $p) {
    //                     $texts = $p->getElementsByTagName("t");
    //                     foreach ($texts as $t) {
    //                         $contents[$i] .= $t->nodeValue;
    //                     }
    //                 }
    //                 $contents[$i] = preg_replace("#[ \n\t\r　]+#um", "", $contents[$i]);
    //             }
    //         }
    //     }
    // }
    



?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>similar_check</title>
    <!-- Bootstrap4 -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        .input_text {
            margin: 30px 0;
        }

        .row {
            margin-top: 0;
        }

        p {
            margin-bottom: 0;
        }

        .result {
            padding-bottom: 25px;
            margin-bottom: 25px;
            border-bottom: 1px solid #000;
        }

        .check {
            padding: 0;
        }

        .check1, .check3, .check5 {
            padding-right: 10px;
        }

        .check2, .check4, .check6 {
            padding-left: 10px;
        }

        .check p {
            margin: 3px 0;
        }

        textarea {
            width: 100%;
        }

        .dirname {
            display: none;
        }

        label {
            display: inline-block;
            border: 1px solid #000;
            padding: 3px;
            background-color: lightgray;
            box-shadow: 1px 1px 0px rgba(0, 0, 0, .3);
            color: #000;
            text-decoration: none;
        }

        label:active {
            position: relative;
            top: 1px;
            left: 1px;
            color: #000;
        }

        .button {
            border-radius: 5px 5px;
        }


        .content_box {
            background-color: #dfd;
            padding: 10px 40px 40px 40px;
            margin-bottom: 40px;
            border-radius: 16px 16px;
        }

        .example {
            border: 1px solid #000;
            padding: 16px;
            margin-bottom: 16px;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="container">
            <!-- ------------------------------------------- -->
            <p>similar_text</p>
            <div class="row input_text">
                <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check1">
                    <p>Text1</p>
                    <textarea name="check1" id="check1" cols="" rows="10"><?php if(!empty($text1)){
                            echo $text1;
                        } ?></textarea>
                </div>

                <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check2">
                    <p>Text2</p>
                    <textarea name="check2" id="check2" cols="" rows="10"><?php if(!empty($text2)){
                            echo $text2;
                        } ?></textarea>
                </div>
                <input class="col-12 col-sm-12 col-md-12" id="check" type="submit" name="check" value="比較">
            </div>
            <div class="result">
                <p>類似度：<?php if (isset($perc)) {echo $perc . "%";} ?></p>
            </div>

            <!-- ------------------------------------------- -->
            <p>substr</p>
            <div class="row input_text">
                <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check1">
                    <p>Text3</p>
                    <textarea name="check3" id="check3" cols="" rows="10"><?php if(!empty($text3)){
                            echo $_POST["check3"];
                        } ?></textarea>
                </div>

                <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check2">
                    <p>Text4</p>
                    <textarea name="check4" id="check4" cols="" rows="10"><?php if(!empty($text4)){
                            echo $_POST["check4"];
                        } ?></textarea>
                </div>
                N-gram:<input type="number" class="ngram" id="ngram" name="ngram" min="2" max="10" step="1" value="3">
                <input class="col-12 col-sm-12 col-md-12" id="check_sub" type="submit" name="check_sub" value="比較">
            </div>
            <div class="result">
                <p>類似度：<?php if (isset($perc_sub)) {echo $perc_sub . "%";} ?></p>
            </div>

            <!-- <p>フォルダ読み込み</p>
            <div class="row input_text">
                <input type="file" class="dirname" id="dirname" name="dirname[]" webkitdirectory directory value="フォルダ読み込み">
                <label for="dirname">フォルダ選択</label>
                <input type="submit" class="col-12 col-sm-12 col-md-12" id="read_dir" name="read_dir" value="読み込み">
                <?php　// var_dump($_FILES["dirname"]["name"]); ?>
                <?php // for ($i = 0; $i < count($contents); $i++): ?>
                    <textarea name="read_text" id="read_text<?php echo $i; ?>" cols="30" rows="10"><?php echo $contents[$i]; ?></textarea>
                <?php //endfor; ?> -->

            </div>


            <div class="content_box">
                <p>【フォルダ読み込み】</p>
                <p class="discription">
                    「フォルダ選択」ボタンで比較したい.docxファイルが入っているフォルダを選択し、読み込みボタンを押下することで、指定したフォルダ内の.docxファイルに書かれている文字列を取得し、類似度を計算します。<br>
                    現状、N-gramでの類似度チェックとなっているため、N-gramの数値の指定もお願いします。
                </p>
                <div class="row input_text">
                    <input type="file" class="dirname" id="dirname" name="dirname[]" webkitdirectory directory value="フォルダ読み込み"><br>
                    <label class="button" for="dirname">フォルダ選択</label><br>
                    <div class="w-100"></div>
                    <p class="mr-5">N-gram:<input type="number" class="ngram" id="ngram" name="ngram" min="2" max="10" step="1" value="<?php echo $_POST["ngram"]; ?>"></p>
                    <p class="p-0">類似度閾値：<input type="number" class="threshold" id="threshold" name="threshold" min="1" max="100" step="1" value="<?php echo $_POST["threshold"]; ?>"></p>
                    <div class="w-100"></div>
                    <p>※文字列に含まれる「改行」「空白」「タブ」は削除して計算しています。</p>
                    <?php if ($text_min_len < $_POST["ngram"] || $_POST["ngram"] < 2) : ?>
                        <p class="error col-12 col-sm-12 col-md-12 p-0">N-gramは2以上かつ文字列の文字数以下で指定してください</p>
                    <?php elseif (empty($_FILES["dirname"]["name"][0]) && isset($_POST["read_dir"])) : ?>
                        <p class="error col-12 col-sm-12 col-md-12 p-0">読み込むフォルダを指定してください</p>
                    <?php endif; ?>
                    <input type="submit" class="col-12 col-sm-12 col-md-12" id="read_dir" name="read_dir" value="読み込み">
                    <?php // var_dump($_FILES["dirname"]["name"]); ?>
                    <?php for ($i = 0; $i < count($contents); $i++): ?>
                        <p>ファイル<?php echo $i + 1; ?>：<?php echo $content_name[$i]; ?></p>
                        <textarea name="read_text" id="read_text<?php echo $i; ?>" cols="30" rows="10"><?php echo $contents[$i]; ?></textarea>
                    <?php endfor; ?>
                </div>

                <?php
                    // 計算した類似度の表示
                    $contents_cnt = count($contents);
                    if ($contents_cnt >=  2) {
                        for ($i = 0; $i < $contents_cnt - 1; $i++) {
                            for ($j = $i + 1; $j < $contents_cnt; $j++) {
                                get_ngram ($contents[$i], $_POST["ngram"], $substr1);
                                get_ngram ($contents[$j], $_POST["ngram"], $substr2);
                                similar_check ($substr1, $substr2, $perc);
                                if ($perc > $_POST["threshold"]) {
                                    echo "<p style='color: red;'>ファイル" . ($i + 1) . "と" . "ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
                                } else {
                                    echo "<p>ファイル" . ($i + 1) . "と" . "ファイル" . ($j + 1) . "の類似度：" . $perc . "%" . "</p>";
                                }

                            }
                        }
                    }
                ?>
            </div>




            <!-- <p>空白、改行、タブ削除</p>
            <div class="row input_text">
                <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check5">
                    <p>Text5</p>
                    <textarea name="check5" id="check5" cols="" rows="10"><?php // if(!empty($text5)){
                            // echo $text5;
                        // } ?></textarea>
                </div>

                <div class="col-6 col-sm-6 col-md-6 col-lg-6 check check6">
                    <p>Text6</p>
                    <textarea name="check6" id="check6" cols="" rows="10"><?php // if(!empty($text6)){
                            // echo $text6;
                        // } ?></textarea>
                </div>
                <input class="col-12 col-sm-12 col-md-12" id="check" type="submit" name="conversion" value="変換">
            </div> -->

    </form>

    <!-- Bootstrap4 -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>