<?php declare(strict_types = 1);

namespace App\Model\Database\Entity;

use App\Model\Database\Entity\LifecycleEvents\EventType;
use App\Model\Database\Repository\XmlDataDto;
use App\Model\Exception\Logic\InvalidCallbackException;
use App\Model\Exception\Logic\InvalidEntityException;
use ReflectionException;

abstract class AbstractEntity
{

	use TId;

	/**
	 * Create an instance of static from preprocessed XML data.
	 * Use constructor and then set rest of the properties directly if needed (when there are any additional non-constructor properties).
	 *
	 * @throws ReflectionException from EntityReflection::__construct
	 * @throws InvalidEntityException from EntityReflection::getPropertiesWithPropertyAttribute
	 */
	public static function fromXmlData(XmlDataDto $xmlData): static
	{
		$entityReflection = new EntityReflection(static::class);
		$xmlProperties = $entityReflection->getPropertiesWithPropertyAttribute();
		$reflectionClass = $entityReflection->getReflection();

		/** @var static $entity */
		$entity = $reflectionClass->newInstanceWithoutConstructor();

		foreach ($xmlProperties as $propertyName => $reflectionProperty) {

			if ($xmlData->offsetExists($propertyName)) {
				$reflectionProperty->setAccessible(true); // Make the property accessible
				$reflectionProperty->setValue($entity, $xmlData->offsetGet($propertyName));
			}
		}

		return $entity;
	}

	/**
	 * Ensures calling entity methods with @see	LifecycleEventsListener attribute.
	 *
	 * @throws ReflectionException when entity reflection cannot be created.
	 * @throws InvalidCallbackException should never happen
	 */
	public function processLifecycleEvent(EventType $eventType): void
	{
		$reflection = new EntityReflection(static::class);

		foreach ($reflection->getLifecycleEventListeners() as $listenerMethods) {

			foreach ($listenerMethods as $methodName) {

				$listenerCallback = [$this, $methodName];

				if (is_callable($listenerCallback) === false) {
					throw new InvalidCallbackException(
						sprintf(
							'Lifecycyle event listener method %s::%s is not a valid callable.',
							static::class,
							$methodName,
						),
					);
				}

				call_user_func($listenerCallback, $eventType);
			}
		}
	}

}
