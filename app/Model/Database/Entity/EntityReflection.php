<?php declare(strict_types = 1);

namespace App\Model\Database\Entity;

use App\Model\Database\Entity\LifecycleEvents\EventType;
use App\Model\Database\Entity\LifecycleEvents\LifecycleEventsListener;
use App\Model\Exception\Logic\ClassIsNoEntityException;
use App\Model\Exception\Logic\InvalidEntityException;
use App\Model\Exception\Logic\PropertyDoesNotExistException;
use App\Model\Exception\Logic\PropertyHasNoTypeException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use ReflectionType;

final class EntityReflection
{

	/** @var ReflectionClass<AbstractEntity> */
	private ReflectionClass $reflection;

	/**
	 * Reflection properties, indexed by property name.
	 *
	 * @var array<ReflectionProperty>
	 */
	private array $properties;

	/**
	 * Properties with @see Property attribute, indexed by property name.
	 *
	 * @var array<Property>
	 */
	private array $propertyAttributes;

	/**
	 * Methods with @see LifecycleEventListener attribute, indexed by EventType value.
	 * E.g. [before_persist => [method1, method2], before_delete => [method1, method3]].
	 *
	 * @var array<string, array<string>>
	 */
	private array $lifecycleEventListeners;

	/**
	 * @param class-string $entityClass
	 * @throws ReflectionException Cannot create entity class reflection.
	 * @throws ClassIsNoEntityException Given class is not a subclass of AbstractEntity.
	 */
	public function __construct(
		private readonly string $entityClass,
	)
	{
		assert(class_exists($entityClass));
		$reflection = new ReflectionClass($this->entityClass);

		if ($reflection->isSubclassOf(AbstractEntity::class) === false) {
			throw new ClassIsNoEntityException(
				sprintf(
					'Cannot create %s for an class that is not a subclass of %s.',
					self::class,
					AbstractEntity::class,
				),
			);
		}

		$this->reflection = $reflection;
	}

	/**
	 * @return ReflectionClass<AbstractEntity>
	 */
	public function getReflection(): ReflectionClass
	{
		return $this->reflection;
	}

	/**
	 * Returns array of ReflectionProperty for all entity properties.
	 *
	 * @return array<string, ReflectionProperty> Key is property name.
	 */
	public function getProperties(): array
	{
		if (!isset($this->properties)) {
			$this->loadProperties();
		}

		return $this->properties;
	}

	/**
	 * @return array<string, Property> Key is property name, value is Property attribute.
	 * @throws InvalidEntityException from self::loadPropertyAttributes
	 */
	public function getPropertyAttributes(): array
	{
		if (!isset($this->propertyAttributes)) {
			$this->loadPropertyAttributes();
		}

		return $this->propertyAttributes;
	}

	/**
	 * Returns array of ReflectionProperty for entity properties that have @see Property attribute.
	 *
	 * @return array<string, ReflectionProperty> Key is property name, value is ReflectionProperty.
	 * @throws InvalidEntityException from self::getPropertyAttributes
	 */
	public function getPropertiesWithPropertyAttribute(): array
	{
		$propertyAttributes = $this->getPropertyAttributes();
		$properties = [];

		foreach ($this->getProperties() as $reflectionProperty) {
			if (array_key_exists($reflectionProperty->name, $propertyAttributes)) {
				$properties[$reflectionProperty->name] = $reflectionProperty;
			}
		}

		return $properties;
	}

	/**
	 * @throws PropertyDoesNotExistException when property not found.
	 */
	public function getProperty(string $propertyName): ReflectionProperty
	{
		$properties = $this->getProperties();

		if (!array_key_exists($propertyName, $properties)) {
			throw new PropertyDoesNotExistException($this->entityClass, $propertyName);
		}

		return $properties[$propertyName];
	}

	/**
	 * @throws PropertyHasNoTypeException when property has no type. Should never happen because of to phpStan.
	 */
	public function getPropertyType(string $propertyName): ReflectionType
	{
		$type = $this->getProperty($propertyName)->getType();

		if ($type === null) {
			throw new PropertyHasNoTypeException($this->entityClass, $propertyName);
		}

		return $type;
	}

	public function hasPropertyDefaultValue(string $propertyName): bool
	{
		return $this->getProperty($propertyName)->hasDefaultValue();
	}

	public function getPropertyDefaultValue(string $propertyName): mixed
	{
		return $this->getProperty($propertyName)->getDefaultValue();
	}


	/**
	 * Returns the @see Property attribute for the given property.
	 *
	 * @throws PropertyDoesNotExistException Property has no Property attribute.
	 * @throws InvalidEntityException from self::getPropertyAttributes
	 */
	public function getPropertyAttribute(string $propertyName): Property
	{
		$this->getPropertyAttributes(); // ensure Property attributes loading

		if (!array_key_exists($propertyName, $this->propertyAttributes)) {
			throw new PropertyDoesNotExistException($this->entityClass, $propertyName);
		}

		return $this->propertyAttributes[$propertyName];
	}

	/**
	 * Returns array of arrays of methods with @see LifecycleEventListener attribute, indexed by EventType value.
	 *
	 * @return array<string, array<string>>
	 */
	public function getLifecycleEventListeners(): array
	{
		if (!isset($this->lifecycleEventListeners)) {
			$this->loadLifecycleEventListeners();
		}

		return $this->lifecycleEventListeners;
	}

	private function loadProperties(): void
	{
		$this->properties = [];

		// get properties declared in parent classes
		$parentClass = $this->reflection->getParentClass();

		while ($parentClass) {
			$parentProperties = $parentClass->getProperties();

			foreach ($parentProperties as $parentProperty) {
				$this->properties[$parentProperty->getName()] = $parentProperty;
			}

			$parentClass = $parentClass->getParentClass();
		}

		// get class's own properties - rewrite parent properties if present
		foreach ($this->reflection->getProperties() as $reflectionProperty) {
			$this->properties[$reflectionProperty->getName()] = $reflectionProperty;
		}
	}

	/**
	 * @throws InvalidEntityException when entity property has multiple attributes of class Property.
	 */
	private function loadPropertyAttributes(): void
	{
		$this->propertyAttributes = [];

		foreach ($this->getProperties() as $propertyName => $reflectionProperty) {
			$propertyAttributes = $reflectionProperty->getAttributes(Property::class);

			if (count($propertyAttributes) === 0) { // is not entity Property
				continue;
			}

			if (count($propertyAttributes) > 1) {
				throw new InvalidEntityException(
					sprintf(
						'Entity property %s::$%s cannot have multiple attributes of class %s.',
						$this->entityClass,
						$propertyName,
						Property::class,
					),
				);
			}

			/** @var ReflectionAttribute<Property> $reflectionAttribute */
			$reflectionAttribute = reset($propertyAttributes);
			$this->propertyAttributes[$propertyName] = $reflectionAttribute->newInstance();
		}
	}

	private function loadLifecycleEventListeners(): void
	{
		$this->lifecycleEventListeners = [];

		foreach (EventType::cases() as $eventType) {
			$this->lifecycleEventListeners[$eventType->value] = [];
		}

		foreach ($this->reflection->getMethods() as $reflectionMethod) {
			$methodAttributes = $reflectionMethod->getAttributes(LifecycleEventsListener::class);

			if (count($methodAttributes) === 0) { // not a listener
				continue;
			}

			$methodName = $reflectionMethod->name;

			/** @var LifecycleEventsListener $attribute */
			foreach ($methodAttributes as $attribute) {
				$this->lifecycleEventListeners[$attribute->newInstance()->getType()->value][] = $methodName;
			}
		}
	}

}
