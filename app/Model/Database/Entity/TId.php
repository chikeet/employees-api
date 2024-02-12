<?php declare(strict_types = 1);

namespace App\Model\Database\Entity;

use App\Model\Database\Type\XmlType;
use App\Model\Exception\Runtime\InvalidStateException;

trait TId
{

	#[Property(type: XmlType::INTEGER, isNullable: false, isUnique: true)]
	private int $id;

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		if (isset($this->id)) {
			throw new InvalidStateException('Entity ID is already set.');
		}

		$this->id = $id;
	}

	public function hasId(): bool
	{
		return isset($this->id);
	}

	public function __clone()
	{
		unset($this->id);
	}

}
