<?php declare(strict_types = 1);

namespace App\Model\Database\Type;

/**
 * XML types used for conversion to PHP.
 */
enum XmlType: string
{

	case BOOLEAN = 'boolean';
	case INTEGER = 'integer';
	case STRING = 'string';
	case DATETIME = 'dateTime';

}
