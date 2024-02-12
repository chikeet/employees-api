<?php declare(strict_types = 1);

namespace App\Model\Database;

use function basename;
use function str_replace;

class XmlMapper // TODO @kreizlova: add test
{

	/**
	 * Returns base name (without extension) of the XML file for the entity class.
	 *
	 * @param class-string $entityClass
	 */
	public static function getXmlFileName(string $entityClass): string
	{
		$class = basename(str_replace('\\', '/', $entityClass));

		return CamelCaseSnakeCaseConverter::convertCamelCaseToSnakeCase(lcfirst($class));
	}

	/**
	 * Returns name of XML property element corresponding to given entity property name.
	 */
	public static function getXmlPropertyName(string $phpPropertyName): string
	{
		return CamelCaseSnakeCaseConverter::convertCamelCaseToSnakeCase($phpPropertyName);
	}

}
