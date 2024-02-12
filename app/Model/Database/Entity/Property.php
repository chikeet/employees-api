<?php declare(strict_types = 1);

namespace App\Model\Database\Entity;

use App\Model\Database\Type\XmlType;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly final class Property
{

	public function __construct(
		private XmlType $type,
		private bool $isNullable = false,
		private bool $isUnique = false,
	)
	{
	}

	public function getType(): XmlType
	{
		return $this->type;
	}

	public function isNullable(): bool
	{
		return $this->isNullable;
	}

	public function isUnique(): bool
	{
		return $this->isUnique;
	}

}
