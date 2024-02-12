<?php declare(strict_types = 1);

namespace App\Model\Database\Entity\Enum;

/**
 * Gender type.
 */
enum Gender: string
{

	case MALE = 'm';
	case FEMALE = 'f';

}
