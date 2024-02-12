<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte;
use Apitte\Core\Http\ApiRequest;
use App\Domain\Api\Facade\UsersFacade;
use App\Domain\Api\Response\UserResDto;

/**
 * @Apitte\Path("/users")
 * @Apitte\Tag("Users")
 */
class UsersController extends BaseV1Controller
{

	private UsersFacade $usersFacade;

	public function __construct(UsersFacade $usersFacade)
	{
		$this->usersFacade = $usersFacade;
	}

	/**
	 * @Apitte\OpenApi("
	 *   summary: List users.
	 * ")
	 * @Apitte\Path("/")
	 * @Apitte\Method("GET")
	 * @return UserResDto[]
	 */
	public function index(ApiRequest $request): array
	{
		return $this->usersFacade->findAll();
	}

}
