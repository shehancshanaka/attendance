<?php
class Holiday {
    private static $holidays = [
        // National Holidays
        '01-01' => 'New Year\'s Day',
        '01-14' => 'Tamil Thai Pongal Day',
        '01-15' => 'Duruthu Full Moon Poya Day',
        '02-04' => 'National Day',
        '02-23' => 'Navam Full Moon Poya Day',
        '03-23' => 'Medin Full Moon Poya Day',
        '04-12' => 'Sinhala and Tamil New Year Eve',
        '04-13' => 'Sinhala and Tamil New Year Day',
        '04-14' => 'Bak Full Moon Poya Day',
        '05-01' => 'May Day',
        '05-22' => 'Vesak Full Moon Poya Day',
        '05-23' => 'Day following Vesak Full Moon Poya Day',
        '06-21' => 'Poson Full Moon Poya Day',
        '07-20' => 'Esala Full Moon Poya Day',
        '08-19' => 'Nikini Full Moon Poya Day',
        '09-17' => 'Binara Full Moon Poya Day',
        '10-17' => 'Vap Full Moon Poya Day',
        '11-15' => 'Il Full Moon Poya Day',
        '12-15' => 'Unduvap Full Moon Poya Day',
        '12-25' => 'Christmas Day'
    ];

    public static function isHoliday($date) {
        $dateStr = $date->format('m-d');
        return isset(self::$holidays[$dateStr]);
    }

    public static function getHolidayName($date) {
        $dateStr = $date->format('m-d');
        return self::$holidays[$dateStr] ?? null;
    }
} 