<?php

namespace App\Models\Prognoza;

use CodeIgniter\Model;
use CodeIgniter\ConnectionInterface;

require_once(__DIR__.'/../CacheTools.php');
require_once(__DIR__.'/../MasterDataTools.php');

class TemperaturesModel extends Model
{
	use \MasterDataTools;
	use \CacheTools;
	protected $table      = 'forecast_temperatures';
	protected $db;
	
	function __construct()
	{
		$this->db = db_connect();
	}
	
	public function getTemperatures($month, $year)
	{
		$sql = "select * from forecast_temperatures fc where year(temperature_datetime)=$year and month(temperature_datetime)=$month order by hour(temperature_datetime), day(temperature_datetime)";
		return $this->getArray($sql);
	}
	
	public function importSQLValues($data)
	{
		$sql = "insert into forecast_temperatures (temperature_datetime, temperature) VALUES $data ON DUPLICATE KEY UPDATE temperature=VALUES(temperature)";
		return $this->writeData($sql);
	}

	public function deleteSQLValues($data)
	{
		$sql = "delete from forecast_temperatures where $data";
		return $this->writeData($sql);
	}
	
	public function uploadValues($YMdate, $values)
	{
		$sqlData='';
		$sqlDeleteData='';
		$dt =  \DateTime::createFromFormat('Y-n-d',$YMdate.'-01');
		$YMdate = $dt->format('Y-m');
		$maxDays = cal_days_in_month(CAL_GREGORIAN,date('n',$dt->getTimestamp()),date('Y',$dt->getTimestamp()));

		for($h=0;$h<24;$h++)
		{
			for($d = 1;$d<=$maxDays;$d++)
			{
				$dateTime = $YMdate.'-'.sprintf('%02d',$d).' '.sprintf('%02d',$h).':00:00';
				$temperature = $values[$h][$d-1];
				
				if($temperature!='')
					$sqlData.="('$dateTime', $temperature),";
				else
					$sqlDeleteData.="(temperature_datetime = '$dateTime') or";
			}
		}
		
		
		$added = 0; $deleted = 0;
		if(!empty($sqlData)) 
		{
			$sqlData = substr_replace($sqlData,'',-1);
			$added = $this->importSQLValues($sqlData);
		}
		
		if(!empty($sqlDeleteData)) 
		{
			$sqlDeleteData = substr_replace($sqlDeleteData,'',-2);
			$deleted = $this->deleteSQLValues($sqlDeleteData);
		}
		
		
		return 24*$maxDays." valori actualizate!";
	}
	
	function isDuplicate($date, $temperature)
	{
		$sql = "SELECT id as value FROM forecast_temperatures where temperature_datetime='$date' and temperature=$temperature";
		
		log_message('error',$sql);
		return (!empty($this->getValue($sql)));
	}
	
	public function createTemperatureLegend()
	{
		//$info = getimagesize(ROOTPATH.'public/images/temperature-legend.png');
		//log_message("error",print_r($info, true));
		$i=0;
		$im = imagecreatefrompng(ROOTPATH.'public/images/temperature-legend.png');
		
		$tColors=array();
		$tColorsSize = 0;
		
		while($im !== false)
		{
			$rgb = imagecolorat($im, $i++,0);
			if($rgb === false) break;
			$tColors[] = '#'.str_pad(dechex($rgb), 6, "0", STR_PAD_LEFT);
			$tColorsSize++;
		}
		
		$tCoef = $tColorsSize/100;
		
		return ['tColors'=>$tColors, 'tColorsSize'=>$tColorsSize, 'tCoef'=>$tCoef];
	}
	
	public function getMeteo($year, $month)
	{
		//10 min execution time
		set_time_limit(600);
		
		$counties = $this->getArray("SELECT * FROM counties");

		// Get the current date and calculate the maximum allowed date
		$currentDate = new \DateTime();
		$maxDate = (clone $currentDate)->modify('+14 days');

		// Start date: 1st of the given month and year
		$startDate = new \DateTime("$year-$month-01");

		// Get the end of the month for the given year and month
		$endDate = (clone $startDate)->modify('last day of this month');

		// Iterate from the start date to the end date or maxDate, whichever comes first
		$loopEndDate = $endDate < $maxDate ? $endDate : $maxDate;

		$affected = 0; $sqlQueue = $this->getSqlQueue(); $qIDs = [];
		for ($date = $startDate; $date <= $loopEndDate; $date->modify('+1 day')) 
		{
			$day = $date->format('Y-m-d');
			if(!empty($this->getValue("SELECT weather_info_id as value FROM weather_info WHERE source='obs' AND weather_date='$day'"))) continue;
			foreach($counties as $county)
			{
				$location =  urlencode($county['municipality'].', Romania');
				$countyCode = $county['county_code'];
				
				$retry = 5;
				while($retry--)
				{
					$r = @file_get_contents("https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/timeline/{$location}/{$day}?unitGroup=metric&include=hours&key=79RTLN9BJS5NGDHYE3UMM3KFS&contentType=json");									
					if(!empty($r)) break;
					else
						log_message("error","Retry($retry) descarcare $countyCode weather for $day");
				}
				
				$vcData = json_decode($r);
				if(empty($vcData))
				{
					log_message("error","Eroare descarcare $countyCode weather for $day");
					return ["errors"=>["Eroare descarcare date meteo!"]];
				}
				
				log_message("error","Import $countyCode weather for $day");
				$sqlData = '';
				
				foreach($vcData->days[0]->hours as $hourData)
				{
					$dayTime = $day.' '.$hourData->datetime;
					
					if($hourData->temp!='') $sqlData .= "('$countyCode', '$dayTime', 'temp', '{$hourData->temp}'),";
					if($hourData->feelslike!='') $sqlData .= "('$countyCode', '$dayTime', 'feelslike', '{$hourData->feelslike}'),";
					if($hourData->humidity!='') $sqlData .= "('$countyCode', '$dayTime', 'humidity', '{$hourData->humidity}'),";
					if($hourData->dew!='') $sqlData .= "('$countyCode', '$dayTime', 'dew', '{$hourData->dew}'),";
					if($hourData->precip!='') $sqlData .= "('$countyCode', '$dayTime', 'precip', '{$hourData->precip}'),";
					if($hourData->precipprob!='') $sqlData .= "('$countyCode', '$dayTime', 'precipprob', '{$hourData->precipprob}'),";
					if($hourData->snow!='') $sqlData .= "('$countyCode', '$dayTime', 'snow', '{$hourData->snow}'),";
					if($hourData->snowdepth!='') $sqlData .= "('$countyCode', '$dayTime', 'snowdepth', '{$hourData->snowdepth}'),";
					if($hourData->windgust!='') $sqlData .= "('$countyCode', '$dayTime', 'windgust', '{$hourData->windgust}'),";
					if($hourData->windspeed!='') $sqlData .= "('$countyCode', '$dayTime', 'windspeed', '{$hourData->windspeed}'),";
					if($hourData->winddir!='') $sqlData .= "('$countyCode', '$dayTime', 'winddir', '{$hourData->winddir}'),";
					if($hourData->pressure!='') $sqlData .= "('$countyCode', '$dayTime', 'pressure', '{$hourData->pressure}'),";
					if($hourData->visibility!='') $sqlData .= "('$countyCode', '$dayTime', 'visibility', '{$hourData->visibility}'),";
					if($hourData->cloudcover!='') $sqlData .= "('$countyCode', '$dayTime', 'cloudcover', '{$hourData->cloudcover}'),";
					if($hourData->solarradiation!='') $sqlData .= "('$countyCode', '$dayTime', 'solarradiation', '{$hourData->solarradiation}'),";
					if($hourData->solarenergy!='') $sqlData .= "('$countyCode', '$dayTime', 'solarenergy', '{$hourData->solarenergy}'),";
					if($hourData->uvindex!='') $sqlData .= "('$countyCode', '$dayTime', 'uvindex', '{$hourData->uvindex}'),";
					
					$affected+=17;
				}
				
				if(!empty($sqlData))
				{
					$sqlData = rtrim($sqlData, ',');
					$sql = "INSERT INTO weather_data (county_code, weather_datetime, layer, value) VALUES $sqlData ON DUPLICATE KEY UPDATE value=VALUES(value)";
					//$this->writeData($sql);
					$qIDs[] = $sqlQueue->sendUniqueItem([$sql],"weather_data");
				}
				
			}
			
			if(!empty($sqlData))
				$qIDs[] = $sqlQueue->sendUniqueItem(["INSERT INTO weather_info (weather_date, source) VALUES ('$day','{$vcData->days[0]->source}')"],"weather_info");
		}
		
		$sqlQueue->waitFor($qIDs);
		
		return "$affected valori importate";
	}
	
	public function getWeatherData($year,$month,$layer,$countyCode)
	{
		
		$sql = "SELECT weather_datetime as datetime, value FROM weather_data WHERE year(weather_datetime)=$year AND month(weather_datetime)=$month AND layer = '$layer' AND county_code='$countyCode' ORDER BY hour(weather_datetime), day(weather_datetime)";
		return ["data"=>$this->getArray($sql),"legend"=>$this->createTemperatureLegend()];
	}
}