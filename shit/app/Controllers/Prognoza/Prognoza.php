<?php 
namespace App\Controllers\Prognoza;
use App\Controllers\BaseController;

//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use CodeIgniter\Database\Query;
use DateTime;
use DateInterval;
use IntlDateFormatter;

require_once(__DIR__ .'/../tools.php');
require_once(__DIR__ .'/../GridTools.php');

require_once(APPPATH . 'Libraries/ebs/TimePeriodFilter.php');
require_once(APPPATH . 'Libraries/ebs/DistributorFilter.php');
require_once(APPPATH . 'Libraries/ebs/ActiveFilter.php');
require_once(APPPATH . 'Libraries/ebs/TypesFilter.php');

class Prognoza extends BaseController
{
	use \GridTools;
	private $data = [
        'title'   => '',
		'menu' => 'prognoza',
		'msg' => '',
		'output' => '',
		'upload' =>'',
		'div-card' => ''];
	
	protected $temperaturesModel;	
	protected $syntheticsModel;
	protected $forecastModel;
	protected $sqlData;
	
	protected $tColors;
	protected $tColorsSize;
	protected $tCoef;

	function __construct() {
		checkAuth();
		
	}
	
	/**
     * Initializer 
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {

        parent::initController($request, $response, $logger);
	
		$session = \Config\Services::session();
		if ($session->get('user') === NULL) return view('login.php'); 

        // Load the model
		$this->temperaturesModel = new \App\Models\Prognoza\TemperaturesModel();
        $this->syntheticsModel = new \App\Models\Prognoza\SyntheticsModel();
		$this->forecastModel = new \App\Models\Prognoza\ForecastModel();
    }

	public function index()
	{
	}

	
	
	public function Estimare()
	{
		
		$range = new \Kendo\UI\DateRangePickerRange();
		$startDate = new DateTime();
		$startDate->modify("+1 day");
		
		$dow = (int)$startDate->format('w');
		if($dow == 5) $modif = 4;      // Vineri → Luni
		elseif($dow == 6) $modif = 3;  // Sâmbătă → Luni
		else $modif = 1;
		
        $endDate = new DateTime();
        $endDate->modify("+$modif day");
		$range->start($startDate)
                ->end($endDate);

		
		$view_type = new \Kendo\UI\DropDownList("view_type");
		$view_type->dataSource(['Sumar','Analitic','Lunar'])
					->change('viewTypeChange')
					->attr('style', 'width: 100px')
					->value("Sumar");
		
		$this->data['view_type'] = $view_type->render();
		
		$consumption_types = new \TypesFilter('consumption_types',$this->forecastModel->getFilterConsumptionTypes());
		$this->data['consumption_types'] = $consumption_types->render();
		
		$view_types = new \TypesFilter('view_types',[['value'=>'"Sumar"','text'=>'Sumar'],['value'=>'"Lunar"','text'=>'Lunar'],['value'=>'"Analitic"','text'=>'Analitic']]);
		$this->data['view_types'] = $view_types->render();

		$view_interval = new \TypesFilter('view_interval',[['value'=>'"15min"','text'=>'15min'],['value'=>'"60min"','text'=>'60min']]);
		$this->data['view_interval'] = $view_interval->render();
		
		$zoom_types = new \TypesFilter('zoom_types',[['value'=>'"Mic"','text'=>'Mic'],['value'=>'"Normal"','text'=>'Normal']]);
		$this->data['zoom_types'] = $zoom_types->render();
		
		$mY = $this->forecastModel->get_min_max_years('forecast_estimates','forecast_datetime');
		$PFilter = new \TimePeriodFilter('tp','raport_estimari','Perioana',$mY['minY'],$mY['maxY']+1);
		$PFilter->setcallJSFunction("forecastPeriodChanged");
		$this->data['period_filter'] = $PFilter->render();
	
		
		$this->data['output'] = '<div id="spreadsheet" class="w-100"></div>';
		$this->data['jsFiles'] = ['tools.js','prognoza/estimates.js'];
		
		$this->data['title'] = 'Estimare';
		$this->data['title_class'] = 'text-danger';
		$data['data'] = $this->data;

		return view('prognoza/content',$data);
	}
	
	protected function computeColor($value, $coef, $minV)
	{
		$str = '#ff'.sprintf('%02x', 255-floor($coef * ($value-$minV)) ).sprintf('%02x', 255-floor($coef * ($value-$minV)) );
		
		//echo $value * $coef; exit();
		return $str;
	}
	
	
	
}
