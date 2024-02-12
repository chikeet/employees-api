<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use App\Domain\Api\Facade\UserFacade;
use App\Domain\Api\Response\UserResponseDto;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Runtime\Database\EntityNotFoundException;
use App\Model\Utils\Caster;
use Nette\Http\IResponse;

/**
 * @Apitte\Path("/users")
 * @Apitte\Tag("Users")
 */
class UsersOneController extends BaseV1Controller
{

	private UserFacade $usersFacade;

	public function __construct(UserFacade $usersFacade)
	{
		$this->usersFacade = $usersFacade;
	}

	/**
	 * @Apitte\OpenApi("
	 *   summary: Get user by email.
	 * ")
	 * @Apitte\Path("/email")
	 * @Apitte\Method("GET")
	 * @Apitte\RequestParameters({
	 *      @Apitte\RequestParameter(name="email", in="query", type="string", description="User e-mail address")
	 * })
	 */
	public function byEmail(ApiRequest $request): UserResponseDto
	{
		try {
			return $this->usersFacade->findOneBy(['email' => Caster::toString($request->getParameter('email'))]);
		} catch (EntityNotFoundException $e) {
			throw ClientErrorException::create()
				->withMessage('User not found.')
				->withCode(IResponse::S404_NotFound);
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot get user.')
				->withPrevious($e);
		}
	}

	/**
	 * @Apitte\OpenApi("
	 *   summary: Get user by id.
	 * ")
	 * @Apitte\Path("/{id}")
	 * @Apitte\Method("GET")
	 * @Apitte\RequestParameters({
	 *      @Apitte\RequestParameter(name="id", in="path", type="int", description="User ID")
	 * })
	 */
	public function byId(ApiRequest $request): UserResponseDto
	{
		try {
			return $this->usersFacade->findOne(Caster::toInt($request->getParameter('id')));
		} catch (EntityNotFoundException $e) {
			throw ClientErrorException::create()
				->withMessage('User not found.')
				->withCode(IResponse::S404_NotFound);
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot get user.')
				->withPrevious($e);
		}
	}

}
