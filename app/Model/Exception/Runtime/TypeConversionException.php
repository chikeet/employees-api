<?php declare(strict_types = 1);

namespace App\Model\Exception\Runtime;

use App\Model\Database\Entity\Property;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\RuntimeException;
use ReflectionType;

abstract class TypeConversionException extends RuntimeException implements IXmlDriverException
{

	protected function constructMessage(
		mixed $rawValue,
		Property $propertyAttribute,
		ReflectionType $propertyType,
		string $conversionType,
	): string
	{
		if (is_bool($rawValue) || is_int($rawValue) || is_float($rawValue) || is_string($rawValue)) {
			$stringValue = (string) $rawValue;
		} elseif (is_array($rawValue)) {
			$stringValue = 'array';
		} elseif (is_object($rawValue)) {
			$stringValue = 'instance of ' . $rawValue::class;
		} elseif (is_callable($rawValue)) {
			$stringValue = 'callable';
		} elseif ($rawValue === null) {
			$stringValue = 'null';
		}

		$valueMessage = isset($stringValue) ? sprintf(
			'"%s" (%s)',
			$stringValue,
			gettype($rawValue),
		) : sprintf(
			'[other value] (%s)',
			gettype($rawValue),
		);

		return sprintf(
			'Value %s cannot be converted from %s to %s (%s, %s).',
			$valueMessage,
			$propertyAttribute->getType()->value,
			$propertyType,
			$conversionType,
			$propertyType->allowsNull() ? 'nullable' : 'non-nullable',
		);
	}

}
