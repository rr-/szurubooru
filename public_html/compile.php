<?php
define('DS', DIRECTORY_SEPARATOR);
require_once __DIR__ . DS . '..' . DS . 'vendor' . DS . 'autoload.php';

class Compressor
{
	public static function css($content)
	{
		return CssMin::minify($content);
	}

	public static function js($content)
	{
		return JSMin::minify($content);
	}

	public static function html($html)
	{
		$illegalTags = ['script', 'link', 'textarea', 'pre'];
		$chunks = preg_split( '/(<(' . join('|', $illegalTags) . ')(?:\/|.*?\/\2)>)/ms', $html, -1,
		PREG_SPLIT_DELIM_CAPTURE);
		$buffer = '';
		foreach ($chunks as $chunk)
		{
			if (in_array($chunk, $illegalTags))
				continue;

			if (preg_match('/^<(' . join('|', $illegalTags) . ')/', $chunk))
			{
				$buffer .= $chunk;
				continue;
			}

			# remove new lines & tabs
			$chunk = preg_replace( '/[\\n\\r\\t]+/', ' ', $chunk);
			# remove extra whitespace
			$chunk = preg_replace( '/\\s{2,}/', ' ', $chunk);
			# remove inter-tag whitespace
			$chunk = preg_replace( '/>\\s</', '><', $chunk);
			# remove CSS & JS comments
			$chunk = preg_replace( '/\\/\\*.*?\\*\\//i', '', $chunk);
			$buffer .= $chunk;
		}
		return $buffer;
	}
}

class IndexBuilder
{
	public static function build()
	{
		$html = file_get_contents(__DIR__ . DS . 'index.html');
		self::includeTemplates($html);
		self::minifyScripts($html);
		self::minifyStylesheets($html);
		return $html;
	}

	private static function injectBody(&$html, $text)
	{
		$html = str_replace('</body>', $text . '</body>', $html);
	}

	private static function injectHead(&$html, $text)
	{
		$html = str_replace('</head>', $text . '</head>', $html);
	}

	private static function minifyScripts(&$html)
	{
		$scriptsToMinify = [];

		$html = preg_replace_callback(
			'/<script[^>]*src="([^"]+)"[^>]*><\/script>/',
			function($matches) use (&$scriptsToMinify)
			{
				$scriptPath = $matches[1];
				if (substr($scriptPath, 0, 2) == '//' or strpos($scriptPath, 'http') !== false)
					return $matches[0];
				$scriptsToMinify []= __DIR__ . DS . $scriptPath;
				return '';
			}, $html);

		$out = '<script type="text/javascript">';
		foreach ($scriptsToMinify as $scriptPath)
			$out .= Compressor::js(file_get_contents($scriptPath));
		$out .= '</script>';
		self::injectBody($html, $out);
	}

	private static function minifyStylesheets(&$html)
	{
		$html = preg_replace_callback(
			'/<link[^>]*href="([^"]+)"[^>]*>/',
			function($matches) use (&$stylesToMinify)
			{
				$stylePath = $matches[1];
				if (substr($stylePath, 0, 2) == '//' or strpos($stylePath, 'http') !== false)
					return $matches[0];
				if (strpos($matches[0], 'css') === false)
					return $matches[0];
				$stylesToMinify []= __DIR__ . DS . $stylePath;
				return '';
			}, $html);

		$out = '<style type="text/css">';
		foreach ($stylesToMinify as $stylePath)
			$out .= Compressor::css(file_get_contents($stylePath));
		$out .= '</style>';
		self::injectHead($html, $out);
	}

	private static function includeTemplates(&$html)
	{
		$templatesToInclude = [];
		foreach (glob(__DIR__ . DS . 'templates' . DS . '*.tpl') as $templatePath)
			$templatesToInclude []= $templatePath;

		$out = '';
		foreach ($templatesToInclude as $templatePath)
		{
			$out .= '<script type="text/template" id="' . str_replace('.tpl', '-template', basename($templatePath)) . '">';
			$out .= Compressor::html(file_get_contents($templatePath));
			$out .= '</script>';
		}
		self::injectBody($html, $out);
	}
}

$compiledPath = __DIR__ . DS . 'index-compiled.html';
$html = IndexBuilder::build();
$html = Compressor::html($html);
file_put_contents($compiledPath, $html);
