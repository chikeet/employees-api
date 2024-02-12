<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte; // for Apitte annotations
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Domain\Api\Facade\UserFacade;
use App\Domain\Api\Request\User\CreateUserRequestDto;
use App\Model\Exception\IXmlDriverException;
use Nette\Http\IResponse;

/**
 * @Apitte\Path("/users")
 * @Apitte\Tag("Users")
 */
class UserCreateController extends BaseV1Controller
{

	private UserFacade $usersFacade;

	public function __construct(UserFacade $usersFacade)
	{
		$this->usersFacade = $usersFacade;
	}

	/**
	 * @Apitte\OpenApi("
	 *   summary: Create new user.
	 * ")
	 * @Apitte\Path("/")
	 * @Apitte\Method("POST")
	 * @Apitte\RequestBody(entity="App\Domain\Api\Request\User\CreateUserRequestDto")
	 */
	public function create(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		/** @var CreateUserRequestDto $dto */
		$dto = $request->getParsedBody();

		try {
			$this->usersFacade->create($dto);

			return $response->withStatus(IResponse::S201_Created)
				->withHeader('Content-Type', 'application/json');
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot create user.')
				->withPrevious($e);
		}
	}

}
