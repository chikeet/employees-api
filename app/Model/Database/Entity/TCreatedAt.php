<?php declare(strict_types = 1);

namespace App\Model\Database\Entity;

use App\Model\Database\Type\XmlType;
use App\Model\Utils\DateTime;

trait TCreatedAt
{

	#[Property(type: XmlType::DATETIME, isNullable: false)]
	protected DateTime $createdAt;

	public function getCreatedAt(): DateTime
	{
		return $this->createdAt;
	}

	public function setCreatedAt(): void
	{
		$this->createdAt = new DateTime();
	}

}
