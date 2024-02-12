<?php declare(strict_types = 1);

namespace App\Model\Database;

use App\Model\Database\Entity\AbstractEntity;
use App\Model\Database\Entity\EntityReflection;
use App\Model\Database\Type\TypeConverter;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Logic\EntityXmlFileNotFoundException;
use App\Model\Exception\Logic\InvalidEntityException;
use App\Model\Exception\Logic\InvalidXmlException;
use App\Model\Exception\Logic\NotImplementedException;
use App\Model\Exception\Logic\PropertyDoesNotExistException;
use App\Model\Exception\Logic\XmlDataDirNotFoundException;
use App\Model\Exception\Logic\XmlElementDoesNotExistException;
use App\Model\Exception\Logic\XmlNodeDoesNotExistException;
use App\Model\Exception\Logic\XmlNodeListDoesNotExistException;
use App\Model\Exception\Runtime\TypeConversionNullabilityMismatchException;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use ReflectionClass;
use ReflectionException;

/**
 * Responsibilities:
 * - Loads, validates and persists XML file. Triggers XSD schema regenerating when entity class is changed.
 * - Retrieves entity nodes from XML file by given criteria.
 * - Creates, updates and deletes entity nodes within XML file. Ensures conversion of PHP values into XML format.
 */
class XmlDriver
{

	/* Names of elements in the XML structure. May be replaced by configurable values later. */
	public const string XML_DATA_WRAPPER_ELEMENT = 'data';
	public const string XML_ROW_ELEMENT = 'row';

	/** @var DOMDocument for work with the XML structure. */
	protected DOMDocument $dom;

	/**
	 * @param class-string $entityClass
	 * @throws InvalidXmlException from self::validateXmlFile
	 * @throws ReflectionException from self::validateXmlFile
	 * @throws EntityXmlFileNotFoundException from self::validateXmlFile
	 * @throws XmlDataDirNotFoundException When $dataDirPath is invalid
	 */
	public function __construct(
		private readonly string $entityClass,
		private readonly string $dataDirPath,
	)
	{
		if (!is_dir($this->dataDirPath)) {
			throw new XmlDataDirNotFoundException('XML data dir was not found. Check the $dataDirPath parameter value.');
		}

		$this->loadXmlFile();
		$this->validateXmlFile();
	}

	/**
	 * Creates a new XML node for a newly created entity.
	 *
	 * @throws DOMException from self::getNewEntityXmlNode & DomDocument::createElement
	 * @throws ReflectionException from EntityReflection::__construct
	 * @throws IXmlDriverException from  self::getEntityPropertyXmlValue
	 * @throws InvalidEntityException from EntityReflection::getPropertyAttributes & self::getEntityPropertyXmlValue
	 * @throws NotImplementedException from self::getEntityPropertyXmlValue
	 * @throws TypeConversionNullabilityMismatchException from self::getEntityPropertyXmlValue
	 * @throws PropertyDoesNotExistException from self::getEntityPropertyXmlValue
	 */
	public function create(AbstractEntity $entity): void
	{
		// create new row node in XML
		$entityNode = $this->getNewEntityXmlNode();

		// write entity properties to XML
		$entityReflection = new EntityReflection($entity::class);

		foreach ($entityReflection->getPropertyAttributes() as $propertyName => $propertyAttribute) {
			$propertyXmlValue = $this->getEntityPropertyXmlValue($entity, $entityReflection, $propertyName);
			$propertyXmlName = XmlMapper::getXmlPropertyName($propertyName);

			$propertyNode = $this->dom->createElement($propertyXmlName, $propertyXmlValue);

			if ($propertyXmlValue === '') {
				$this->setPropertyNodeValueNull($propertyNode);
			}

			$entityNode->appendChild($propertyNode);
		}

		$this->persistXml();
	}

	/**
	 * Updates an XML node of entity with given ID. Throws exception if node that should exist is not found.
	 *
	 * @throws XmlNodeDoesNotExistException Invalid XML structure. Entity or property XML node does not exist.
	 * @throws XmlNodeListDoesNotExistException Invalid XML structure. Properties XML node list does not exist.
	 * @throws ReflectionException from EntityReflection::__construct
	 * @throws InvalidEntityException from EntityReflection::getPropertyAttributes && self::getEntityPropertyXmlValue
	 * @throws NotImplementedException from self::getEntityPropertyXmlValue
	 * @throws TypeConversionNullabilityMismatchException from self::getEntityPropertyXmlValue
	 * @throws PropertyDoesNotExistException from self::getEntityPropertyXmlValue
	 */
	public function update(AbstractEntity $entity): void
	{
		// get the row node in XML
		$entityNode = $this->getEntityXmlNodeById($entity->getId());

		if ($entityNode === null) {
			throw new XmlNodeDoesNotExistException(
				sprintf(
					'Invalid XML structure. Entity XML node for entity %s does not exist.',
					$this->entityClass,
				),
			);
		}

		// write entity properties to XML
		$entityReflection = new EntityReflection($entity::class);
		$xPath = $this->getXPath();

		foreach ($entityReflection->getPropertyAttributes() as $propertyName => $propertyAttribute) {
			$propertyXmlValue = $this->getEntityPropertyXmlValue($entity, $entityReflection, $propertyName);
			$propertyXmlName = XmlMapper::getXmlPropertyName($propertyName);

			$domNodeList = $xPath->query($propertyXmlName, $entityNode);

			if ($domNodeList === false) {
				throw new XmlNodeListDoesNotExistException('Invalid XML structure. Properties XML node list does not exist.');
			}

			$propertyNode = $domNodeList->item(0);

			if ($propertyNode === null) {
				throw new XmlNodeDoesNotExistException(
					sprintf(
						'Invalid XML structure. Property XML node for entity property %s::$%s does not exist.',
						$this->getEntityClass(),
						$propertyName,
					),
				);
			}

			if ($propertyXmlValue === '') {
				$this->setPropertyNodeValueNull($propertyNode);
			} else {
				$this->setPropertyNodeValueNotNull($propertyNode);
			}

			$propertyNode->nodeValue = $propertyXmlValue;
		}

		$this->persistXml();
	}

	/**
	 * Deletes an XML node with given ID.
	 *
	 * @return bool true if the node is found and deleted, false if the node is not found.
	 */
	public function delete(int $id): bool
	{
		$entityNode = $this->getEntityXmlNodeById($id);

		if ($entityNode === null) {
			return false;
		}

		$parentNode = $entityNode->parentNode;

		if ($parentNode === null) {
			throw new XmlNodeDoesNotExistException('Invalid XML structure. XML wrapper node does not exist.');
		}

		$parentNode->removeChild($entityNode);

		$this->persistXml();

		return true;
	}

	/**
	 * Returns an XML node for one entity with given ID or null if no such node is found.
	 *
	 * Safe - throws nothing.
	 */
	public function getEntityXmlNodeById(int $id): ?DOMNode
	{
		return $this->getEntityXmlNodeByCriteria(['id' => (string) $id]);
	}

	/**
	 * Returns an XML node for one entity by given criteria or null if no such node is found.
	 *
	 * @param array<string, string> $criteria
	 * @throws XmlNodeListDoesNotExistException XPath query error.
	 */
	public function getEntityXmlNodeByCriteria(array $criteria): ?DOMNode
	{
		$xPath = $this->getXPath();

		$formattedCriteria = [];

		foreach ($criteria as $key => $value) {
			$xmlKey = XmlMapper::getXmlPropertyName($key);
			$formattedCriteria[] = sprintf("%s='%s'", $xmlKey, $value);
		}

		$criteriaQuery = implode(' and ', $formattedCriteria);

		$query = sprintf('//%s/%s[%s]', self::XML_DATA_WRAPPER_ELEMENT, self::XML_ROW_ELEMENT, $criteriaQuery);

		$domNodeList = $xPath->query($query);

		if ($domNodeList === false) {
			throw new XmlNodeListDoesNotExistException('XPath query error. Query result DOM node list not found.');
		}

		return $domNodeList->item(0);
	}

	/**
	 * Returns XML nodes of all entities that exist in managed XML file.
	 *
	 * Safe - throws nothing.
	 *
	 * @return DOMNodeList<DOMNode>
	 */
	public function getAllEntitiesXmlNodes(): DOMNodeList
	{
		return $this->dom->getElementsByTagName(self::XML_ROW_ELEMENT);
	}

	/**
	 * Creates an XML node for a new entity.
	 *
	 * @throws DOMException from DomDocument::createElement
	 */
	private function getNewEntityXmlNode(): DOMNode
	{
		$entityNode = $this->dom->createElement(self::XML_ROW_ELEMENT);
		$dataNode = $this->dom->getElementsByTagName(self::XML_DATA_WRAPPER_ELEMENT)->item(0);

		if ($dataNode === null) {
			throw new XmlNodeDoesNotExistException('Invalid XML structure. XML wrapper node does not exist.');
		}

		return $dataNode->appendChild($entityNode);
	}

	/**
	 * Persists the DOMDocument into the managed XML file.
	 *
	 * Safe - throws nothing.
	 */
	private function persistXml(): void
	{
		$this->dom->save($this->getFilePath($this->getXmlFileName()));
	}

	/**
	 * Retrieves value of an entity property and converts it to value that will be stored in XML.
	 *
	 * @throws NotImplementedException from TypeConverter::convertFromPhpToXml
	 * @throws TypeConversionNullabilityMismatchException from TypeConverter::convertFromPhpToXml
	 * @throws PropertyDoesNotExistException from EntityReflection::getPropertyAttribute
	 * @throws InvalidEntityException from EntityReflection::getPropertyAttribute
	 */
	private function getEntityPropertyXmlValue(
		AbstractEntity $entity,
		EntityReflection $entityReflection,
		string $propertyName,
	): string
	{
		$propertyType = $entityReflection->getPropertyType($propertyName);
		$property = $entityReflection->getProperty($propertyName);
		$property->setAccessible(true);
		$propertyValue = $property->getValue($entity);

		return TypeConverter::convertFromPhpToXml(
			$propertyValue,
			$entityReflection->getPropertyAttribute($propertyName),
			$propertyType,
		);
	}

	/**
	 * Marks property node that contains NULL value.
	 *
	 * @throws XmlElementDoesNotExistException when cannot get XML element
	 */
	private function setPropertyNodeValueNull(DOMNode $propertyNode): void
	{
		if (($propertyNode instanceof DOMElement) === false) {
			throw new XmlElementDoesNotExistException('Property XML node is not an XML element and nullable value cannot be set.');
		}

		$propertyNode->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:nil', 'true');
	}

	/**
	 * Marks property node that contains NULL value.
	 *
	 * @throws XmlElementDoesNotExistException when cannot get XML element
	 */
	private function setPropertyNodeValueNotNull(DOMNode $propertyNode): void
	{
		if (($propertyNode instanceof DOMElement) === false) {
			throw new XmlElementDoesNotExistException('Property XML node is not an XML element and nullable value cannot be set.');
		}

		$propertyNode->removeAttribute('xsi:nil');
		$propertyNode->removeAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:nil');
	}

	/**
	 * Creates DOMXPath for loaded DOMDocument.
	 *
	 * Safe - throws nothing.
	 */
	private function getXPath(): DOMXPath
	{
		return new DOMXPath($this->dom);
	}

	/**
	 * Loads content of the XML file into DOMDocument.
	 *
	 * @throws EntityXmlFileNotFoundException when XML file does not exist or is empty
	 */
	private function loadXmlFile(): void
	{
		$xmlFilePath = $this->getFilePath($this->getXmlFileName());

		if (file_exists($xmlFilePath) === false) {
			throw new EntityXmlFileNotFoundException($xmlFilePath);
		}

		$xmlDocument = file_get_contents($xmlFilePath);

		if ($xmlDocument === false) {
			throw new EntityXmlFileNotFoundException($xmlFilePath);
		}

		$this->dom = new DOMDocument();
		$this->dom->loadXML($xmlDocument);
	}

	/**
	 * Validates content of the XML file, where entities data are stored, against corresponding XSD schema.
	 *
	 * @throws InvalidXmlException XML file is not valid against the XSD schema.
	 * @throws ReflectionException from self::isXsdUpToDate & self::regenerateXsd
	 * @throws EntityXmlFileNotFoundException from self::isXsdUpToDate & self::regenerateXsd
	 */
	private function validateXmlFile(): void
	{
		if (!$this->isXsdUpToDate()) {
			$this->regenerateXsd();
		}

		$xsdFilePath = $this->getFilePath($this->getXsdFileName());

		// Enable libxml schema validation
		libxml_use_internal_errors(true);

		// Validate the XML document against the XML schema
		if ($this->dom->schemaValidate($xsdFilePath)) {
			return;
		}

		$message = sprintf(
			'The XML document %s is not valid against the schema %s.',
			$this->getXmlFileName(),
			$this->getXsdFileName(),
		);

		// Output any validation errors
		$errors = libxml_get_errors();
		foreach ($errors as $error) {
			$message .= sprintf(
				"\nError: %s (Line: %s, Column: %s)",
				$error->message,
				$error->line,
				$error->column,
			);
		}

		// Clear libxml error buffer
		libxml_clear_errors();

		throw new InvalidXmlException($message);
	}

	/**
	 * Returns base name (without extension) of the XML/XSD file for the managed entity.
	 *
	 * Safe - throws nothing.
	 */
	private function getBaseFileName(): string
	{
		return XmlMapper::getXmlFileName($this->entityClass);
	}

	/**
	 * Returns full name of the XML file where data of managed entities are stored.
	 *
	 * Safe - throws nothing.
	 */
	private function getXmlFileName(): string
	{
		return sprintf('%s.xml', $this->getBaseFileName());
	}

	/**
	 * Returns full name of the XSD schema file used for validation of the XML file.
	 *
	 * Safe - throws nothing.
	 */
	private function getXsdFileName(): string
	{
		return sprintf('%s.xsd', $this->getBaseFileName());
	}

	/**
	 * Returns full path of the XML/XSD file within the data dir.
	 *
	 * Safe - throws nothing.
	 */
	private function getFilePath(string $fileName): string
	{
		return sprintf('%s/%s', $this->getDataDir(), $fileName);
	}

	/**
	 * Returns full path of the data dir where the XML/XSD files are stored.
	 *
	 * Safe - throws nothing.
	 */
	private function getDataDir(): string
	{
		return $this->dataDirPath;
	}

	/**
	 * Returns class of the managed entity
	 *
	 * Safe - throws nothing.
	 *
	 * @return class-string
	 */
	private function getEntityClass(): string
	{
		return $this->entityClass;
	}

	/**
	 * Checks if XSD schema is generated from current version of the corresponding entity class.
	 * MD5 hash of entity PHP file is used as a check mark.
	 *
	 * @throws ReflectionException from self::getEntityHashComment
	 * @throws EntityXmlFileNotFoundException from self::getEntityHashComment
	 */
	private function isXsdUpToDate(): bool
	{
		$xsdFilePath = $this->getFilePath($this->getXsdFileName());

		if (file_exists($xsdFilePath) === false) {
			return false;
		}

		$xsdLines = file($xsdFilePath, FILE_IGNORE_NEW_LINES);

		if ($xsdLines === false) {
			return false;
		}

		$lastLine = end($xsdLines);

		if (!is_string($lastLine)) {
			return false;
		}

		$hashComment = $this->getEntityHashComment();

		return trim($lastLine) === trim($hashComment);
	}

	/**
	 * Returns XML comment with MD5 hash of entity PHP file.
	 * Used to regenerate XSD file on entity change.
	 *
	 * @throws ReflectionException from ReflectionClass::__construct
	 * @throws EntityXmlFileNotFoundException Entity class PHP file was not found.
	 */
	private function getEntityHashComment(): string
	{
		$reflection = new ReflectionClass($this->getEntityClass());
		$entityFileName = $reflection->getFileName();

		if ($entityFileName === false) {
			throw new EntityXmlFileNotFoundException('');
		}

		$hash = md5_file($entityFileName);

		if ($hash === false) {
			throw new EntityXmlFileNotFoundException($entityFileName);
		}

		return sprintf('<!-- Entity hash: %s -->', $hash);
	}

	/**
	 * Regenerates XSD schema according to entity PHP class.
	 * An XML comment with entity hash is appended to the end of the file to keep the schema up to date with the entity.
	 *
	 * @throws ReflectionException from XsdSchemaGenerator::generateXsdSchema
	 * @throws ReflectionException from self::getEntityHashComment
	 * @throws EntityXmlFileNotFoundException from self::getEntityHashComment
	 */
	private function regenerateXsd(): void
	{
		$xsdSchemaContent = XsdSchemaGenerator::generateXsdSchema($this->getEntityClass());
		$xsdSchemaContent .= PHP_EOL . $this->getEntityHashComment();

		$xsdFilePath = $this->getFilePath($this->getXsdFileName());
		file_put_contents($xsdFilePath, $xsdSchemaContent);
	}

}
