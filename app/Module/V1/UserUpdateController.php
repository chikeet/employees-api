<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte; // for Apitte annotations
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Domain\Api\Facade\UserFacade;
use App\Domain\Api\Request\User\UpdateUserRequestDto;
use App\Domain\Api\Response\UserResponseDto;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Runtime\Database\EntityNotFoundException;
use App\Model\Utils\Caster;
use Nette\Http\IResponse;

/**
 * @Apitte\Path("/users")
 * @Apitte\Tag("Users")
 */
class UserUpdateController extends BaseV1Controller
{

	private UserFacade $usersFacade;

	public function __construct(UserFacade $usersFacade)
	{
		$this->usersFacade = $usersFacade;
	}

	/**
	 * @Apitte\OpenApi("
	 *   summary: Update an user.
	 * ")
	 * @Apitte\Path("/{id}")
	 * @Apitte\Method("PUT")
	 * @Apitte\RequestBody(entity="App\Domain\Api\Request\User\UpdateUserRequestDto")
	 * @Apitte\RequestParameters({
	 *       @Apitte\RequestParameter(name="id", in="path", type="int", description="User ID")
	 *  })
	 */
	public function update(ApiRequest $request, ApiResponse $response): UserResponseDto
	{
		$id = Caster::toInt($request->getParameter('id'));
		/** @var UpdateUserRequestDto $dto */
		$dto = $request->getParsedBody();

		try {
			$user = $this->usersFacade->update($id, $dto);

			return UserResponseDto::from($user);
		} catch (EntityNotFoundException $e) {
			throw ClientErrorException::create()
				->withMessage('User not founds')
				->withCode(IResponse::S404_NotFound);
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot update users')
				->withPrevious($e);
		}
	}

}
