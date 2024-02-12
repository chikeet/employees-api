<?php declare(strict_types = 1);

namespace App\Model\Exception\Runtime;

use App\Model\Database\Entity\Property;
use ReflectionType;
use Throwable;

final class TypeConversionXmlToPhpException extends TypeConversionException
{

	public function __construct(
		mixed $rawValue,
		Property $propertyAttribute,
		ReflectionType $propertyType,
		int $code = 0,
		?Throwable $previous = null,
	)
	{
		$message = $this->constructMessage($rawValue, $propertyAttribute, $propertyType, 'XML to PHP');

		parent::__construct($message, $code, $previous);
	}

}
