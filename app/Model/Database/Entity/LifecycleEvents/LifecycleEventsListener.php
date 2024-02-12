<?php declare(strict_types = 1);

namespace App\Model\Database\Entity\LifecycleEvents;

use Attribute;

/**
 * Attribute for entity methods that should be called on entity lifecycle events.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class LifecycleEventsListener
{

	public function __construct(
		private EventType $type
	)
	{
	}

	public function getType(): EventType
	{
		return $this->type;
	}

}
