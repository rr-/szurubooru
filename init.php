<?php
function download($source, $destination)
{
	$content = file_get_contents($source);
	if (substr($destination, -1, 1) == '/')
		$destination .= basename($source);
	file_put_contents($destination, $content);
}

$path = './public_html/';

//jQuery
download('http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js', $path . 'media/js/jquery.min.js');
download('http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js', $path . 'media/js/jquery-ui.min.js');
download('http://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/flick/jquery-ui.css', $path . 'media/css/jquery-ui.css');

//jQuery Tag-it!
download('http://raw.github.com/aehlke/tag-it/master/css/jquery.tagit.css', $path . 'media/css/jquery.tagit.css');
download('http://raw.github.com/aehlke/tag-it/master/js/tag-it.min.js', $path . 'media/js/jquery.tagit.js');

//fonts
download('http://googlefontdirectory.googlecode.com/hg/apache/droidsans/DroidSans.ttf', $path . 'media/fonts/DroidSans.ttf');
download('http://googlefontdirectory.googlecode.com/hg/apache/droidsans/DroidSans-Bold.ttf', $path . 'media/fonts/DroidSans-Bold.ttf');
