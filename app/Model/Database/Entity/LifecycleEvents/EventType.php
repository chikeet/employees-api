<?php declare(strict_types = 1);

namespace App\Model\Database\Entity\LifecycleEvents;

/**
 * Entity lifecycle event type.
 */
enum EventType: string
{

	case BEFORE_PERSIST = 'before_persist';
	case BEFORE_DELETE = 'before_delete';

}
