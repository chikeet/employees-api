<?php declare(strict_types = 1);

namespace App\Module\V1;

use Apitte\Core\Annotation\Controller as Apitte; // for Apitte annotations
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Domain\Api\Facade\EmployeeFacade;
use App\Domain\Api\Request\Employee\CreateEmployeeRequestDto;
use App\Model\Exception\IXmlDriverException;
use Nette\Http\IResponse;

/**
 * @Apitte\Path("/employees")
 * @Apitte\Tag("Employees")
 */
class EmployeeCreateController extends BaseV1Controller
{

	private EmployeeFacade $employeesFacade;


	public function __construct(EmployeeFacade $employeesFacade)
	{
		$this->employeesFacade = $employeesFacade;
	}


	/**
	 * @Apitte\OpenApi("
	 *   summary: Create new employee.
	 * ")
	 * @Apitte\Path("/")
	 * @Apitte\Method("POST")
	 * @Apitte\RequestBody(entity="App\Domain\Api\Request\Employee\CreateEmployeeRequestDto")
	 */
	public function create(ApiRequest $request, ApiResponse $response): ApiResponse
	{
		/** @var CreateEmployeeRequestDto $dto */
		$dto = $request->getParsedBody();

		try {
			$this->employeesFacade->create($dto);

			return $response->withStatus(IResponse::S201_Created)
				->withHeader('Content-Type', 'application/json');
		} catch (IXmlDriverException $e) {
			throw ServerErrorException::create()
				->withMessage($e->getMessage() . ', trace: ' . $e->getTraceAsString())
				->withPrevious($e);
		}
	}

}
