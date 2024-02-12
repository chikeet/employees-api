<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte; // for Apitte annotations
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Domain\Api\Facade\EmployeeFacade;
use App\Model\Exception\IXmlDriverException;
use App\Model\Exception\Runtime\Database\EntityNotFoundException;
use App\Model\Utils\Caster;
use Nette\Http\IResponse;

/**
 * @Apitte\Path("/employees")
 * @Apitte\Tag("Employees")
 */
class EmployeeDeleteController extends BaseV1Controller
{

	private EmployeeFacade $employeesFacade;


	public function __construct(EmployeeFacade $employeesFacade)
	{
		$this->employeesFacade = $employeesFacade;
	}


	/**
	 * @Apitte\OpenApi("
	 *   summary: Update an employee.
	 * ")
	 * @Apitte\Path("/{id}")
	 * @Apitte\Method("DELETE")
	 * @Apitte\RequestParameters({
	 *       @Apitte\RequestParameter(name="id", in="path", type="int", description="Employee ID")
	 *  })
	 */
	public function update(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		$id = Caster::toInt($request->getParameter('id'));

		try {
			$result = $this->employeesFacade->delete($id);

			if ($result === true) {
				return $response->withStatus(IResponse::S204_NoContent)
					->withHeader('Content-Type', 'application/json');
			} else {
				throw ServerErrorException::create()
					->withMessage('Cannot delete employee.');
			}
		} catch (EntityNotFoundException $e) {
			throw ClientErrorException::create()
				->withMessage('Employee not found.')
				->withCode(IResponse::S404_NotFound);
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage('Cannot delete employee.')
				->withPrevious($e);
		}
	}

}
