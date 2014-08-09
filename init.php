<?php
require_once 'src/core.php';

function updateVersion()
{
	$version = exec('git describe --tags --always --dirty');
	$branch = exec('git rev-parse --abbrev-ref HEAD');
	PropertyModel::set(PropertyModel::EngineVersion, $version . '@' . $branch);
}

function getLibPath()
{
	return TextHelper::absolutePath(Core::getConfig()->main->mediaPath . DS . 'lib');
}

function getFontsPath()
{
	return TextHelper::absolutePath(Core::getConfig()->main->mediaPath . DS . 'fonts');
}

function download($source, $destination = null)
{
	echo 'Downloading: ' . $source . '...' . PHP_EOL;
	flush();

	if ($destination !== null and file_exists($destination))
		return file_get_contents($destination);

	$content = file_get_contents($source);
	if ($destination !== null)
	{
		$dir = dirname($destination);
		if (!file_exists($dir))
			mkdir($dir, 0755, true);

		file_put_contents($destination, $content);
	}
	return $content;
}

function downloadJquery()
{
	$libPath = getLibPath();
	download('http://code.jquery.com/jquery-2.1.1.min.js', $libPath . DS . 'jquery' . DS . 'jquery.min.js');
	download('http://code.jquery.com/jquery-2.1.1.min.map', $libPath . DS . 'jquery' . DS . 'jquery.min.map');
}

function downloadJqueryUi()
{
	$libPath = getLibPath();
	download('http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js', $libPath . DS . 'jquery-ui' . DS . 'jquery-ui.min.js');
	$manifest = download('http://ajax.googleapis.com/ajax/libs/jqueryui/1/MANIFEST');
	$lines = explode("\n", str_replace("\r", '', $manifest));
	foreach ($lines as $line)
	{
		if (preg_match('/themes\/flick\/(.*?) /', $line, $matches))
		{
			$srcUrl = 'http://ajax.googleapis.com/ajax/libs/jqueryui/1/' . $matches[0];
			$dstUrl = $libPath . DS . 'jquery-ui' . DS . $matches[1];
			download($srcUrl, $dstUrl);
		}
	}
}

function downloadJqueryTagIt()
{
	$libPath = getLibPath();
	download('http://raw.github.com/aehlke/tag-it/master/css/jquery.tagit.css', $libPath . DS . 'tagit' . DS . 'jquery.tagit.css');
	download('http://raw.github.com/aehlke/tag-it/master/js/tag-it.min.js', $libPath . DS . 'tagit' . DS . 'jquery.tagit.js');
}

function downloadMousetrap()
{
	$libPath = getLibPath();
	download('http://raw.github.com/ccampbell/mousetrap/master/mousetrap.min.js', $libPath . DS . 'mousetrap' . DS . 'mousetrap.min.js');
}

function downloadFonts()
{
	$fontsPath = getFontsPath();
	download('http://googlefontdirectory.googlecode.com/hg/apache/droidsans/DroidSans.ttf', $fontsPath . DS . 'DroidSans.ttf');
	download('http://googlefontdirectory.googlecode.com/hg/apache/droidsans/DroidSans-Bold.ttf', $fontsPath . DS . 'DroidSans-Bold.ttf');
}

downloadJquery();
downloadJqueryUi();
downloadJqueryTagIt();
downloadMousetrap();
downloadFonts();

require_once 'upgrade.php';
