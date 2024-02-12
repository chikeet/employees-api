<?php declare(strict_types = 1);

namespace App\Model\Exception\Runtime;

use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\RuntimeException;
use Throwable;

final class PropertyValueNotSetException extends RuntimeException implements IXmlDriverException
{

	public function __construct(string $className, string $propertyName, int $code = 0, ?Throwable $previous = null)
	{
		$message = sprintf(
			'Property %s of entity %s is not nullable, has no default value and property value in XML is not set.',
			$propertyName,
			$className,
		);

		parent::__construct($message, $code, $previous);
	}

}
