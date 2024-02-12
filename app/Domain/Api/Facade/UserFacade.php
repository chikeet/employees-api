<?php declare(strict_types = 1);

namespace App\Domain\Api\Facade;

use App\Domain\Api\Request\User\CreateUserRequestDto;
use App\Domain\Api\Request\User\UpdateUserRequestDto;
use App\Domain\Api\Response\UserResponseDto;
use App\Domain\User\User;
use App\Domain\User\UserRepository;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Runtime\Database\EntityNotFoundException;
use App\Model\Security\Passwords;

final readonly class UserFacade
{

	public function __construct(
		private UserRepository $userRepository,
	)
	{
	}

	/**
	 * @return UserResponseDto[]
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function findAll(): array
	{
		$entities = $this->userRepository->findAll();
		$result = [];

		foreach ($entities as $entity) {
			$result[] = UserResponseDto::from($entity);
		}

		return $result;
	}

	/**
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function findOne(int $id): UserResponseDto
	{
		$entity = $this->userRepository->find($id);

		if ($entity === null) {
			throw new EntityNotFoundException();
		}

		return UserResponseDto::from($entity);
	}

	/**
	 * @param array<string, string> $criteria
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function findOneBy(array $criteria): UserResponseDto
	{
		$entity = $this->userRepository->findOneBy($criteria);

		if ($entity === null) {
			throw new EntityNotFoundException();
		}

		return UserResponseDto::from($entity);
	}

	/**
	 * @throws IXmlDriverException
	 */
	public function create(CreateUserRequestDto $dto): User
	{
		$user = new User(
			$dto->name,
			$dto->surname,
			$dto->email,
			$dto->username,
			Passwords::create()->hash($dto->password ?? md5(microtime()))
		);

		$this->userRepository->persist($user);

		return $user;
	}

	/**
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function update(int $id, UpdateUserRequestDto $dto): User
	{
		$user = $this->userRepository->find($id);

		if ($user === null) {
			throw new EntityNotFoundException();
		}

		$user->setName($dto->name);
		$user->setSurname($dto->surname);
		$user->setEmail($dto->email);

		$this->userRepository->persist($user);

		return $user;
	}

	/**
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function delete(int $id): bool
	{
		$entity = $this->userRepository->find($id);

		if ($entity === null) {
			throw new EntityNotFoundException();
		}

		return $this->userRepository->delete($entity);
	}

}
