<?php

// adminpass
$adminpass = 'pass';

// use monobook (old style)
//define('SKIN_DIR', WWW_HOME . 'skin/');
//define('SKIN_FILE_DEFAULT', SKIN_DIR . 'monobook/monobook.skin.php');

// override from pukiwiki.php.ini
$page_title = 'Mono Wiki';
//$modifier = '';
//$modifierlink = '';

// enable AutoBaseAlias
$autobasealias = 1;

// enable paraedit
$fixed_heading_anchor = 1;
$fixed_heading_edited = 1;

// disable trackback
$trackback = 0;

// disable referer
$referer = 0;

// always create backup
//$cycle = 0;

// redirect
defined('PKWK_USE_REDIRECT') or define('PKWK_USE_REDIRECT', 1);

// auto_template
$auto_template_func = 1;
$auto_template_rules = array(
        '(.+)\/.+'              => '\1/template',
        '([^\/]+?)\/[^\/]+'     => 'templates/nonSucceeded/\1',
        '([^\/]+?)\/.+'         => 'templates/succeeded/\1',
        '.+'                    => 'templates/all'                                                                                                                       
);

?>
