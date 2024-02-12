<?php declare(strict_types = 1);

namespace App\Model\Database\Repository;

use App\Model\Database\Entity\AbstractEntity;
use App\Model\Database\Entity\EntityReflection;
use App\Model\Database\Entity\LifecycleEvents\EventType;
use App\Model\Database\EntityManager;
use App\Model\Database\Type\TypeConverter;
use App\Model\Database\XmlDriver;
use App\Model\Database\XmlMapper;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Logic\EntityIdNotRegisteredException;
use App\Model\Exception\Logic\EntityXmlFileNotFoundException;
use App\Model\Exception\Logic\InvalidEntityException;
use App\Model\Exception\Logic\InvalidXmlException;
use App\Model\Exception\Logic\NotImplementedException;
use App\Model\Exception\Logic\XmlDataDirNotFoundException;
use App\Model\Exception\Logic\XmlElementDoesNotExistException;
use App\Model\Exception\Runtime\EntityAlreadyAttachedException;
use App\Model\Exception\Runtime\PropertyValueNotSetException;
use App\Model\Exception\Runtime\TypeConversionNullabilityMismatchException;
use App\Model\Exception\Runtime\TypeConversionXmlToPhpException;
use App\Model\Exception\XmlDriverException;
use App\Model\Utils\Caster;
use DOMElement;
use DOMException;
use DOMNode;
use DOMNodeList;
use ReflectionException;

/**
 * Repository implementing base features for CRUD operations with the entities using the @see XmlDriver .
 *
 * @phpstan-template TEntityClass of object
 */
abstract readonly class AbstractRepository
{

	/** @var XmlDriver Driver for low-level XML operations. */
	private XmlDriver $xmlDriver;

	/**
	 * @throws InvalidXmlException from XmlDriver::__construct
	 * @throws ReflectionException from XmlDriver::__construct
	 * @throws EntityXmlFileNotFoundException from XmlDriver::__construct
	 * @throws XmlDataDirNotFoundException from XmlDriver::__construct
	 */
	public function __construct(
		private EntityManager $entityManager,
		string $dataDirPath = __DIR__ . '/../../../../data/xml', // TODO @improve: load from config?
	)
	{
		$this->xmlDriver = new XmlDriver(
			$this->getEntityClass(),
			$dataDirPath,
		);
	}

	/**
	 * Returns single entity found in the XML by given ID or null if not found.
	 *
	 * @throws IXmlDriverException
	 */
	public function find(int $id): ?AbstractEntity
	{
		// try to get entity from identity map
		$entity = $this->entityManager->getEntityById($this->getEntityClass(), $id);

		if ($entity instanceof AbstractEntity) {
			return $entity;
		}

		$entityNode = $this->xmlDriver->getEntityXmlNodeById($id);

		if ($entityNode === null) {
			return null;
		}

		try {
			return $this->createEntityFromXml($entityNode);
		} catch (ReflectionException $e) {
			throw new XmlDriverException(previous: $e);
		}
	}

	/**
	 * Returns single entity found in the XML by given criteria or null if not found.
	 *
	 * @param array<string, string> $criteria
	 * @throws IXmlDriverException
	 */
	public function findOneBy(array $criteria): ?AbstractEntity
	{
		$entityNode = $this->xmlDriver->getEntityXmlNodeByCriteria($criteria);

		if ($entityNode === null) {
			return null;
		}

		try {
			return $this->createEntityFromXml($entityNode);
		} catch (ReflectionException $e) {
			throw new XmlDriverException(previous: $e);
		}
	}

	/**
	 * Returns array of all entities found in the corresponding XML file.
	 *
	 * @return array<AbstractEntity>
	 * @throws IXmlDriverException
	 */
	public function findAll(): array
	{
		$xmlItems = $this->xmlDriver->getAllEntitiesXmlNodes();

		try {
			return $this->createEntitiesFromXml($xmlItems);
		} catch (ReflectionException $e) {
			throw new XmlDriverException(previous: $e);
		}
	}

	/**
	 * Persists new or updated entity into XML according to its state in @ see EntityManager .
	 *
	 * @throws IXmlDriverException
	 */
	public function persist(AbstractEntity $entity): AbstractEntity
	{
		$this->entityManager->attachEntity($entity);

		try {
			$entity->processLifecycleEvent(EventType::BEFORE_PERSIST);
		} catch (ReflectionException $e) {
			throw new XmlDriverException(previous: $e);
		}

		if ($this->entityManager->isEntityPersisted($entity)) {
			try {
				$this->xmlDriver->update($entity);
			} catch (ReflectionException $e) {
				throw new XmlDriverException(previous: $e);
			}
		} else {
			try {
				$this->xmlDriver->create($entity);
			} catch (ReflectionException | DOMException $e) {
				throw new XmlDriverException(previous: $e);
			}
		}

		return $entity;
	}

	/**
	 * Deletes single entity from the XML file.
	 *
	 * @throws IXmlDriverException
	 */
	public function delete(AbstractEntity $entity): bool
	{
		try {
			$entity->processLifecycleEvent(EventType::BEFORE_DELETE);
		} catch (ReflectionException $e) {
			throw new XmlDriverException(previous: $e);
		}

		$this->entityManager->removeFromIdentityMap($entity);

		return $this->xmlDriver->delete($entity->getId());
	}

	/**
	 * Should return name of the managed class, e.g. User::class for user repository.
	 *
	 * @return class-string
	 */
	abstract protected function getEntityClass(): string;

	/**
	 * Creates an array of entities from list of XML nodes.
	 *
	 * @param DOMNodeList<DOMNode> $xmlRows
	 * @return array<AbstractEntity>
	 * @throws ReflectionException from self::createEntityFromXml
	 * @throws EntityAlreadyAttachedException from self::createEntityFromXml
	 * @throws EntityIdNotRegisteredException from self::createEntityFromXml
	 * @throws PropertyValueNotSetException from self::createEntityFromXml
	 * @throws InvalidEntityException from self::createEntityFromXml
	 * @throws TypeConversionNullabilityMismatchException from self::createEntityFromXml
	 * @throws NotImplementedException from self::createEntityFromXml
	 * @throws TypeConversionXmlToPhpException from self::createEntityFromXml
	 * @throws IXmlDriverException from self::createEntityFromXml
	 */
	private function createEntitiesFromXml(DOMNodeList $xmlRows): array
	{
		$entities = [];

		foreach ($xmlRows as $row) {
			$entities[] = $this->createEntityFromXml($row);
		}

		return $entities;
	}

	/**
	 * Creates single entity from an XLM node using @see AbstractEntity::fromXmlData() method.
	 *
	 * @throws ReflectionException from self::prepareEntityXmlData & AbstractEntity::fromXmlData
	 * @throws EntityAlreadyAttachedException from EntityManager::attachEntity
	 * @throws EntityIdNotRegisteredException from EntityManager::attachEntity
	 * @throws PropertyValueNotSetException from self::prepareEntityXmlData
	 * @throws InvalidEntityException from self::prepareEntityXmlData & AbstractEntity::fromXmlData
	 * @throws TypeConversionNullabilityMismatchException from self::prepareEntityXmlData
	 * @throws NotImplementedException from self::prepareEntityXmlData
	 * @throws TypeConversionXmlToPhpException from self::prepareEntityXmlData
	 * @throws IXmlDriverException from EntityManager::attachEntity
	 */
	private function createEntityFromXml(DOMNode $xmlItem): AbstractEntity
	{
		$entityClass = $this->getEntityClass();
		assert(class_exists($entityClass));

		$xmlData = $this->prepareEntityXmlData($xmlItem);

		// try to find entity in identity map by its id and return existing entity if found
		$originalEntity = $this->entityManager->getEntityById($entityClass, Caster::toInt($xmlData->offsetGet('id')));

		if ($originalEntity instanceof $entityClass) {
			return $originalEntity;
		}

		// create new entity if not found in identity map
		$entity = $entityClass::fromXmlData($xmlData);
		$this->entityManager->attachEntity($entity); // has ID so must be attached after data are set

		return $entity;
	}

	/**
	 * Prepares XML data into ArrayHash where entity property names are used as the offsets.
	 * Converts the raw XML data into its PHP equivalents.
	 *
	 * @throws ReflectionException from EntityReflection::__construct
	 * @throws PropertyValueNotSetException Entity property is not nullable, has no default value and is not set in XML.
	 * @throws InvalidEntityException from EntityReflection::getPropertyAttributes
	 * @throws TypeConversionNullabilityMismatchException from TypeConverter::convertFromXmlToPhp
	 * @throws NotImplementedException from TypeConverter::convertFromXmlToPhp
	 * @throws TypeConversionXmlToPhpException from TypeConverter::convertFromXmlToPhp
	 */
	private function prepareEntityXmlData(DOMNode $xmlItem): XmlDataDto
	{
		$entityClass = $this->getEntityClass();
		$reflection = new EntityReflection($entityClass);
		$xmlData = new XmlDataDto();

		foreach ($reflection->getPropertyAttributes() as $propertyName => $propertyAttribute) {
			$propertyXmlName = XmlMapper::getXmlPropertyName($propertyName);

			if (($xmlItem instanceof DOMElement) === false) {
				throw new XmlElementDoesNotExistException('Entity XML node is not an XML element and property elements cannot be get.');
			}

			$propertyElements = $xmlItem->getElementsByTagName($propertyXmlName);

			if ($propertyElements->count() > 0) {
				$propertyElement = $propertyElements->item(0);

				if ($propertyElement === null) {
					throw new XmlElementDoesNotExistException('Property XML element is null and its value cannot be get.');
				}

				$propertyRawValue = Caster::toString($propertyElement->nodeValue);
				$convertedValue = TypeConverter::convertFromXmlToPhp($propertyRawValue, $propertyAttribute, $reflection->getPropertyType($propertyName));
			} else { // property element not present in XML - must be nullable or have default value
				if ($propertyAttribute->isNullable() === false && $reflection->hasPropertyDefaultValue($propertyName) === false) {
					throw new PropertyValueNotSetException($entityClass, $propertyName);
				}

				$convertedValue = $propertyAttribute->isNullable() ? null : $reflection->getPropertyDefaultValue($propertyName);
			}

			$xmlData->offsetSet($propertyName, $convertedValue);
		}

		return $xmlData;
	}

}
