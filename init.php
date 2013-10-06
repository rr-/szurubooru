<?php
function download($source, $destination)
{
	$content = file_get_contents($source);
	if (substr($destination, -1, 1) == '/')
		$destination .= basename($source);
	file_put_contents($destination, $content);
}

download('http://raw.github.com/aehlke/tag-it/master/css/jquery.tagit.css', './public_html/media/css/jquery.tagit.css');
download('http://raw.github.com/aehlke/tag-it/master/js/tag-it.min.js', './public_html/media/js/jquery.tagit.js');
