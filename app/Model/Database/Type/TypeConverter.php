<?php declare(strict_types = 1);

namespace App\Model\Database\Type;

use App\Model\Database\Entity\Enum\Gender;
use App\Model\Database\Entity\Property;
use App\Model\Exception\Logic\NotImplementedException;
use App\Model\Exception\Runtime\TypeConversionNullabilityMismatchException;
use App\Model\Exception\Runtime\TypeConversionPhpToXmlException;
use App\Model\Exception\Runtime\TypeConversionXmlToPhpException;
use App\Model\Utils\Caster;
use App\Model\Utils\DateTime;
use DateTimeInterface;
use ReflectionType;

/**
 * Converter between PHP and XML types.
 */
class TypeConverter // TODO @kreizlova: add test
{

	 // TODO @improve: add more types if needed

	public const array ALLOWED_CONVERSIONS_FROM_XML_TO_PHP = [
		XmlType::BOOLEAN->value => [
			PhpType::BOOLEAN->value,
		],
		XmlType::INTEGER->value => [
			PhpType::INTEGER->value,
		],
		XmlType::STRING->value => [
			PhpType::STRING->value,
			PhpType::GENDER->value,
		],
		XmlType::DATETIME->value => [
			PhpType::DATETIME->value,
		],
	];

	public const array ALLOWED_CONVERSIONS_FROM_PHP_TO_XML = [
		PhpType::BOOLEAN->value => [
			XmlType::BOOLEAN->value,
		],
		PhpType::INTEGER->value => [
			XmlType::INTEGER->value,
		],
		PhpType::STRING->value => [
			XmlType::STRING->value,
		],
		PhpType::DATETIME->value => [
			XmlType::DATETIME->value,
		],
		PhpType::GENDER->value => [
			XmlType::STRING->value,
		],
	];

	/**
	 * General converter from XML to PHP.
	 *
	 * @throws TypeConversionNullabilityMismatchException when one of the types is nullable and the other is not.
	 * @throws NotImplementedException when conversion between these two types is not implemented.
	 * @throws TypeConversionXmlToPhpException from self::convertToPhpDateTime
	 */
	public static function convertFromXmlToPhp(
		string $rawValue,
		Property $propertyAttribute,
		ReflectionType $propertyType,
	): mixed
	{
		self::checkNullabilityMatch($propertyAttribute, $propertyType);

		if (self::isAllowedConversionFromXmlToPhp($propertyAttribute, $propertyType) === false) {
			self::handleNotImplementedXmlToPhp($propertyAttribute, $propertyType, __METHOD__);
		}

		return match (self::formatPropertyType($propertyType)) {
			PhpType::BOOLEAN->value => self::convertToPhpBoolean($rawValue, $propertyAttribute, $propertyType),
			PhpType::INTEGER->value => self::convertToPhpInteger($rawValue, $propertyType),
			PhpType::STRING->value => self::convertToPhpString($rawValue, $propertyType),
			PhpType::DATETIME->value => self::convertToPhpDateTime($rawValue, $propertyAttribute, $propertyType),
			PhpType::GENDER->value => self::convertToPhpGender($rawValue, $propertyType),
			default => self::handleNotImplementedXmlToPhp($propertyAttribute, $propertyType, __METHOD__),
		};
	}

	/**
	 * General converter from PHP to XML.
	 *
	 * @throws TypeConversionNullabilityMismatchException when one of the types is nullable and the other is not.
	 * @throws NotImplementedException when conversion between these two types is not implemented.
	 */
	public static function convertFromPhpToXml(
		mixed $rawValue,
		Property $propertyAttribute,
		ReflectionType $propertyType,
	): string
	{
		self::checkNullabilityMatch($propertyAttribute, $propertyType);

		if (self::isAllowedConversionFromPhpToXml($propertyAttribute, $propertyType) === false) {
			self::handleNotImplementedPhpToXml($propertyType, $propertyAttribute, __METHOD__);
		}

		return match ($propertyAttribute->getType()->value) {
			XmlType::BOOLEAN->value => self::convertToXmlBoolean($rawValue, $propertyAttribute, $propertyType),
			XmlType::INTEGER->value => self::convertToXmlInteger($rawValue, $propertyAttribute, $propertyType),
			XmlType::STRING->value => self::convertToXmlString($rawValue, $propertyAttribute),
			XmlType::DATETIME->value => self::convertToXmlDateTime($rawValue, $propertyAttribute, $propertyType),
		};
	}

	private static function isAllowedConversionFromXmlToPhp(Property $propertyAttribute, ReflectionType $propertyType): bool
	{
		if (array_key_exists($propertyAttribute->getType()->value, self::ALLOWED_CONVERSIONS_FROM_XML_TO_PHP) === false) {
			return false;
		}

		$allowedTargetTypes = self::ALLOWED_CONVERSIONS_FROM_XML_TO_PHP[$propertyAttribute->getType()->value];

		return in_array(self::formatPropertyType($propertyType), $allowedTargetTypes, true);
	}

	private static function isAllowedConversionFromPhpToXml(Property $propertyAttribute, ReflectionType $propertyType): bool
	{
		if (array_key_exists(self::formatPropertyType($propertyType), self::ALLOWED_CONVERSIONS_FROM_PHP_TO_XML) === false) {
			return false;
		}

		$allowedTargetTypes = self::ALLOWED_CONVERSIONS_FROM_PHP_TO_XML[self::formatPropertyType($propertyType)];

		return in_array($propertyAttribute->getType()->value, $allowedTargetTypes, true);
	}

	/**
	 * Converter to PHP bool.
	 */
	private static function convertToPhpBoolean(
		string $rawValue,
		Property $propertyAttribute,
		ReflectionType $propertyType,
	): ?bool
	{
		if ($propertyType->allowsNull() && $rawValue === '') {
			return null;
		}

		return match ($rawValue) {
			'true' => true,
			'false' => false,
			default => self::handleNotImplementedXmlToPhp($propertyAttribute, $propertyType, __METHOD__),
		};
	}

	/**
	 * Converter to PHP integer.
	 */
	private static function convertToPhpInteger(
		string $rawValue,
		ReflectionType $propertyType,
	): ?int
	{
		if ($propertyType->allowsNull() && $rawValue === '') {
			return null;
		}

		return (int) $rawValue;
	}

	/**
	 * Converter to PHP string.
	 */
	private static function convertToPhpString(
		string $rawValue,
		ReflectionType $propertyType,
	): ?string
	{
		if ($propertyType->allowsNull() && $rawValue === '') {
			return null;
		}

		return $rawValue;
	}

	/**
	 * Converter to PHP DateTime class.
	 *
	 * @throws TypeConversionXmlToPhpException on invalid datetime string when target property is not nullable
	 */
	private static function convertToPhpDateTime(
		string $rawValue,
		Property $propertyAttribute,
		ReflectionType $propertyType,
	): ?DateTime
	{
		if ($propertyType->allowsNull() && $rawValue === '') {
			return null;
		}

		$dateTime = DateTime::createFromFormat(DateTimeInterface::ATOM, $rawValue);
		$dateTime = $dateTime === false
			? null
			: $dateTime;

		if ($dateTime === null && $propertyType->allowsNull() === false) {
			throw new TypeConversionXmlToPhpException($rawValue, $propertyAttribute, $propertyType);
		}

		return $dateTime;
	}

	/**
	 * Converter to PHP Gender enum.
	 */
	private static function convertToPhpGender(
		string $rawValue,
		ReflectionType $propertyType,
	): ?Gender
	{
		return $propertyType->allowsNull()
			? Gender::tryFrom($rawValue)
			: Gender::from($rawValue);
	}

	/**
	 * Converter to XML bool.
	 */
	private static function convertToXmlBoolean(
		mixed $rawValue,
		Property $propertyAttribute,
		ReflectionType $propertyType,
	): string
	{
		if (is_bool($rawValue) === false && $rawValue !== null) {
			throw new TypeConversionPhpToXmlException($rawValue, $propertyAttribute, $propertyType);
		}

		if ($propertyAttribute->isNullable() && $rawValue === null) {
			return '';
		}

		return $rawValue === true ? 'true' : 'false';
	}

	/**
	 * Converter to XML integer.
	 */
	private static function convertToXmlInteger(
		mixed $rawValue,
		Property $propertyAttribute,
		ReflectionType $propertyType,
	): string
	{
		if (is_numeric($rawValue) === false && $rawValue !== null) {
			throw new TypeConversionPhpToXmlException($rawValue, $propertyAttribute, $propertyType);
		}

		if ($propertyAttribute->isNullable() && $rawValue === null) {
			return '';
		}

		return (string) (int) $rawValue;
	}

	/**
	 * Converter to XML string.
	 */
	private static function convertToXmlString(
		mixed $rawValue,
		Property $propertyAttribute,
	): string
	{
		if ($propertyAttribute->isNullable() && $rawValue === null) {
			return '';
		}

		if ($rawValue instanceof Gender) {
			$rawValue = $rawValue->value;
		}

		return Caster::toString($rawValue);
	}

	/**
	 * Converter to XML datetime.
	 */
	private static function convertToXmlDateTime(
		mixed $rawValue,
		Property $propertyAttribute,
		ReflectionType $propertyType,
	): string
	{
		if ($propertyAttribute->isNullable() && $rawValue === null) {
			return '';
		}

		if (($rawValue instanceof DateTimeInterface) === false) {
			throw new TypeConversionPhpToXmlException($rawValue, $propertyAttribute, $propertyType);
		}

		return $rawValue->format(DateTimeInterface::ATOM);
	}

	private static function handleNotImplementedXmlToPhp(
		Property $from,
		ReflectionType $to,
		string $method,
	): void
	{
		throw new NotImplementedException(
			sprintf(
				'Conversion from %s to %s is not implemented in %s.',
				$from->getType()->value,
				$to,
				$method,
			),
		);
	}

	private static function handleNotImplementedPhpToXml(
		ReflectionType $from,
		Property $to,
		string $method,
	): void
	{
		throw new NotImplementedException(
			sprintf(
				'Conversion from %s to %s is not implemented in %s.',
				$from,
				$to->getType()->value,
				$method,
			),
		);
	}

	private static function checkNullabilityMatch(
		Property $propertyAttribute,
		ReflectionType $propertyType,
	): void
	{
		if ($propertyAttribute->isNullable() !== $propertyType->allowsNull()) {
			throw new TypeConversionNullabilityMismatchException(
				'Nullability mismatch: entity property and its Property attribute must be both nullable or non-nullable.',
			);
		}
	}

	private static function formatPropertyType(ReflectionType $propertyType): string
	{
		return ltrim(Caster::toString($propertyType), '?');
	}

}
