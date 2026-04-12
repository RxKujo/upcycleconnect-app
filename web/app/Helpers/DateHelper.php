<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function formatFrench($date)
    {
        if (!$date) {
            return '';
        }
        $carbon = Carbon::parse($date);
        return $carbon->format('d/m/Y H:i');
    }

    public static function formatFrenchWithPeriod($date)
    {
        if (!$date) {
            return '';
        }
        $carbon = Carbon::parse($date);
        $hour = (int)$carbon->format('H');
        $period = $hour < 12 ? 'matin' : 'après-midi';
        return $carbon->format('d/m/Y') . ' ' . $carbon->format('H:i') . ' (' . $period . ')';
    }

    public static function formatForInput($date)
    {
        if (!$date) {
            return '';
        }
        return Carbon::parse($date)->format('Y-m-d\TH:i');
    }

    public static function getDateFromInput($date, $hour, $minute)
    {
        if (!$date) {
            return null;
        }
        try {
            $parts = explode('/', $date);
            if (count($parts) === 3) {
                $day = $parts[0];
                $month = $parts[1];
                $year = $parts[2];
                return Carbon::createFromFormat('d/m/Y H:i', "$day/$month/$year " . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minute, 2, '0', STR_PAD_LEFT))->format('Y-m-d H:i:s');
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    public static function getDateParts($date)
    {
        if (!$date) {
            return ['day' => '', 'month' => '', 'year' => '', 'hour' => '', 'minute' => ''];
        }
        $carbon = Carbon::parse($date);
        return [
            'day' => $carbon->format('d'),
            'month' => $carbon->format('m'),
            'year' => $carbon->format('Y'),
            'hour' => $carbon->format('H'),
            'minute' => $carbon->format('i'),
        ];
    }
}
