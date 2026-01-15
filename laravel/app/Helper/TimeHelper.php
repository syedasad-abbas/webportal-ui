<?php

if (!function_exists('minutesToTime')) {
    function minutesToTime($minutes) {
        return str_pad(intdiv($minutes, 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT);
    }
}
