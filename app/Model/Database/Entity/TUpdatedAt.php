<?php declare(strict_types = 1);

namespace App\Model\Database\Entity;

use App\Model\Database\Entity\LifecycleEvents\EventType;
use App\Model\Database\Entity\LifecycleEvents\LifecycleEventsListener;
use App\Model\Database\Type\XmlType;
use App\Model\Utils\DateTime;

trait TUpdatedAt
{

	#[Property(type: XmlType::DATETIME, isNullable: true)]
	protected ?DateTime $updatedAt = null;

	public function getUpdatedAt(): ?DateTime
	{
		return $this->updatedAt;
	}


	#[LifecycleEventsListener(type: EventType::BEFORE_PERSIST)]
	public function setUpdatedAt(EventType $eventType): void
	{
		$this->updatedAt = new DateTime();
	}

}
