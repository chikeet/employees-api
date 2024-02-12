<?php declare(strict_types = 1);

namespace App\Domain\User;

use App\Model\Database\Repository\AbstractRepository;

/**
 * @method User|NULL find($id)
 * @method User|NULL findOneBy($criteria)
 * @method User[] findAll()
 * @extends AbstractRepository<User>
 */
final readonly class UserRepository extends AbstractRepository
{

	protected function getEntityClass(): string
	{
		return User::class;
	}

}
