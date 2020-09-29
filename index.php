<?php

header("X-Download-Options: noopen");
header("Content-type: text/html; charset=utf-8");
ini_set( 'session.cookie_httponly', 1 );
ini_set('session.use_only_cookies',1);
ini_set( 'display_errors', 0);

session_start();

/**
 * サニタイズ／エスケープ処理
 * ------------------------------------------------------------------ */
$_SERVER["REQUEST_URI"]       = htmlspecialchars($_SERVER["REQUEST_URI"], ENT_QUOTES, 'UTF-8');
$_SERVER["HTTP_REFERER"]      = htmlspecialchars($_SERVER["HTTP_REFERER"], ENT_QUOTES, 'UTF-8');
$_SERVER["REMOTE_ADDR"]       = htmlspecialchars($_SERVER["REMOTE_ADDR"], ENT_QUOTES, 'UTF-8');
$_SERVER["SERVER_NAME"]       = htmlspecialchars($_SERVER["SERVER_NAME"], ENT_QUOTES, 'UTF-8');
$_SERVER["HTTP_HOST"]         = htmlspecialchars($_SERVER["HTTP_HOST"], ENT_QUOTES, 'UTF-8');
$_SERVER["HTTP_X_CSRF_TOKEN"] = htmlspecialchars($_SERVER["HTTP_X_CSRF_TOKEN"], ENT_QUOTES, 'UTF-8');
$_SERVER["HTTP_USER_AGENT"]   = htmlspecialchars($_SERVER["HTTP_USER_AGENT"], ENT_QUOTES, 'UTF-8');

// 基本設定
date_default_timezone_set("Asia/Tokyo");

// 定義
define('__ABSPATH__', dirname(__FILE__) . '/');
define('__HOST__',    $_SERVER['HTTP_HOST']);
define('__REFERER__',$_SERVER['HTTP_REFERER']);

define('input_template',   './includes/1_input.html');
define('confirm_template', './includes/2_confirm.html');
define('thanks_template',  './includes/3_thanks.html');

// メールFrom
define('mail_ad_admin',  'hide@toyosu.jp');
define('mail_from_name', '送信者名');
define('mail_from_mail', 'hide@toyosu.jp');

// メールテンプレート
define('mail_txt_admin', './includes/mail_1.txt');
define('mail_txt_user',  './includes/mail_2.txt');
// メール件名
define('mail_subject_contact_admin', 'お問い合わせがきました'); // 管理者向け
define('mail_subject_contact_user',  'お問い合わせを受け付けました'); // ユーザ向け

$required = array('category', 'subject', 'customerName', 'customerEmail', 'optin');
$num_check = array('tel');
$multiple = array('category', 'subject');
$allow_break = array('honbun');
$mail_check = array('customerEmail');

$category_list = array(
  'カテゴリー1',
  'カテゴリー2',
  'カテゴリー3',
  'カテゴリー4',
  'カテゴリー5',
);

$subject_list = array(
  'お問い合わせ項目1',
  'お問い合わせ項目2',
  'お問い合わせ項目3',
  'お問い合わせ項目4',
  'お問い合わせ項目5',
);


$keyj = array(
  'category' => 'カテゴリー',
  'subject' => 'ご用件',
  'honbun' => 'お問い合わせ内容',
  'customerName' => 'お名前',
  'customerKana' => 'お名前カナ',
  'customerEmail' => 'E-mailアドレス',
  'tel' => '電話番号',
  'optin' => '個人情報保護方針についての同意',
);

/**
 * エスケープ処理
 * ------------------------------------------------------------------ */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $pa = htmlspecialchars_encode_array($_POST);
}
else {
  $pa = htmlspecialchars_encode_array($_GET);
}
$mode = $pa['mode'];
$error = form_check($mode,$pa);


// リファラチェック
// HTTPリファラーが設定されている場合は取得して、そうでない場合はnullを設定する。
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
// 同一ドメインからの遷移のみ許可
if ( $mode ) {
  if (parse_url($referer, PHP_URL_HOST) != __HOST__ ) {
    $error_msg = '不正な画面遷移が行われました。<span style="color:#ff0000;">( Error No. 090 )</span>';
    print(get_error_page($error_msg));
    exit;
  }
}

if ($mode == "" || $error != "" || $mode == "戻る") {
  $_SESSION['contact'] = false;
  get_input_form($mode,$error,$pa);
}
elseif ($mode == "ご記入内容の確認") {
  get_confirm_page($pa);
}
elseif ($mode == "送信") {
  if ( $_SESSION['contact'] ) {

  }
  else {
    send_mail('admin',$pa);
    send_mail('user',$pa);
  }
  get_thanks_page();
}



function form_check($mode,$pa=NULL) {

  global $required;
  global $num_check;
  global $mail_check;
  global $keyj;

  if ( empty($pa) && $mode != "") {
    $error_msg = '不正な画面遷移が行われました。<span style="color:#ff0000;">( Error No. 090 )</span>';
    print(get_error_page($error_msg));
    exit;
  }
  $error_msg = "";

  foreach ( $required as $key => $value ) {
    if ( !is_array($pa[$value]) ) {
      if ( empty($pa[$value]) ) {
        $error_msg .= "<li>$keyj[$value]を入力して下さい。</li>\n";
      }
    }
    else {
//       if ( is_countable($pa[$value]) && count($pa[$value]) == 0 ) {
      if ( empty($pa[$value]) ) {
        $error_msg .= "<li>$keyj[$value]を入力して下さい。</li>\n";
      }
    }
  }

  foreach ( $num_check as $key => $value ) {
    if ( !empty($pa[$key]) ) {
      if ( preg_match("/^[0-9]$/", $value) ) {
        $error_msg .= "<li>$keyj[$value]には半角数字しか入力できません。</li>\n";
      }
    }
  }

  if ( !preg_match("/^\d{2,5}\d{1,4}\d{4}$/",$pa['tel']) ) {
    $error_msg .= "<li>電話番号を正しく入力してください。</li>\n";
  }

  foreach ( $mail_check as $key => $value ) {
    if ( !empty($pa[$value]) ) {
      if ( !preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $pa[$value]) ) {
        $error_msg .= "<li>$keyj[$value]を正しく入力して下さい。</li>\n";
      }
    }
  }

  if ( !empty($pa['customerName']) ) {
    if ( mb_strlen($pa['customerName'], 'UTF-8') > 10 ) {
      $error_msg .= "<li>お名前は全角10文字（半角30文字）以内で入力してください。</li>\n";
    }
    if ( preg_match("/([Hh][Tt][Tt][Pp]|[Ff][Tt][Pp]|\/\/|@)/",$pa['customerName']) ) {
      $error_msg .= "<li>お名前に使用できない文字が含まれています</li>\n";
    }
  }

  if ( !empty($pa['companyName']) ) {
    if ( mb_strlen($pa['companyName'], 'UTF-8') > 25 ) {
      $error_msg .= "<li>法人名は全角25文字（半角75文字）以内で入力してください。</li>\n";
    }
    if ( preg_match("/([Hh][Tt][Tt][Pp]|[Ff][Tt][Pp]|\/\/|@)/",$pa['customerName']) ) {
      $error_msg .= "<li>法人名に使用できない文字が含まれています</li>\n";
    }
  }

  return $error_msg;
}

function send_mail($target=NULL,$pa) {

  mb_language("ja");
  mb_internal_encoding("UTF-8");

  $fromDisp = mail_from_name;

  if ( $target == 'admin' ) {
    $to = mail_ad_admin;
    $mail_body = get_mail_template($target,$pa);
    $mail_body .= get_mail_template('user',$pa);
    $subject = mail_subject_contact_admin;
  }
  else {
    $to = $pa['customerEmail'];
    $mail_body = get_mail_template($target,$pa);
    $subject = mail_subject_contact_user;
  }

  $fromDisp = mb_encode_mimeheader($fromDisp, "ISO-2022-JP","AUTO")."<".mail_from_mail.">";

  $mail_head = "From: $fromDisp\r\n";
  $mail_head .= "Content-Transfer-Encoding: 7bit\r\n";
  $mail_head .= "Content-Type: text/plain; charset=ISO-2022-JP\r\n";

  $mail_body = preg_replace('/^\.$/','. ',$mail_body);

  $mail_body = preg_replace("/\xEF\xBB\xBF/", "", $mail_body);

  $res = mb_send_mail( $to, $subject, $mail_body, $mail_head);
  if ( $res != 1 ) {
    $error_msg = 'FAILED TO MAIL';
    print(get_error_page($error_msg));
    exit;
  }
  $_SESSION['contact'] = true;
}

function get_mail_template($target=NULL,$pa=NULL) {
  $mail_template = "";
  if ( $target == 'user') {
    $mail_template = file_get_contents(mail_txt_user, FALSE, NULL);
  }
  elseif ($target == 'admin') {
    $mail_template = file_get_contents(mail_txt_admin, FALSE, NULL);
  }

  $mail_template = preg_replace('/###date###/',get_datetime(),$mail_template);
  $mail_template = preg_replace('/###category_txt###/',implode('、',$pa['category']),$mail_template);
  $mail_template = preg_replace('/###subject_txt###/',implode('、',$pa['subject']),$mail_template);

  $customer = $pa['customerName'];
  $customer = preg_replace('/\./',' ． ',$customer);
  $customer = preg_replace('/\//',' ／ ',$customer);
  $customer = preg_replace('/(@|＠)/',' ＠ ',$customer);
  $mail_template = preg_replace('/###customerName###/',$customer,$mail_template);

  $company = $pa['companyName'];
  $company = preg_replace('/\./',' ． ',$company);
  $company = preg_replace('/\//',' ／ ',$company);
  $company = preg_replace('/(@|＠)/',' ＠ ',$company);
  $mail_template = preg_replace('/###companyName###/',$company,$mail_template);

  // その他の項目置換
  foreach ( $pa as $key => $value ) {
    if ( !is_array($value) ) {
      $mail_template = preg_replace("/###{$key}###/", $value, $mail_template);
    }
  }

  return $mail_template;
}

// お問い合わせ送信完了ページ
function get_thanks_page($pa=NULL) {
  $thanks = file_get_contents(thanks_template, FALSE, NULL);
  print($thanks);
}

// お問い合わせ確認ページ
function get_confirm_page($pa=NULL) {

  global $allow_break;

  $confirm = file_get_contents(confirm_template, FALSE, NULL);

  foreach ( $allow_break as $key => $value ) {
    if ( strlen($pa[$value]) > 0 ) {
      $text = preg_replace("/(\r\n|\r|\n)/", '<br>', $pa[$value]);
      $confirm = preg_replace("/###{$value}_br###/", $text, $confirm);
    }
    else {
      $confirm = preg_replace("/###{$value}_br###/", '本文なし', $confirm);
    }
  }

  $html = get_list_confirm('category',  $pa);
  $confirm = preg_replace('/###category_val###/',$html, $confirm);

  $html = get_list_confirm('subject',  $pa);
  $confirm = preg_replace('/###subject_val###/',$html, $confirm);

  // その他の項目置換
  foreach ( $pa as $key => $value ) {
    if ( !is_array($value) ) {
      $confirm = preg_replace("/###{$key}###/", $value, $confirm);
    }
  }
  print($confirm);

}

// お問い合わせフォーム
function get_input_form($mode=NULL,$error=NULL,$pa=NULL) {
  global $category_list;
  global $subject_list;

  $input = file_get_contents(input_template, FALSE, NULL);

  if ( $input == "" ) {
    error("Can't open INPUT TEMPLATE <span style=\"color:#ff0000;\">( Error No. 002 )</span>");
  }

  if ( !empty($mode) && $error != "") {
    $input = preg_replace('/###errorDisplay###/','block',$input);
    $input = preg_replace('/###errormsg###/',$error,$input);
  }
  else {
    $input = preg_replace('/###errorDisplay###/','none',$input);
  }

  // チェックボックス生成
  $base_html = '<li><label><input type="checkbox" name="category[]" value="###val###" ###checked###>###txt###</label></li>';
  $html = get_list('category', $base_html,  $category_list, $pa);
  $input = preg_replace('/###category_list###/',$html,$input);

  // チェックボックス生成
  $base_html = '<li><label><input type="checkbox" name="subject[]" value="###val###" ###checked###>###txt###</label></li>';
  $html = get_list('subject', $base_html,  $subject_list, $pa);
  $input = preg_replace('/###subject_list###/',$html,$input);

  // その他の項目置換
  foreach ( $pa as $key => $value ) {
    if ( !is_array($value) ) {
      $input = preg_replace("/###{$key}###/", $value, $input);
    }
  }

  // 置換できなかったものは空にする
  $input = preg_replace('/###(.*?)###/','',$input);
  print($input);

}

function get_list_confirm($type, $pa=NULL) {
  $html = "";
  if ( is_array($pa[$type]) ) {
    foreach ($pa[$type] as $key => $value) {
      $item = '<input type="hidden" name="' . $type . '[]" value="' . $value . '">';
      $html .= $item. "" . $value . "、";
    }
  }
  $html = preg_replace("/、$/",'',$html);
  return $html;
}

// チェックボックス生成
function get_list($type,$base_html,$list, $pa) {
  global $subject_list;
  $html = "";
  foreach ( $list as $key => $value ) {
    $item = $base_html;
    $item = preg_replace('/###val###/',$value,$item);
    if ( $type != "subject" ) {
      $item = preg_replace('/###txt###/', $value, $item);
    }
    else {
      $item = preg_replace('/###txt###/', $subject_list[$key], $item);
    }
    if ( is_array($pa[$type]) ) {
      foreach ($pa[$type] as $key2 => $value2) {
        if ($value == $value2) {
          $item = preg_replace('/###checked###/', ' checked="checked"', $item);
        }
      }
    }
    $html .= $item;
  }
  $html = preg_replace('/###checked###/', '', $html);
  return $html;
}


// エラーページ表示
function get_error_page($param) {
  $mail_ad_admin = mail_ad_admin;
  $html = <<<EOF_HTML
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>エラー</title>
</head>
<body>
<br><br>
{$param}
<br><br>
エラーが発生しました。<br>
お手数ですが最初からやり直してください。
<br><br>
<form method="post" action="/">
<input type="submit" value="戻る">
</form>
</body>
</html>

EOF_HTML;
  return $html;
}

// 日時取得
function get_datetime() {
  $date = date("Y/m/d(D)H:i");
  return $date;
}


// パラメーター解析
function htmlspecialchars_encode_array($array, $ignore_keys = NULL){
  if (is_array($array)){
    foreach($array as $k => $v){
      // スキップキー
      if (in_array($k, (array)$ignore_keys, TRUE)){
        continue;
      }
      if (is_array($v)){
        // 配列だったらここで再帰
        $array[$k] = htmlspecialchars_encode_array($array[$k], $ignore_keys);
      } else {
        // 変換
        if (get_magic_quotes_gpc()) $v = stripslashes($v);
        $v = trim($v);
        $array[$k] = htmlspecialchars($v, ENT_QUOTES);
      }
    }
  } else {
    // 変換
    if (get_magic_quotes_gpc()) $array = stripslashes($array);
    $array = trim($array);
    $array = htmlspecialchars($array, ENT_QUOTES);
  }
  return $array;
}




