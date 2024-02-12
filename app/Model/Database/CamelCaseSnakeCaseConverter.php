<?php declare(strict_types = 1);

namespace App\Model\Database;

/**
 * Simple converter of camelCase to snake_case.
 * Used for conversion between entity class name and xml file basename
 * and between entity property names and XML property element names.
 */
final class CamelCaseSnakeCaseConverter // TODO @kreizlova: add test
{

	public static function convertCamelCaseToSnakeCase(string $input): string
	{
		return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
	}

	public static function convertSnakeCaseToCamelCase(string $input): string
	{
		return lcfirst(str_replace('_', '', ucwords($input, '_')));
	}

}
