<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: freeze.inc.php,v 1.11.2 2007/07/29 09:45:51 miko Exp $
// Copyright (C)
//   2005-2007 PukiWiki Plus! Team
//   2003-2004, 2007 PukiWiki Developers Team
// License: GPL v2 or (at your option) any later version
//
// Freeze(Lock) plugin

// Reserve 'Do nothing'. '^#freeze' is for internal use only.
function plugin_freeze_convert() { return ''; }

function plugin_freeze_action()
{
	global $script, $vars, $function_freeze;

	$_title_isfreezed = _(' $1 has already been frozen');
	$_title_freezed   = _(' $1 has been frozen.');
	$_title_freeze    = _('Freeze  $1');
	$_msg_invalidpass = _('Invalid password.');
	$_msg_freezing    = _('Please input the password for freezing.');
	$_btn_freeze      = _('Freeze');

	$page = isset($vars['page']) ? $vars['page'] : '';
	if (! $function_freeze || is_cantedit($page) || ! is_page($page))
		return array('msg' => '', 'body' => '');

	$pass = isset($vars['pass']) ? $vars['pass'] : NULL;
	$msg = $body = '';
	if (is_freeze($page)) {
		// Freezed already
		$msg  = & $_title_isfreezed;
		$body = str_replace('$1', htmlspecialchars(strip_bracket($page)),
			$_title_isfreezed);

	} else
	if ( (! auth::check_role('role_adm_contents') ) ||
	     ($pass !== NULL && pkwk_login($pass) ) )
	{
		// Freeze
		$postdata = get_source($page);
		array_unshift($postdata, "#freeze\n");
		file_write(DATA_DIR, $page, join('', $postdata), TRUE);

		// Update
		is_freeze($page, TRUE);
		$vars['cmd'] = 'read';
		$msg  = & $_title_freezed;
		$body = '';

	} else {
		// Show a freeze form
		$msg    = & $_title_freeze;
		$s_page = htmlspecialchars($page);
		$body   = ($pass === NULL) ? '' : '<div class="alert alert-error">' .
		'<button type="button" class="close" data-dismiss="alert">&times;</button>' .
		'<strong class="alert-heading">Error!</strong><p>'.$_msg_invalidpass.'</p></div>'."\n";
		$body  .= <<<EOD
<p>$_msg_freezing</p>
<form action="$script" method="post">
 <div>
	<div class="input-append">
		<input type="hidden"   name="cmd"  value="freeze" />
		<input type="hidden"   name="page" value="$s_page" />
		<input type="password" name="pass" size="12" /><input
			   type="submit" class="btn btn-primary" name="ok" value="$_btn_freeze" />
	</div>
 </div>
</form>
EOD;
	}

	return array('msg'=>$msg, 'body'=>$body);
}
?>
