<?php
global $ari, $lang, $CFG, $db, $var, $addons;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$pl = $lang["ADDONS"];

title($pl["ADDONS"]);
menu("addons");

// Check admin rights
if ($ari->check(44)) {
    $tpl = "addons";
    $deleted = [];

    if (isset($_GET['activate']) && isset($addons->get()[$_GET['activate']]) && !$addons->get()[$_GET['activate']]->isActive() && $addons->get()[$_GET['activate']]->activate()) {
        alog("addon_management", "activated", $_GET['activate']);
        $var['msg'] = "<div class='alert alert-success'>" . $pl['ACTIVATED'] . "</div>";
    }

    if (isset($_GET['deactivate']) && isset($addons->get()[$_GET['deactivate']]) && $addons->get()[$_GET['deactivate']]->isActive() && $addons->get()[$_GET['deactivate']]->deactivate()) {
        alog("addon_management", "deactivated", $_GET['deactivate']);
        $var['msg'] = "<div class='alert alert-success'>" . $pl['DEACTIVATED'] . "</div>";
    }

    if (isset($_GET['delete']) && isset($addons->get()[$_GET['delete']]) && !$addons->get()[$_GET['delete']]->isActive() && method_exists($addons->get()[$_GET['delete']], 'delete') && $addons->get()[$_GET['delete']]->delete()) {
        alog("addon_management", "deleted", $_GET['delete']);
        array_push($deleted, $_GET['delete']);
        $var['msg'] = "<div class='alert alert-success'>" . $pl['DELETED'] . "</div>";
    }

    if (isset($_POST['activate_selected']) && is_array($_POST['addon'])) {
        $d = 0;
        foreach ($_POST['addon'] as $addon) {
            if (isset($addons->get()[$addon]) && !$addons->get()[$addon]->isActive() && $addons->get()[$addon]->activate()) {
                $d++;
                alog("addon_management", "activated", $addon);
            }
        }

        if ($d == 1) {
            $var['msg'] = "<div class='alert alert-success'>" . $pl['ACTIVATED1'] . "</div>";
        } else if ($d > 0) {
            $var['msg'] = "<div class='alert alert-success'>$d " . $pl['ACTIVATEDX'] . "</div>";
        }

    }

    if (isset($_POST['deactivate_selected']) && is_array($_POST['addon'])) {
        $d = 0;
        foreach ($_POST['addon'] as $addon) {
            if (isset($addons->get()[$addon]) && $addons->get()[$addon]->isActive() && $addons->get()[$addon]->deactivate()) {
                $d++;
                alog("addon_management", "deactivated", $addon);
            }
        }

        if ($d == 1) {
            $var['msg'] = "<div class='alert alert-success'>" . $pl['DEACTIVATED1'] . "</div>";
        } else if ($d > 0) {
            $var['msg'] = "<div class='alert alert-success'>$d " . $pl['DEACTIVATEDX'] . "</div>";
        }

    }

    if (isset($_POST['delete_selected']) && is_array($_POST['addon'])) {
        $d = 0;
        foreach ($_POST['addon'] as $addon) {
            if (isset($addons->get()[$addon]) && method_exists($addons->get()[$addon], 'delete') && $addons->get()[$addon]->delete()) {
                $d++;
                alog("addon_management", "deleted", $addon);
                array_push($deleted, $addon);
            }
        }

        if ($d == 1) {
            $var['msg'] = "<div class='alert alert-success'>" . $pl['DELETED1'] . "</div>";
        } else if ($d > 0) {
            $var['msg'] = "<div class='alert alert-success'>$d " . $pl['DELETEDX'] . "</div>";
        }

    }

    if (isset($_POST['save'])) {
        unset($_POST['save']);
        foreach ($_POST as $k => $v) {
            if (!is_array($v) || !array_key_exists($k, $addons->get())) {
                continue;
            }

            foreach ($v as $k2 => $v2) {
                if (is_array($v2)) {
                    $v2 = serialize($v2);
                }

                $addons->get()[$k]->setOption($k2, $v2);
            }
            alog("addon_management", "settings", $k);
        }

        $var['msg'] = "<div class='alert alert-success'>" . $pl['SAVED'] . "</div>";
    }

    $var['admins'] = array();
    $sql = $db->query("SELECT ID, name FROM admins ORDER BY name ASC");
    while ($r = $sql->fetch_object()) {
        $var['admins'][$r->ID] = $r->name;
    }

    $addons = new AddonHandler;
    $addons->construct();
    $var['addons'] = $addons->get();

    foreach ($deleted as $k) {
        unset($var['addons'][$k]);
    }

    $var['registeredHooks'] = [];
    foreach ($addons->getHooks() as $hp => $hu) {
        foreach ($hu as $hd) {
            $var['registeredHooks'][] = [
                "point" => $hp,
                "addon" => $hd[0],
                "method" => $hd[1],
            ];
        }
    }

    $var['adminPages'] = [];
    foreach ($addons->getDryAdminPages() as $page => $det) {
        $var['adminPages'][] = [
            "page" => $page,
            "addon" => $det[0],
            "method" => $det[1],
        ];
    }

    $var['adminMenu'] = [];
    foreach ($addons->getDryAdminMenu() as $name => $det) {
        $var['adminMenu'][] = [
            "name" => $name,
            "url" => $det[1],
            "addon" => $det[0],
        ];
    }

    $var['clientPages'] = [];
    foreach ($addons->getClientPages() as $page => $det) {
        $var['clientPages'][] = [
            "page" => $page,
            "addon" => $det[0],
            "method" => $det[1],
        ];
    }
} else {
    alog("general", "insufficient_page_rights", "addons");
    $tpl = "error";
}
