<?php declare(strict_types = 1);

namespace App\Model\Exception\Logic;

use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\LogicException;
use Throwable;

final class PropertyDoesNotExistException extends LogicException implements IXmlDriverException
{

	public function __construct(
		string $entityClass,
		string $propertyName,
		int $code = 0,
		?Throwable $previous = null,
	)
	{
		$message = sprintf(
			'Entity property %s::$%s does not exist. Cannot get property attributes.',
			$entityClass,
			$propertyName,
		);

		parent::__construct($message, $code, $previous);
	}

}
