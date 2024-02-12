<?php declare(strict_types = 1);

namespace App\Domain\User;

use App\Model\Database\Entity\AbstractEntity;
use App\Model\Database\Entity\Property;
use App\Model\Database\Entity\TCreatedAt;
use App\Model\Database\Entity\TUpdatedAt;
use App\Model\Database\Type\XmlType;
use App\Model\Exception\Logic\InvalidArgumentException;
use App\Model\Utils\DateTime;
use Nette\Utils\Random;

final class User extends AbstractEntity
{

	use TCreatedAt;
	use TUpdatedAt;

	public const ROLE_ADMIN = 'admin';
	public const ROLE_USER = 'user';

	public const STATE_FRESH = 1;
	public const STATE_ACTIVATED = 2;
	public const STATE_BLOCKED = 3;

	public const STATES = [
		self::STATE_FRESH,
		self::STATE_BLOCKED,
		self::STATE_ACTIVATED,
	];

	#[Property(type: XmlType::STRING, isNullable: false, isUnique: false)]
	private string $name;

	#[Property(type: XmlType::STRING, isNullable: false, isUnique: false)]
	private string $surname;

	#[Property(type: XmlType::STRING, isNullable: false, isUnique: true)]
	private string $email;

	#[Property(type: XmlType::STRING, isNullable: false, isUnique: true)]
	private string $username;

	#[Property(type: XmlType::INTEGER, isNullable: false)]
	private int $state;

	#[Property(type: XmlType::STRING, isNullable: false)]
	private string $password;

	#[Property(type: XmlType::STRING, isNullable: false)]
	private string $role;

	#[Property(type: XmlType::STRING, isNullable: false)]
	private string $apikey;

	#[Property(type: XmlType::DATETIME, isNullable: true)]
	private ?DateTime $lastLoggedAt = null;

	public function __construct(
		string $name,
		string $surname,
		string $email,
		string $username,
		string $passwordHash
	)
	{
		$this->name = $name;
		$this->surname = $surname;
		$this->email = $email;
		$this->username = $username;
		$this->password = $passwordHash;

		$this->role = self::ROLE_USER;
		$this->state = self::STATE_FRESH;
		$this->apikey = Random::generate(100);

		$this->setCreatedAt();
	}

	public function changeLoggedAt(): void
	{
		$this->lastLoggedAt = new DateTime();
	}

	public function getEmail(?\Throwable $notSetException = null): string
	{
		if (!isset($this->email) && $notSetException !== null) {
			throw new $notSetException();
		}

		return $this->email;
	}

	public function getUsername(?\Throwable $notSetException = null): string
	{
		if (!isset($this->username) && $notSetException !== null) {
			throw new $notSetException();
		}

		return $this->username;
	}

	public function changeUsername(string $username): void
	{
		$this->username = $username;
	}

	public function getLastLoggedAt(?\Throwable $notSetException = null): ?DateTime
	{
		if (!isset($this->lastLoggedAt) && $notSetException !== null) {
			throw new $notSetException();
		}

		return $this->lastLoggedAt;
	}

	public function getRole(): string
	{
		return $this->role;
	}

	public function setRole(string $role): void
	{
		$this->role = $role;
	}

	public function getPasswordHash(?\Throwable $notSetException = null): string
	{
		if (!isset($this->password) && $notSetException !== null) {
			throw new $notSetException();
		}

		return $this->password;
	}

	public function changePasswordHash(string $password): void
	{
		$this->password = $password;
	}

	public function block(): void
	{
		$this->state = self::STATE_BLOCKED;
	}

	public function activate(): void
	{
		$this->state = self::STATE_ACTIVATED;
	}

	public function isActivated(): bool
	{
		return $this->state === self::STATE_ACTIVATED;
	}

	public function getName(?\Throwable $notSetException = null): string
	{
		if (!isset($this->name) && $notSetException !== null) {
			throw new $notSetException();
		}

		return $this->name;
	}

	public function getSurname(?\Throwable $notSetException = null): string
	{
		if (!isset($this->surname) && $notSetException !== null) {
			throw new $notSetException();
		}

		return $this->surname;
	}

	public function getFullname(): string
	{
		return $this->name . ' ' . $this->surname;
	}

	public function rename(string $name, string $surname): void
	{
		$this->name = $name;
		$this->surname = $surname;
	}

	public function getState(): int
	{
		return $this->state;
	}

	public function setState(int $state): void
	{
		if (!in_array($state, self::STATES, true)) {
			throw new InvalidArgumentException(sprintf('Unsupported state %s', $state));
		}

		$this->state = $state;
	}

	public function getGravatar(): string
	{
		return 'https://www.gravatar.com/avatar/' . md5($this->email);
	}

	public function getApikey(): string
	{
		return $this->apikey;
	}

	public function setApikey(string $apikey): void
	{
		$this->apikey = $apikey;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function setSurname(string $surname): void
	{
		$this->surname = $surname;
	}

	public function setEmail(string $email): void
	{
		$this->email = $email;
	}

}
