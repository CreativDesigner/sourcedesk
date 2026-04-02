<?php

class Monitoring {
    public static function serviceTypes() {
        require_once __DIR__ . "/MonitoringService.php";

        $types = [];

        foreach (get_declared_classes() as $c) {
            if (is_subclass_of($c, "MonitoringService")) {
                array_push($types, $c);
            }
        }

        return $types;
    }
}