<?php declare(strict_types = 1);

namespace App\Model\Exception\Logic;

use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\LogicException;
use Throwable;

final class XmlElementDoesNotExistException extends LogicException implements IXmlDriverException
{

	private const string MESSAGE = 'XML element does not exist.';

	public function __construct(
		string $message = self::MESSAGE,
		int $code = 0,
		?Throwable $previous = null,
	)
	{
		parent::__construct($message, $code, $previous);
	}

}
