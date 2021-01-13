# php-hari-libur

Sebuah library sederhana untuk membantu melakukan pengecekan hari libur di Indonesia.

Library ini merujuk kepada sumber data di https://publicholidays.co.id, akan tetapi tidak ada afiliasi terhadap situs tersebut. Library ini melakukan crawling dan dapat digunakan secara offline apabila data hari libur telah diunduh sebelumnya.

## Instalasi

```bash
composer require ngekoding\php-hari-libur
```

## Contoh Penggunaan

```php
<?php
require  'vendor/autoload.php';

use  ngekoding\PhpHariLibur\Holiday;

$holiday = new  Holiday('2020');

$date = '2020-01-01';
$isHoliday = $holiday->check($date);

echo $date.': '.$isHoliday; // Output: 2020-01-01: true
```

## Pengaturan dan Method

`Holiday($year, $local = FALSE, $defaultSunday = TRUE)`

Konstruktor Holiday memiliki tiga parameter, `$year` digunakan untuk menentukan tahun, `$local` digunakan untuk menentukan apakah akan menggunakan sumber data lokal atau tidak, dan terakhir adalah `$defaultSunday` untuk menentukan apakah akan menggunakan hari Minggu sebagai default hari libur. Hanya `$year` yang wajib untuk diisi.

`check($date, $bool = TRUE)`

Method yang digunakan untuk mengecekan tanggal yang diberikan apakah merupakan hari libur atau bukan. Kita bisa menentukan apakah akan mengembalikan nilai boolean (TRUE/FALSE) atau object yaitu dengan format seperti berikut:

```php
{
  "status": TRUE,
  "result": {
    "date": "2020-01-01",
    "description": "Hari libur awal tahun"
  }
}
```

`addDefaultDay($day, $description)`

Digunakan untuk menambahkan default hari libur berdasarkan nama hari (format Indonesia). Misalnya membuat default hari libur pada hari Selasa.

`addDefaultDate($date, $description)`

Digunakan untuk menambahkan default hari libur berdasarkan tanggal tertentu (format `Y-m-d`). Misalnya membuat default hari libur pada tanggal 2020-01-10.

`getHolidays()`

Digunakan untuk mendapatkan semua hari libur (libur nasional + default date).

`getDefaultDays()`

Digunakan untuk mendapatkan semua data hari libur berdasarkan nama hari.

`getDefaultDates()`

Digunakan untuk mendapatkan semua data hari libur berdasarkan tanggal yang sudah ditentukan sebelumnya.

### Lisensi

MIT
