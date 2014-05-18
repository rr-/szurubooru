<?php
class RecursiveSerializer implements ISerializable
{
	public function __construct($input)
	{
		$this->input = $input;
	}

	public function serializeToArray()
	{
		return
		$output = $this->traverse($this->input);
	}

	private function traverse($input)
	{
		if (is_array($input))
		{
			foreach ($input as $key => $val)
			{
				$input[$key] = $this->traverse($input[$key]);
			}
			return $input;
		}
		elseif ($input instanceof ISerializable)
		{
			return $input->serializeToArray();
		}
		elseif ($input instanceof Exception)
		{
			return $this->serializeException($input);
		}
		elseif (is_object($input))
		{
			foreach ($input as $key => $val)
			{
				$input->$key = $this->traverse($input->$key);
			}
			return $input;
		}
		return $input;
	}

	private function serializePost(PostEntity $post)
	{
		return
		[
			'name' => $post->getName(),
		];
	}

	private function serializeException(Exception $exception)
	{
		return
		[
			'message' => $exception->getMessage(),
			'trace' => explode("\n", $exception->getTraceAsString())
		];
	}
}
