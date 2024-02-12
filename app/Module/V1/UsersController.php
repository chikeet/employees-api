<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use App\Domain\Api\Facade\UserFacade;
use App\Domain\Api\Response\UserResponseDto;
use App\Model\Exception\IXmlDriverException;

/**
 * @Apitte\Path("/users")
 * @Apitte\Tag("Users")
 */
class UsersController extends BaseV1Controller
{

	private UserFacade $usersFacade;

	public function __construct(UserFacade $usersFacade)
	{
		$this->usersFacade = $usersFacade;
	}

	/**
	 * @Apitte\OpenApi("
	 *   summary: List users.
	 * ")
	 * @Apitte\Path("/")
	 * @Apitte\Method("GET")
	 * @return UserResponseDto[]
	 */
	public function index(ApiRequest $request): array
	{
		try {
			return $this->usersFacade->findAll();
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot get users.')
				->withPrevious($e);
		}
	}

}
