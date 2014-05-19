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

	public function testToIntegerOrNull()
	{
		$this->assert->areEqual(1, TextHelper::toIntegerOrNull(1));
		$this->assert->areEqual(1, TextHelper::toIntegerOrNull('1'));
		$this->assert->areEqual(-1, TextHelper::toIntegerOrNull(-1));
		$this->assert->areEqual(-2, TextHelper::toIntegerOrNull('-2'));
		$this->assert->areEqual(0, TextHelper::toIntegerOrNull(0));
		$this->assert->areEqual(0, TextHelper::toIntegerOrNull('0'));
		$this->assert->isNull(TextHelper::toIntegerOrNull('rubbish'));
		$this->assert->isNull(TextHelper::toIntegerOrNull('1e1'));
		$this->assert->isNull(TextHelper::toIntegerOrNull('1.7'));
		$this->assert->isNull(TextHelper::toIntegerOrNull(true));
		$this->assert->isNull(TextHelper::toIntegerOrNull(false));
		$this->assert->isNull(TextHelper::toIntegerOrNull(null));
	}

	public function testToBooleanOrNull()
	{
		$this->assert->isTrue(TextHelper::toBooleanOrNull(1));
		$this->assert->isTrue(TextHelper::toBooleanOrNull('1'));
		$this->assert->isTrue(TextHelper::toBooleanOrNull('yes'));
		$this->assert->isTrue(TextHelper::toBooleanOrNull('y'));
		$this->assert->isTrue(TextHelper::toBooleanOrNull('on'));
		$this->assert->isTrue(TextHelper::toBooleanOrNull('TrUe'));
		$this->assert->isTrue(TextHelper::toBooleanOrNull('true'));
		$this->assert->isTrue(TextHelper::toBooleanOrNull(true));
		$this->assert->isFalse(TextHelper::toBooleanOrNull(0));
		$this->assert->isFalse(TextHelper::toBooleanOrNull('0'));
		$this->assert->isFalse(TextHelper::toBooleanOrNull('no'));
		$this->assert->isFalse(TextHelper::toBooleanOrNull('n'));
		$this->assert->isFalse(TextHelper::toBooleanOrNull('off'));
		$this->assert->isFalse(TextHelper::toBooleanOrNull('FaLsE'));
		$this->assert->isFalse(TextHelper::toBooleanOrNull('false'));
		$this->assert->isFalse(TextHelper::toBooleanOrNull(false));
		$this->assert->isNotNull(TextHelper::toBooleanOrNull(false));
		$this->assert->isNull(TextHelper::toBooleanOrNull(2));
		$this->assert->isNull(TextHelper::toBooleanOrNull('2'));
		$this->assert->isNull(TextHelper::toBooleanOrNull('rubbish'));
		$this->assert->isNull(TextHelper::toBooleanOrNull(null));
	}

	public function testAssert()
	{
		$this->assert->isNull(null);
		$this->assert->isNotNull(false);
		$this->assert->isFalse(false);
		$this->assert->areNotEqual(true, '1');
		$this->assert->isTrue(true);
		$this->assert->areNotEqual(false, null);
	}
}
