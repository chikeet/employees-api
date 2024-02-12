<?php declare(strict_types = 1);

namespace App\Model\Database;

use App\Model\Database\Entity\AbstractEntity;
use App\Model\Database\Repository\AbstractRepository;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Logic\EntityIdNotRegisteredException;
use App\Model\Exception\Runtime\EntityAlreadyAttachedException;
use Nette\DI\Container;

/**
 * Responsibilities:
 * - Keeps an identity map of all loaded entities (with ID generated).
 * - Generates IDs for new entities.
 */
class EntityManager
{

	/* Entity states, indicating if the entity is attached and persisted. */
	private const string ENTITY_STATE_NEW = 'new';

	private const string ENTITY_STATE_PERSISTED = 'persisted';

	private const string ENTITY_STATE_DETACHED = 'detached';

	/**
	 * Entity states that are considered as attached
	 */
	private const array ATTACHED_STATES = [
		self::ENTITY_STATE_NEW,
		self::ENTITY_STATE_PERSISTED,
	];

	/* States indicating if all IDs for a particular entity class were loaded. */
	private const string IDS_LOADING_STATE_NOT_LOADED = 'not loaded';

	private const string IDS_LOADING_STATE_LOADED = 'loaded';

	/**
	 * Identity map of entities, indexed by their class and spl_object_hash.
	 *
	 * @var array<class-string, array<int, AbstractEntity>>
	 */
	private array $identityMap = [];

	/**
	 * Map of IDs of all loaded entities, indexed by their class and spl_object_hash.
	 *
	 * @var array<class-string, array<string, int>>
	 */
	private array $entityIds = [];

	/**
	 * Map of states of all loaded entities, indexed by their spl_object_hash.
	 *  Values are one of @see self::ENTITY_STATE_*.
	 *
	 * @var array<string, string>
	 */
	private array $entityStates = [];

	/**
	 * Map of loading states indicating if all IDs for a particular entity class were loaded.
	 * Values are one of @see self::IDS_LOADING_STATE_*.
	 *
	 * @var array<class-string, string>
	 */
	private array $idsLoadingStates = [];

	public function __construct(
		private readonly Container $container,
	)
	{
	}

	/**
	 * Adds entity to identity map including ID management (generates unique id for new entity).
	 *
	 * @throws EntityAlreadyAttachedException when adding entity to identity map fails.
	 * @throws IXmlDriverException from self::generateId
	 */
	public function attachEntity(AbstractEntity $entity): bool
	{
		if ($this->isEntityAttached($entity)) {
			return false;
		}

		$isPersisted = $entity->hasId();

		if ($entity->hasId() === false) {
			$entity->setId($this->generateId($entity));
		}

		$entityClass = $entity::class;
		$hash = $this->getEntityHash($entity);

		$this->entityIds[$entityClass][$hash] = $entity->getId();
		$addedToIdentityMap = $this->addToIdentityMap($entity, $isPersisted);

		if ($addedToIdentityMap === false) {
			throw new EntityAlreadyAttachedException();
		}

		return true;
	}

	/**
	 * Removes entity from identity map.
	 *
	 * @return bool true if removed successfully, false if not found in identity map.
	 * @throws EntityIdNotRegisteredException when entity ID not found in ID map.
	 */
	public function removeFromIdentityMap(AbstractEntity $entity): bool
	{
		$entityClass = $entity::class;

		if (!array_key_exists($entityClass, $this->identityMap)) {
			return false;
		}

		$id = $this->getEntityId($entity);

		if (!isset($this->identityMap[$entityClass][$id])) {
			return false;
		}

		unset($this->identityMap[$entityClass][$id]);

		$hash = $this->getEntityHash($entity);
		$this->entityStates[$hash] = self::ENTITY_STATE_DETACHED;

		return true;
	}

	/**
	 * Removes entity from identity map by its id.
	 *
	 * @return bool true if removed successfully, false if not found in identity map.
	 */
	public function removeFromIdentityMapById(string $entityClass, int $id): bool
	{
		try {
			$hash = $this->getEntityHashById($entityClass, $id);
		} catch (EntityIdNotRegisteredException $e) {
			return false;
		}

		if (!array_key_exists($entityClass, $this->identityMap)) {
			return false;
		}

		if (!isset($this->identityMap[$entityClass][$id])) {
			return false;
		}

		unset($this->identityMap[$entityClass][$id]);
		unset($this->entityIds[$entityClass][$hash]);

		$this->entityStates[$hash] = self::ENTITY_STATE_DETACHED;

		return true;
	}

	/**
	 * Finds entity in identity map by its id. Prevents duplicate loading of same entities from XML.
	 *
	 * @return AbstractEntity|null Returns null if entity is not found.
	 *
	 * Safe - throws nothing.
	 */
	public function getEntityById(string $entityClass, int $id): ?AbstractEntity
	{
		return $this->identityMap[$entityClass][$id] ?? null;
	}

	/**
	 * Returns true if entity is attached to the identity map.
	 *
	 * Safe - throws nothing.
	 */
	public function isEntityAttached(AbstractEntity $entity): bool
	{
		return in_array($this->getEntityState($entity), self::ATTACHED_STATES, true);
	}

	/**
	 * Returns true if entity is persisted - has a corresponding row in the XML file.
	 *
	 * Safe - throws nothing.
	 */
	public function isEntityPersisted(AbstractEntity $entity): bool
	{
		return $this->getEntityState($entity) === self::ENTITY_STATE_PERSISTED;
	}

	/**
	 * Generates new int ID for the entity. The ID is 1 higher than max existing ID in the XML file.
	 * Only int IDs stored in the id property are supported so far.
	 *
	 * @throws IXmlDriverException
	 */
	private function generateId(AbstractEntity $entity): int // TODO @improve: support non-int IDs, e.g. UUID
	{
		$entityClass = $entity::class;

		$this->loadIdsForEntityClass($entityClass);

		$existingIds = $this->entityIds[$entityClass];
		$maxId = count($existingIds) > 0
			? max($existingIds)
			: 0;

		return $maxId + 1;
	}

	/**
	 * Adds entity to identity map.
	 *
	 * @param AbstractEntity $entity Entity must have an ID set to be added to identity map.
	 * @throws EntityIdNotRegisteredException when entity ID not found in ID map.
	 */
	private function addToIdentityMap(AbstractEntity $entity, bool $isPersisted): bool
	{
		$entityClass = $entity::class;

		if (!array_key_exists($entityClass, $this->identityMap)) {
			$this->identityMap[$entityClass] = [];
		}

		$id = $this->getEntityId($entity);

		if (array_key_exists($id, $this->identityMap[$entityClass])) {
			return false;
		}

		$this->identityMap[$entityClass][$id] = $entity;

		$hash = $this->getEntityHash($entity);
		$this->entityStates[$hash] = $isPersisted
			? self::ENTITY_STATE_PERSISTED
			: self::ENTITY_STATE_NEW;

		return true;
	}

	/**
	 * Returns entity state - one of the @see self::ENTITY_STATE_* constants if entity is registered in identity map.
	 * Returns null if entity was not yet registered in the identity map.
	 *
	 * Safe - throws nothing.
	 */
	private function getEntityState(AbstractEntity $entity): ?string
	{
		$hash = $this->getEntityHash($entity);

		return $this->entityStates[$hash] ?? null;
	}

	/**
	 * Returns repository for the entity class.
	 *
	 * @param class-string $entityClass
	 * @return AbstractRepository<AbstractEntity>
	 */
	private function getRepository(string $entityClass): AbstractRepository
	{
		$repositoryClass = $this->getEntityRepositoryClass($entityClass);

		return $this->container->getByType($repositoryClass);
	}

	/**
	 * Returns repository class for the entity class.
	 *
	 * @return class-string
	 */
	private function getEntityRepositoryClass(string $entityClass): string
	{
		/** @var class-string $repositoryClass */
		$repositoryClass = sprintf('%sRepository', $entityClass);

		return $repositoryClass;
	}

	/**
	 * Returns entity ID from the map of IDs.
	 *
	 * @throws EntityIdNotRegisteredException when entity is not in the map.
	 */
	private function getEntityId(AbstractEntity $entity): int
	{
		$entityClass = $entity::class;
		$hash = $this->getEntityHash($entity);

		if (!isset($this->entityIds[$entityClass][$hash])) {
			throw new EntityIdNotRegisteredException();
		}

		return $this->entityIds[$entityClass][$hash];
	}

	/**
	 * Returns entity spl_object_hash by its ID from the map of IDs.
	 *
	 * @throws EntityIdNotRegisteredException when entity is not in the map.
	 */
	private function getEntityHashById(string $entityClass, int $id): string
	{
		if (!isset($this->entityIds[$entityClass])) {
			throw new EntityIdNotRegisteredException();
		}

		$hash = array_search($id, $this->entityIds[$entityClass], true);

		if ($hash === false) {
			throw new EntityIdNotRegisteredException();
		}

		return $hash;
	}

	/**
	 * Returns entity spl_object_hash.
	 *
	 * Safe - throws nothing.
	 */
	private function getEntityHash(AbstractEntity $entity): string
	{
		return spl_object_hash($entity);
	}

	/**
	 * Loads all IDs for an entity class. Used to generate a new ID.
	 *
	 * @param class-string $entityClass
	 * @throws IXmlDriverException from AbstractRepository::findAll
	 */
	private function loadIdsForEntityClass(string $entityClass): void
	{
		if (!array_key_exists($entityClass, $this->idsLoadingStates)) {
			$this->idsLoadingStates[$entityClass] = self::IDS_LOADING_STATE_NOT_LOADED;
		}

		if ($this->idsLoadingStates[$entityClass] === self::IDS_LOADING_STATE_LOADED) {
			return;
		}

		if (array_key_exists($entityClass, $this->entityIds) === false) {
			$this->entityIds[$entityClass] = []; // init entity IDs - important when no persisted entities of the class exist so far
		}

		$repository = $this->getRepository($entityClass);
		$repository->findAll(); // ids are loaded by entity manager when entities are added to identity map

		$this->idsLoadingStates[$entityClass] = self::IDS_LOADING_STATE_LOADED;
	}

}
