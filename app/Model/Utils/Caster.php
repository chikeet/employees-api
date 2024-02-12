<?php declare(strict_types = 1);

namespace App\Model\Utils;

use App\Model\Exception\Runtime\InvalidStateException;
use Stringable;

final class Caster
{

	public static function toInt(mixed $value): int
	{
		if (is_string($value) || is_int($value) || is_float($value)) {
			return intval($value);
		}

		throw new InvalidStateException('Cannot cast to integer');
	}

	public static function toString(mixed $value): string
	{
		if (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || $value instanceof Stringable || $value === null) {
			return strval($value);
		}

		throw new InvalidStateException('Cannot cast to string');
	}

}
