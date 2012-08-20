<?php
// $Id: article.inc.php,v 1.25.6 2008/01/05 17:58:00 upk Exp $
// Copyright (C)
//   2005-2006,2008 PukiWiki Plus! Team
//   2002-2005 PukiWiki Developers Team
//   2002      Originally written by OKAWARA,Satoshi <kawara@dml.co.jp>
//             http://www.dml.co.jp/~kawara/pukiwiki/pukiwiki.php
//
// article: BBS-like plugin

 /*
 メッセージを変更したい場合はLANGUAGEファイルに下記の値を追加してからご使用ください
	$_btn_name = 'お名前';
	$_btn_article = '記事の投稿';
	$_btn_subject = '題名: ';

 ※$_btn_nameはcommentプラグインで既に設定されている場合があります

 投稿内容の自動メール転送機能をご使用になりたい場合は
 -投稿内容のメール自動配信
 -投稿内容のメール自動配信先
 を設定の上、ご使用ください。

 */

defined('PLUGIN_ARTICLE_COLS')           or define('PLUGIN_ARTICLE_COLS',	70); // テキストエリアのカラム数
defined('PLUGIN_ARTICLE_ROWS')           or define('PLUGIN_ARTICLE_ROWS',	 5); // テキストエリアの行数
defined('PLUGIN_ARTICLE_NAME_COLS')      or define('PLUGIN_ARTICLE_NAME_COLS',	24); // 名前テキストエリアのカラム数
defined('PLUGIN_ARTICLE_SUBJECT_COLS')   or define('PLUGIN_ARTICLE_SUBJECT_COLS',	60); // 題名テキストエリアのカラム数
defined('PLUGIN_ARTICLE_NAME_FORMAT')    or define('PLUGIN_ARTICLE_NAME_FORMAT',	'[[$name]]'); // 名前の挿入フォーマット
defined('PLUGIN_ARTICLE_SUBJECT_FORMAT') or define('PLUGIN_ARTICLE_SUBJECT_FORMAT',	'**$subject'); // 題名の挿入フォーマット

defined('PLUGIN_ARTICLE_INS')            or define('PLUGIN_ARTICLE_INS',	0); // 挿入する位置 1:欄の前 0:欄の後
defined('PLUGIN_ARTICLE_COMMENT')        or define('PLUGIN_ARTICLE_COMMENT',	1); // 書き込みの下に一行コメントを入れる 1:入れる 0:入れない
defined('PLUGIN_ARTICLE_AUTO_BR')        or define('PLUGIN_ARTICLE_AUTO_BR',	1); // 改行を自動的変換 1:する 0:しない

defined('PLUGIN_ARTICLE_MAIL_AUTO_SEND') or define('PLUGIN_ARTICLE_MAIL_AUTO_SEND',	0); // 投稿内容のメール自動配信 1:する 0:しない
defined('PLUGIN_ARTICLE_MAIL_FROM')      or define('PLUGIN_ARTICLE_MAIL_FROM',	''); // 投稿内容のメール送信時の送信者メールアドレス
defined('PLUGIN_ARTICLE_MAIL_SUBJECT_PREFIX') or define('PLUGIN_ARTICLE_MAIL_SUBJECT_PREFIX', "[someone's PukiWiki]"); // 投稿内容のメール送信時の題名

// 投稿内容のメール自動配信先
global $_plugin_article_mailto;
$_plugin_article_mailto = array (
	''
);

function plugin_article_action()
{
	global $script, $post, $vars, $cols, $rows, $now;
//	global $_title_collided, $_msg_collided, $_title_updated;
	global $_plugin_article_mailto, $_no_subject, $_no_name;
//	global $_msg_article_mail_sender, $_msg_article_mail_page;

$_title_collided   = _('On updating $1, a collision has occurred.');
$_title_updated    = _('$1 was updated');
$_msg_collided = _('It seems that someone has already updated this page while you were editing it.<br />
 + is placed at the beginning of a line that was newly added.<br />
 ! is placed at the beginning of a line that has possibly been updated.<br />
 Edit those lines, and submit again.');
$_msg_article_mail_sender = _('Author: ');
$_msg_article_mail_page = _('Page: ');

	// if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');
	if (auth::check_role('readonly')) die_message('PKWK_READONLY prohibits editing');

	if ($post['msg'] == '')
		return array('msg'=>'','body'=>'');

	$name = ($post['name'] == '') ? $_no_name : $post['name'];
	$name = ($name == '') ? '' : str_replace('$name', $name, PLUGIN_ARTICLE_NAME_FORMAT);
	$subject = ($post['subject'] == '') ? $_no_subject : $post['subject'];
	$subject = ($subject == '') ? '' : str_replace('$subject', $subject, PLUGIN_ARTICLE_SUBJECT_FORMAT);
	$article  = $subject . "\n" . '>' . $name . ' (' . $now . ')~' . "\n" . '~' . "\n";

	$msg = rtrim($post['msg']);
	if (PLUGIN_ARTICLE_AUTO_BR) {
		//改行の取り扱いはけっこう厄介。特にURLが絡んだときは…
		//コメント行、整形済み行には~をつけないように arino
		$msg = implode("\n", preg_replace('/^(?!\/\/)(?!\s)(.*)$/', '$1~', explode("\n", $msg)));
	}
	$article .= $msg . "\n\n" . '//';

	if (PLUGIN_ARTICLE_COMMENT) $article .= "\n\n" . '#comment' . "\n";

	$postdata = '';
	$postdata_old  = get_source($post['refer']);
	$article_no = 0;

	foreach($postdata_old as $line) {
		if (! PLUGIN_ARTICLE_INS) $postdata .= $line;
		if (preg_match('/^#article/i', $line)) {
			if ($article_no == $post['article_no'] && $post['msg'] != '')
				$postdata .= $article . "\n";
			++$article_no;
		}
		if (PLUGIN_ARTICLE_INS) $postdata .= $line;
	}

	$postdata_input = $article . "\n";
	$body = '';

	if (md5(@implode('', get_source($post['refer']))) != $post['digest']) {
		$title = $_title_collided;

		$body = $_msg_collided . "\n";

		$s_refer    = htmlspecialchars($post['refer']);
		$s_digest   = htmlspecialchars($post['digest']);
		$s_postdata = htmlspecialchars($postdata_input);
		$body .= <<<EOD
<form action="$script?cmd=preview" method="post" class="form-stacked">
 <div>
  <input type="hidden" name="refer" value="$s_refer" />
  <input type="hidden" name="digest" value="$s_digest" />
  <textarea name="msg" rows="$rows" cols="$cols" id="textarea">$s_postdata</textarea><br />
 </div>
</form>
EOD;

	} else {
		page_write($post['refer'], trim($postdata));

		// 投稿内容のメール自動送信
		if (PLUGIN_ARTICLE_MAIL_AUTO_SEND) {
			$mailaddress = implode(',', $_plugin_article_mailto);
			$mailsubject = PLUGIN_ARTICLE_MAIL_SUBJECT_PREFIX . ' ' . str_replace('**', '', $subject);
			if ($post['name'])
				$mailsubject .= '/' . $post['name'];
			$mailsubject = mb_encode_mimeheader($mailsubject);

			$mailbody = $post['msg'];
			$mailbody .= "\n\n" . '---' . "\n";
			$mailbody .= _('Author: ') . $post['name'] . ' (' . $now . ')' . "\n";
			$mailbody .= _('Page: ') . $post['refer'] . "\n";
			$mailbody .= '　 URL: ' . get_page_absuri($post['refer']) . "\n";
			$mailbody = mb_convert_encoding($mailbody, 'JIS');

			$mailaddheader = 'From: ' . PLUGIN_ARTICLE_MAIL_FROM;

			mail($mailaddress, $mailsubject, $mailbody, $mailaddheader);
		}

		$title = $_title_updated;
	}
	$retvars['msg'] = $title;
	$retvars['body'] = $body;

	$post['page'] = $post['refer'];
	$vars['page'] = $post['refer'];

	return $retvars;
}

function plugin_article_convert()
{
	global $script, $vars, $digest;
//	global $_btn_article, $_btn_name, $_btn_subject;
	static $numbers = array();

	$_btn_name    = _('Name: ');
	$_btn_article = _('Submit');
	$_btn_subject = _('Subject: ');
	// if (PKWK_READONLY) return ''; // Show nothing
	if (auth::check_role('readonly')) return ''; // Show nothing

	if (! isset($numbers[$vars['page']])) $numbers[$vars['page']] = 0;

	$article_no = $numbers[$vars['page']]++;

	$helptags = edit_form_assistant();

	$s_page   = htmlspecialchars($vars['page']);
	$s_digest = htmlspecialchars($digest);
	$name_cols = PLUGIN_ARTICLE_NAME_COLS;
	$subject_cols = PLUGIN_ARTICLE_SUBJECT_COLS;
	$article_rows = PLUGIN_ARTICLE_ROWS;
	$article_cols = PLUGIN_ARTICLE_COLS;
	$string = <<<EOD
<form action="$script" method="post" class="form-horizontal">
	<fieldset>
		<legend></legend>
		<div class="articleform" onmouseup="pukiwiki_pos()" onkeyup="pukiwiki_pos()">
			<input type="hidden" name="article_no" value="$article_no" />
			<input type="hidden" name="plugin" value="article" />
			<input type="hidden" name="digest" value="$s_digest" />
			<input type="hidden" name="refer" value="$s_page" />
			<div class="control-group">
				<div class="control-label">
					<label for="_p_article_name_$article_no">$_btn_name</label>
				</div>
				<div class="controls">
					<input type="text" class="input-xlarge" name="name" id="_p_article_name_$article_no" maxlength="$name_cols" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					<label for="_p_article_subject_$article_no">$_btn_subject</label>
				</div>
				<div class="controls">
					<input type="text" class="input-xlarge" name="subject" id="_p_article_subject_$article_no" maxlength="$subject_cols" />
				</div>
			</div>
			<div class="control-group">
				<div class="control-label">
					内容
				</div>
				<div class="controls">
					<textarea class="input-xlarge" name="msg" rows="$article_rows" cols="$article_cols">\n</textarea><br />
				</div>
			</div>
			<div class="form-actions">
				<input type="submit" class="btn btn-primary" name="article" value="$_btn_article" />
			</div>
			$helptags
		</div>
	</fieldset>
</form>
EOD;

	return $string;
}
?>
