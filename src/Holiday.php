<?php
namespace Ngekoding\PhpHariLibur;

use Goutte\Client;

require __DIR__.'/helpers/array_helper.php';

class Holiday
{
  private $baseUrl = 'https://publicholidays.co.id/id/%s-dates';
  private $dayList = [ 1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
  private $monthList = [ 
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 
    'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];

  private $holidays;
  private $defaultDays = [];
  private $defaultDates = [];

  /**
   * Initialize the Holiday.
   * 
   * @param int|string $year    The holidays year
   * @param bool $local         Use local saved file if available
   * @param bool $defaultSunday Indicate that Sunday as default holiday
   */
  public function __construct($year = NULL, $local = FALSE, $defaultSunday = TRUE)
  {
    $year = empty($year) ? date('Y') : $year;
    $url = sprintf($this->baseUrl, $year);
    $client = new Client();
    $crawler = $client->request('GET', $url);
    
    // Add default Sunday as holiday
    if ($defaultSunday) {
      $this->addDefaultDay('Minggu', 'Libur pekanan hari minggu');
    }

    $holidays = [];
    $localHolidays = $this->getFromLocal($year);

    if (!$local || empty($localHolidays)) {
      $crawler->filter('table.publicholidays tbody tr')->each(function($row) use (&$holidays, $year) {
        $rowClass = trim($row->attr('class'));
        if (in_array($rowClass, ['even', 'odd'])) {
          $columns = $row->children('td');
          $dateStr = $columns->eq(0)->text();
          $description = trim($columns->eq(2)->text());
          
          // The date str may include range with format date1 to date2
          $dateExp = explode(' to ', $dateStr);

          if (count($dateExp) == 2) {
            list($dayStart, $monthIndoStart) = explode(' ', trim($dateExp[0]));
            list($dayEnd, $monthIndoEnd) = explode(' ', trim($dateExp[1]));
            $monthStart = array_search($monthIndoStart, $this->monthList);
            $monthEnd = array_search($monthIndoEnd, $this->monthList);
            $dateStart = date('Y-m-d', strtotime($year.'-'.$monthStart.'-'.$dayStart));
            $dateEnd = date('Y-m-d', strtotime($year.'-'.$monthEnd.'-'.$dayEnd));
          } else {
            list($day, $monthIndo) = explode(' ', trim($dateExp[0]));
            $month = array_search($monthIndo, $this->monthList);
            $dateStart = $dateEnd = date('Y-m-d', strtotime($year.'-'.$month.'-'.$day));
          }

          array_push($holidays, (object) [
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'description' => $description
          ]); 
        }
      });
      $this->holidays = $holidays;
      $this->saveToLocal($year, $holidays);
    } else {
      $this->holidays = $localHolidays;
    }
  }

  /**
   * Add default holiday by day name.
   * 
   * @param string $day         The day name in Indonesia format (e.g. Minggu)
   * @param string $description The holiday description
   */
  public function addDefaultDay($day, $description)
  {
    $day = ucfirst(strtolower($day));
    array_push($this->defaultDays, (object) [
      'day' => $day,
      'description' => $description
    ]);
  }

  /**
   * Add default holiday by date.
   * 
   * @param string $dateStart   The start holiday date
   * @param string $dateEnd     The end holiday date
   * @param string $description The holiday description
   */
  public function addDefaultDate($dateStart, $dateEnd = NULL, $description)
  {
    array_push($this->defaultDates, (object) [
      'dateStart' => $dateStart,
      'dateEnd' => $dateEnd ?: $dateStart,
      'description' => $description
    ]);
  }

  /**
   * Get all holidays, including national holiday + default dates.
   * 
   * @return array
   */
  public function getHolidays()
  {
    return array_merge($this->holidays, $this->defaultDates);
  }

  /**
   * Get all holiday default day.
   * 
   * @return array
   */
  public function getDefaultDays()
  {
    return $this->defaultDays;
  }

  /**
   * Get all holiday default dates.
   * 
   * @return array
   */
  public function getDefaultDates()
  {
    return $this->defaultDates;
  }

  /**
   * Checking given date is holiday or not.
   * 
   * @param $date   The date to check, Y-m-d format
   * @param $bool   Defining the return type
   * 
   * @return boolean|object
   */
  public function check($date, $bool = TRUE)
  {
    $status = FALSE;
    $result = (object) [
      'date' => $date,
      'description' => NULL
    ];

    $holidays = $this->getHolidays();
    $dayNum = date('N', strtotime($date));
    $day = $this->dayList[$dayNum];

    // Checking default date
    if (($key = array_search($day, array_column($this->defaultDays, 'day'))) !== FALSE) {
      $status = TRUE;
      $result = (object) [
        'date' => $date,
        'description' => $this->defaultDays[$key]->description
      ];
    } else {
      $time = strtotime($date);
      foreach ($holidays as $holiday) {
        $timeStart = strtotime($holiday->dateStart);
        $timeEnd = strtotime($holiday->dateEnd);
        
        if ($time >= $timeStart && $time <= $timeEnd) {
          $status = TRUE;
          $result = (object) [
            'date' => $date,
            'description' => $holiday->description
          ];
          break;
        }
      }
    }

    if ($bool) {
      return $status;
    } else {
      return (object) [
        'status' => $status,
        'result' => $result
      ];
    }
  }

  /**
   * PRIVATE METHODS
   * Some awesome func to make our life esasier
   */

  /**
   * Get local holidays by year
   * 
   * @param $year The holidays year
   * 
   * @return array
   */
  private function getFromLocal($year)
  {
    $file = __DIR__.'/locals/holidays-'.$year.'.json';

    if (!file_exists($file)) {
      return [];
    }

    return json_decode(file_get_contents($file));
  }

  /**
   * Save holidays to local
   * @param $year       Holidays year
   * @param $holidays   Array of object the holidays
   * 
   * @return void
   */
  private function saveToLocal($year, $holidays)
  {
    $file = __DIR__.'/locals/holidays-'.$year.'.json';
    file_put_contents($file, json_encode($holidays, JSON_PRETTY_PRINT));
  }
}
