<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: list.inc.php,v 1.5.1 2005/01/09 08:16:28 miko Exp $
//
// IndexPages plugin: Show a list of page names
function plugin_list_action()
{
	global $vars,$_title_list,$_title_filelist,$whatsnew;
	global $adminpass;

	// Redirected from filelist plugin?
	$filelist = (array_key_exists('cmd',$vars) and $vars['cmd']=='filelist');

	if ($filelist and $adminpass != md5($vars['pass'])) $filelist = false;

	return array(
		'msg'=>$filelist ? $_title_filelist : $_title_list,
		'body'=>plugin_list_getlist($filelist));
}

// Get a list
function plugin_list_getlist($withfilename = FALSE)
{
	global $non_list,$whatsnew;

	$pages = array_diff(get_existpages(),array($whatsnew));
	if (!$withfilename)
		$pages = array_diff($pages, preg_grep('/' . $non_list . '/', $pages));
	if (empty($pages)) return '';

	return page_list($pages,'read',$withfilename);
}
?>
