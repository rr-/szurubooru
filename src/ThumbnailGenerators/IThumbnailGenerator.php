<?php
interface IThumbnailGenerator
{
	public function generateFromFile($srcPath, $dstPath, $width, $height);
}
