<?php declare(strict_types = 1);

namespace App\Model\Exception\Runtime;

use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\RuntimeException;
use Throwable;

final class EntityAlreadyAttachedException extends RuntimeException implements IXmlDriverException
{

	private const string MESSAGE = 'Entity attaching failed. Newly attached entity was already found in identity map.';

	public function __construct(
		string $message = self::MESSAGE,
		int $code = 0,
		?Throwable $previous = null,
	)
	{
		parent::__construct($message, $code, $previous);
	}

}
