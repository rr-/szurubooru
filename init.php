<?php
require_once 'src/core.php';

function download($source, $destination)
{
	$content = file_get_contents($source);
	if (substr($destination, -1, 1) == '/')
		$destination .= basename($source);
	file_put_contents($destination, $content);
}

$config = configFactory();
$cssPath = $config->main->mediaPath . DS . 'css' . DS;
$jsPath = $config->main->mediaPath . DS . 'js' . DS;
$fontsPath = $config->main->mediaPath . DS . 'fonts' . DS;

//jQuery
download('http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js', $jsPath . 'jquery.min.js');
download('http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js', $jsPath . 'jquery-ui.min.js');
download('http://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/flick/jquery-ui.css', $cssPath . 'jquery-ui.css');

//jQuery Tag-it!
download('http://raw.github.com/aehlke/tag-it/master/css/jquery.tagit.css', $cssPath . 'jquery.tagit.css');
download('http://raw.github.com/aehlke/tag-it/master/js/tag-it.min.js', $jsPath . 'jquery.tagit.js');

//fonts
download('http://googlefontdirectory.googlecode.com/hg/apache/droidsans/DroidSans.ttf', $fontsPath . 'DroidSans.ttf');
download('http://googlefontdirectory.googlecode.com/hg/apache/droidsans/DroidSans-Bold.ttf', $fontsPath . 'DroidSans-Bold.ttf');
