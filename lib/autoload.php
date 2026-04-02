<?php

function class_loader2($class_name)
{
    $path = "";
    $ex = explode('\\', $class_name);
    foreach ($ex as $part) {
        $path .= "/" . $part;
    }

    if ($class_name == "PDFInvoice" && file_exists(__DIR__ . "/PDFInvoice-custom.php")) {
        include_once __DIR__ . "/PDFInvoice-custom.php";
        return;
    }

    if (file_exists(__DIR__ . $path . ".php")) {
        include_once __DIR__ . $path . ".php";
    }

}

if (!include __DIR__ . "/../vendor/autoload.php") {
    die("Please run Composer first");
}

spl_autoload_register('class_loader2', true, true);
