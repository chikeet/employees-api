<?php declare(strict_types = 1);

namespace App\Model\Database;

use App\Model\Database\Entity\EntityReflection;
use App\Model\Database\Entity\Property;
use App\Model\Exception\Logic\InvalidEntityException;
use ReflectionException;

/**
 * Generator of XSD schema based on @see AbstractEntity properties
 * that have @see Property attribute.
 */
class XsdSchemaGenerator
{

	/**
	 * Generates content of XSD schema file from properties of @see AbstractEntity
	 * that have @see Property attribute.
	 *
	 * @param class-string $entityClass
	 * @throws ReflectionException when entity reflection cannot be created.
	 * @throws InvalidEntityException from EntityReflection::getPropertyAttributes
	 */
	public static function generateXsdSchema(string $entityClass): string // TODO @kreizlova: add test - check if generated file structure is correct
	{
		$entityReflection = new EntityReflection($entityClass);
		$propertyAttributes = $entityReflection->getPropertyAttributes();

		$xsdSchema = self::generateXsdSchemaHeader()
					. self::generateRowElementHeader();

		foreach ($propertyAttributes as $propertyName => $propertyAttribute) {
			$propertySchema = self::generatePropertyElement($propertyName, $propertyAttribute);
			$xsdSchema .= $propertySchema . PHP_EOL;
		}

		$xsdSchema .= self::generateRowElementFooter()
					. self::generateDataElementHeader()
					. self::generateUniqueConstraints($propertyAttributes)
					. self::generateDataElementFooter()
					. self::generateXsdSchemaFooter();

		return $xsdSchema;
	}

	/**
	 * Generates XSD element for the property.
	 */
	private static function generatePropertyElement(string $propertyName, Property $propertyAttribute): string
	{
		$propertyXmlName = XmlMapper::getXmlPropertyName($propertyName);

		return sprintf(
			'<xs:element name="%s" type="xs:%s"%s/>',
			$propertyXmlName,
			$propertyAttribute->getType()->value,
			($propertyAttribute->isNullable() ? ' nillable="true"' : ''),
		);
	}

	/**
	 * Generates unique constraints for the unique properties.
	 *
	 * @param array<string, Property> $propertyAttributes
	 */
	private static function generateUniqueConstraints(array $propertyAttributes): string
	{
		if (count(array_filter($propertyAttributes, fn (Property $property): bool => $property->isUnique())) === 0) {
			return '';
		}

		$constraintsSchema = '';

		foreach ($propertyAttributes as $propertyName => $propertyAttribute) {
			if ($propertyAttribute->isUnique() === false) {
				continue;
			}

			$propertyXmlName = XmlMapper::getXmlPropertyName($propertyName);
			$constraintsSchema .= sprintf('<xs:unique name="unique_%s">' . PHP_EOL, $propertyXmlName)
					. sprintf('<xs:selector xpath="%s"/>' . PHP_EOL, XmlDriver::XML_ROW_ELEMENT)
					. sprintf('<xs:field xpath="%s"/>' . PHP_EOL, $propertyXmlName)
					. '</xs:unique>' . PHP_EOL;
		}

		return $constraintsSchema;
	}

	private static function generateRowElementHeader(): string
	{
		return sprintf('<!-- Define complex type for the %s element -->' . PHP_EOL, XmlDriver::XML_ROW_ELEMENT)
			. sprintf('<xs:element name="%s">' . PHP_EOL, XmlDriver::XML_ROW_ELEMENT)
			. '<xs:complexType>' . PHP_EOL
			. '<xs:sequence>' . PHP_EOL;
	}

	private static function generateRowElementFooter(): string
	{
		return '</xs:sequence>' . PHP_EOL
			. '</xs:complexType>' . PHP_EOL
			. '</xs:element>' . PHP_EOL . PHP_EOL;
	}

	private static function generateDataElementHeader(): string
	{
		return sprintf('<!-- Define complex type for the %s element -->' . PHP_EOL, XmlDriver::XML_DATA_WRAPPER_ELEMENT)
			. sprintf('<xs:element name="%s">' . PHP_EOL, XmlDriver::XML_DATA_WRAPPER_ELEMENT)
			. '<xs:complexType>' . PHP_EOL
			. '<xs:sequence>' . PHP_EOL
			. sprintf('<xs:element ref="%s" minOccurs="0" maxOccurs="unbounded"/>' . PHP_EOL, XmlDriver::XML_ROW_ELEMENT)
			. '</xs:sequence>' . PHP_EOL
		. '</xs:complexType>' . PHP_EOL;
	}

	private static function generateDataElementFooter(): string
	{
		return '</xs:element>' . PHP_EOL . PHP_EOL;
	}

	private static function generateXsdSchemaHeader(): string
	{
		return '<?xml version="1.0"?>' . PHP_EOL
			. '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">' . PHP_EOL . PHP_EOL;
	}

	private static function generateXsdSchemaFooter(): string
	{
		return '</xs:schema>' . PHP_EOL;
	}

}
