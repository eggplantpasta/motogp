<?php

namespace MotoGp;

class Utility {

    // Convert a date string into a "time ago" format
    public static function timeAgo($dateString): string
    {
        $date = new \DateTime($dateString);
        $now = new \DateTime();
        $interval = $now->diff($date);
        
        if ($interval->y > 0) return $interval->y . " year" . ($interval->y > 1 ? "s" : "") . " ago";
        if ($interval->m > 0) return $interval->m . " month" . ($interval->m > 1 ? "s" : "") . " ago";
        if ($interval->d > 0) return $interval->d . " day" . ($interval->d > 1 ? "s" : "") . " ago";
        if ($interval->h > 0) return $interval->h . " hour" . ($interval->h > 1 ? "s" : "") . " ago";
        if ($interval->i > 0) return $interval->i . " minute" . ($interval->i > 1 ? "s" : "") . " ago";
        return "just now";
    }

    public static function dump($data): string
    {
        return '<pre>' . print_r($data, true) . '</pre>';
    }
}
