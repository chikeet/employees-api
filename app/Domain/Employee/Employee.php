<?php declare(strict_types = 1);

namespace App\Domain\Employee;

use App\Model\Database\Entity\AbstractEntity;
use App\Model\Database\Entity\Enum\Gender;
use App\Model\Database\Entity\Property;
use App\Model\Database\Entity\TCreatedAt;
use App\Model\Database\Entity\TUpdatedAt;
use App\Model\Database\Type\XmlType;
use App\Model\Utils\DateTime;

final class Employee extends AbstractEntity
{

	use TCreatedAt;
	use TUpdatedAt;

	#[Property(type: XmlType::STRING, isNullable: false, isUnique: false)]
	private string $name;

	#[Property(type: XmlType::INTEGER, isNullable: false, isUnique: true)]
	private int $age;

	#[Property(type: XmlType::STRING, isNullable: false)]
	private Gender $gender;

	public function __construct(
		string $name,
		int $age,
		Gender $gender,
	)
	{
		$this->name = $name;
		$this->age = $age;
		$this->gender = $gender;

		$this->createdAt = new DateTime();
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getAge(): int
	{
		return $this->age;
	}

	public function setAge(int $age): void
	{
		$this->age = $age;
	}

	public function getGender(): Gender
	{
		return $this->gender;
	}

	public function setGender(Gender $gender): void
	{
		$this->gender = $gender;
	}

}
