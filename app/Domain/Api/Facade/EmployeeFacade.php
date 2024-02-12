<?php declare(strict_types = 1);

namespace App\Domain\Api\Facade;

use App\Domain\Api\Request\Employee\CreateEmployeeRequestDto;
use App\Domain\Api\Request\Employee\UpdateEmployeeRequestDto;
use App\Domain\Api\Response\EmployeeResponseDto;
use App\Domain\Employee\Employee;
use App\Domain\Employee\EmployeeRepository;
use App\Model\Database\Entity\Enum\Gender;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Runtime\Database\EntityNotFoundException;
use App\Model\Security\Passwords;

final readonly class EmployeeFacade
{

	public function __construct(
		private EmployeeRepository $employeeRepository,
	)
	{
	}


	/**
	 * @return EmployeeResponseDto[]
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function findAll(): array
	{
		$entities = $this->employeeRepository->findAll();
		$result = [];

		foreach ($entities as $entity) {
			$result[] = EmployeeResponseDto::from($entity);
		}

		return $result;
	}


	/**
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function findOne(int $id): EmployeeResponseDto
	{
		$entity = $this->employeeRepository->find($id);

		if ($entity === null) {
			throw new EntityNotFoundException();
		}

		return EmployeeResponseDto::from($entity);
	}


	/**
	 * @param array<string, string> $criteria
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function findOneBy(array $criteria): EmployeeResponseDto
	{
		$entity = $this->employeeRepository->findOneBy($criteria);

		if ($entity === null) {
			throw new EntityNotFoundException();
		}

		return EmployeeResponseDto::from($entity);
	}


	/**
	 * @throws IXmlDriverException
	 */
	public function create(CreateEmployeeRequestDto $dto): Employee
	{
		$employee = new Employee(
			$dto->name,
			$dto->age,
			Gender::from($dto->gender),
		);

		$this->employeeRepository->persist($employee);

		return $employee;
	}


	/**
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function update(int $id, UpdateEmployeeRequestDto $dto): Employee
	{
		$employee = $this->employeeRepository->find($id);

		if ($employee === null) {
			throw new EntityNotFoundException();
		}

		$employee->setName($dto->name);
		$employee->setAge($dto->age);

		$this->employeeRepository->persist($employee);

		return $employee;
	}


	/**
	 * @throws IXmlDriverException
	 * @throws EntityNotFoundException
	 */
	public function delete(int $id): bool
	{
		$entity = $this->employeeRepository->find($id);

		if ($entity === null) {
			throw new EntityNotFoundException();
		}

		return $this->employeeRepository->delete($entity);
	}

}
