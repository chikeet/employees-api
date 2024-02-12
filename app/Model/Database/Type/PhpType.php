<?php declare(strict_types = 1);

namespace App\Model\Database\Type;

use App\Model\Database\Entity\Enum\Gender;
use App\Model\Utils\DateTime;

/**
 * PHP types used for conversion to XML.
 */
enum PhpType: string
{

	case BOOLEAN = 'bool';
	case INTEGER = 'int';
	case STRING = 'string';
	case DATETIME = DateTime::class;
	case GENDER = Gender::class;

}
