<?php
global $lang, $var, $db, $CFG, $adminInfo, $addons, $provisioning, $gateways;

$l = $var['l'] = $lang['MARKETPLACE'];
title($l['TITLE']);
menu("addons");
$tpl = "marketplace";

$tabs = [
    1 => "addons",
    2 => "provisioning",
    3 => "payment",
    4 => "domain",
    5 => "dns",
    6 => "encashment",
    7 => "scoring",
    8 => "letter",
    9 => "sms",
    10 => "telephone",
    11 => "telephone_log",
];

if (!empty($_GET['install']) && !empty($_GET['name'])) {
    if (!is_dir(__DIR__ . "/../../modules/" . basename($_GET['install']))) {
        exit;
    }

    $res = @file_get_contents("https://marketplace.sourcedesk.de/?" . urlencode($_GET['install']));
    $res = @json_decode($res, true);

    if (!is_array($res) || !count($res)) {
        exit;
    }

    $found = false;

    foreach ($res as $r) {
        if ($r['module'] == $_GET['name']) {
            $found = true;
            break;
        }
    }

    if (!$found) {
        exit;
    }

    $type = basename($_GET['install']);
    $module = basename($_GET['name']);

    if (is_dir($dir = __DIR__ . "/../../modules/$type/$module")) {
        if (!Update::deleteDirectory($dir)) {
            exit;
        }
    }

    $res = @file_get_contents("https://marketplace.sourcedesk.de/download.php?type=" . urlencode($type) . "&module=" . urlencode($module) . "&key=" . urlencode($CFG['LICENSE_KEY']));

    $tmp = __DIR__ . "/../../lib/tmp/" . uniqid() . time() . rand(100000, 999999);
    if (!@file_put_contents($tmp . ".zip", $res)) {
        exit;
    }

    $zip = new ZipArchive;
    if (!$zip->open($tmp . ".zip")) {
        exit;
    }

    if (!@mkdir(__DIR__ . "/../../modules/$type/$module")) {
        exit;
    }

    if (!$zip->extractTo(__DIR__ . "/../../modules/$type/$module")) {
        exit;
    }
    $zip->close();

    @unlink($tmp . ".zip");

    die("ok");
}

if (isset($_GET['tab']) && is_numeric($_GET['tab']) && array_key_exists($_GET['tab'], $tabs)) {
    $t = $tabs[$_GET['tab']];

    $res = @file_get_contents("https://marketplace.sourcedesk.de/?" . urlencode($t) . "&lang=" . $adminInfo->language);
    $res = @json_decode($res, true);

    if (!is_array($res) || !count($res)) {
        die('<div class="alert alert-info">' . $l['NOTHING'] . '</div>');
    }

    echo '<ul class="list-group">';

    foreach ($res as $m) {
        $status = '<span class="label label-primary">' . $l['RTI'] . '</span>';
        $dl = "https://marketplace.sourcedesk.de?" . urlencode($t) . "&m=" . urlencode($m["module"]) . "&k=" . $CFG['LICENSE_KEY'];

        if ($m['status'] != "ready") {
            $status = '<span class="label label-warning">' . $l['ID'] . '</span>';
            $dl = '';
        } else {
            switch ($t) {
                case "addons":
                    $as = $addons->get();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getInfo("version") >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "provisioning":
                    $as = $provisioning->get();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "payment":
                    $as = $gateways->get();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "domain":
                    $as = DomainHandler::getRegistrars();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "dns":
                    $as = DNSHandler::getDrivers();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "encashment":
                    $as = EncashmentHandler::getDrivers();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "scoring":
                    $as = ScoringHandler::getDrivers();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "letter":
                    $as = LetterHandler::getDrivers();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "sms":
                    $as = SMSHandler::getDrivers();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "telephone":
                    $obj = new TelephoneHandler;
                    $as = $obj->get();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;

                case "telephone_log":
                    $obj = new TelephoneLogHandler;
                    $as = $obj->get();

                    if (array_key_exists($m["module"], $as)) {
                        $a = $as[$m["module"]];
                        if ($a->getVersion() >= $m["version"]) {
                            $status = '<span class="label label-success">' . $l['CV'] . '</span>';
                            $dl = '';
                        } else {
                            $status = '<span class="label label-danger">' . $l['UA'] . '</span>';
                        }
                    }
                    break;
            }
        }

        if (!empty($dl)) {
            $url = "https://marketplace.sourcedesk.de/download.php?type=" . urlencode($t) . "&module=" . urlencode($m["module"]) . "&key=" . urlencode($CFG['LICENSE_KEY']);
            $dl = "<span><a href=\"#\" class=\"btn btn-default btn-xs mp-install\" data-type=\"$t\" data-name=\"{$m['module']}\">{$l['DOWNLOAD']}</a> <a href=\"$url\" class=\"btn btn-default btn-xs\" target=\"_blank\">{$l['DOWNLOAD2']}</a></span>";
        }

        echo '<li class="list-group-item"><b>' . str_replace("&amp;", "&", htmlentities($m['name'])) . '</b><span class="pull-right">' . $status . '</span><br />' . $l['CV'] . ': ' . htmlentities($m['version']) . '<span class="pull-right">' . $dl . '</li>';
    }

    echo '</ul>';

    echo '<script>$(".mp-install").unbind("click").click(mpInstall);</script>';

    exit;
}
