<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte; // for Apitte annotations
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Domain\Api\Facade\UserFacade;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Runtime\Database\EntityNotFoundException;
use App\Model\Utils\Caster;
use Nette\Http\IResponse;

/**
 * @Apitte\Path("/users")
 * @Apitte\Tag("Users")
 */
class UserDeleteController extends BaseV1Controller
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
	 * @Apitte\Method("DELETE")
	 * @Apitte\RequestParameters({
	 *       @Apitte\RequestParameter(name="id", in="path", type="int", description="User ID")
	 *  })
	 */
	public function update(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$id = Caster::toInt($request->getParameter('id'));

		try {
			$result = $this->usersFacade->delete($id);

			if ($result === true) {
				return $response->withStatus(IResponse::S204_NoContent)
					->withHeader('Content-Type', 'application/json');
			} else {
				throw ServerErrorException::create()
					->withMessage('Cannot delete user.');
			}
		} catch (EntityNotFoundException $e) {
			throw ClientErrorException::create()
				->withMessage('User not found.')
				->withCode(IResponse::S404_NotFound);
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot delete user.')
				->withPrevious($e);
		}
	}

}
