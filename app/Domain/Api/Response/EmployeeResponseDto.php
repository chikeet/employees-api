<?php declare(strict_types = 1);

namespace App\Domain\Api\Response;

use App\Domain\Employee\Employee;

final class EmployeeResponseDto
{

	public int $id;

	public string $name;

	public int $age;

	public string $gender;

	public static function from(Employee $employee): self
	{
		$self = new self();
		$self->id = $employee->getId();
		$self->name = $employee->getName();
		$self->age = $employee->getAge();
		$self->gender = $employee->getGender()->value;

		return $self;
	}

}
