<?php
class TextHelperTest extends AbstractTest
{
	public function testEncryption()
	{
		$lengths = [0];
		for ($i = 0; $i < 20; $i ++)
			$lengths []= mt_rand(0, 10000);

		foreach ($lengths as $length)
		{
			$text = '';
			foreach (range(0, $length) as $j)
				$text .= chr(mt_rand(1, 255));

			$this->assert->areEqual($text, TextHelper::decrypt(TextHelper::encrypt($text)));
		}
	}
}
