<?php declare(strict_types = 1);

namespace App\Domain\Employee;

use App\Model\Database\Repository\AbstractRepository;

/**
 * @method Employee|NULL find($id)
 * @method Employee|NULL findOneBy($criteria)
 * @method Employee[] findAll()
 * @extends AbstractRepository<Employee>
 */
final readonly class EmployeeRepository extends AbstractRepository
{

	protected function getEntityClass(): string
	{
		return Employee::class;
	}

}
