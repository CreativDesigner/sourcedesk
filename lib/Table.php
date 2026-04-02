<?php
// Class for table filter and pagination

class Table
{
    protected $itemNumber;
    protected $itemsPerPage = 20;
    protected $qry;
    protected $page;
    protected $filter;
    protected $order = [];
    protected $defOrder = ["", ""];
    protected $tableId = "";
    protected $adminId = 0;

    public function __construct($qry, array $filter = [], array $defOrder = ["", ""], $tableId = "")
    {
        global $db, $adminInfo;

        $this->defOrder = $defOrder;

        if (isset($adminInfo) && is_object($adminInfo)) {
            if (!empty($adminInfo->per_page)) {
                $this->itemsPerPage = max(intval($adminInfo->per_page), 10);
            }

            $this->adminId = $adminInfo->ID;
        }

        $this->tableId = $tableId;

        $this->qry = $qry;
        $this->filter = $filter;

        $countQry = str_replace("*", "COUNT(*) c", $qry);
        $filterQry = $this->filterQry();

        if ($filterQry) {
            if (strpos($countQry, "WHERE") === false) {
                $countQry .= " WHERE ";
            } else {
                $countQry .= " AND ";
            }
            $countQry .= $filterQry;
        }

        $this->itemNumber = $db->query($countQry)->fetch_object()->c;

        $this->page = !empty($_REQUEST['table_page']) ? intval($_REQUEST['table_page']) : 1;
        $this->page = max(0, $this->page);
        $this->page = min($this->page, $this->getPageNum());

        if (isset($_POST['export_fields']) && is_array($_POST['export_fields'])) {
            $this->export();
        }
    }

    public function qry($order = null, $limit = null, $offset = null)
    {
        global $db;

        $qry = $this->qry;
        $filterQry = $this->filterQry();

        if ($filterQry) {
            if (strpos($qry, "WHERE") === false) {
                $qry .= " WHERE ";
            } else {
                $qry .= " AND ";
            }
            $qry .= $filterQry;
        }

        if (!empty($_REQUEST['table_order_field']) && in_array($_REQUEST['table_order_field'], $this->order)) {
            $dir = $_REQUEST['table_order_dir'] ?? "";
            if ($dir != "DESC") {
                $dir = "ASC";
            }

            $order = $_REQUEST['table_order_field'] . " " . $dir;
            $this->saveOrder($_REQUEST['table_order_field'], $dir);
        }

        if ($order) {
            $qry .= " ORDER BY $order";
        }

        if (!$offset) {
            $offset = $this->getOffset();
        }

        if (!$limit) {
            $limit = $this->itemsPerPage;
        }

        $qry .= " LIMIT $offset,$limit";
        return $db->query($qry);
    }

    protected function filterQry()
    {
        global $db;

        $qry = "";
        foreach ($this->filter as $k => $v) {
            if (isset($_REQUEST["table_$k"]) && trim($_REQUEST["table_$k"]) != "") {
                if ($v['type'] == "like") {
                    if (!empty($v['trim']) && substr($_REQUEST['table_' . $k], 0, strlen($v['trim'])) == $v['trim']) {
                        $_REQUEST['table_' . $k] = substr($_REQUEST['table_' . $k], strlen($v['trim']));
                    }

                    $qry .= '`' . $db->real_escape_string($k) . '` LIKE \'%' . $db->real_escape_string(trim($_REQUEST['table_' . $k])) . '%\' AND ';
                } else if ($v['type'] == "selectfirst") {
                    $len = strlen(trim($_REQUEST['table_' . $k]));
                    $qry .= 'SUBSTR(`' . $db->real_escape_string($k) . '`, 0, ' . $len . ') = \'' . $db->real_escape_string(trim($_REQUEST['table_' . $k])) . '\' AND ';
                } else {
                    $qry .= '`' . $db->real_escape_string($k) . '` = \'' . $db->real_escape_string(trim($_REQUEST['table_' . $k])) . '\' AND ';
                }
            }
        }

        $qry = substr($qry, 0, -5);
        return $qry;
    }

    public function orderHeader($column, $name = "")
    {
        array_push($this->order, $column);

        if (empty($name)) {
            $name = $column;
        }

        if (empty($_REQUEST['table_order_field'])) {
            if ($o = $this->getOrder()) {
                $ex = explode(" ", $o);
                $_REQUEST['table_order_dir'] = array_pop($ex);
                $_REQUEST['table_order_field'] = implode(" ", $ex);
            } else {
                $_REQUEST['table_order_field'] = $this->defOrder[0];
                $_REQUEST['table_order_dir'] = $this->defOrder[1];
            }
        }

        $order = $_REQUEST['table_order_field'] ?? "";

        $pars = [
            "table_order_field" => $column,
            "table_order_dir" => "ASC",
        ];

        $icon = "";

        if ($order == $column) {
            if (($_REQUEST['table_order_dir'] ?? "ASC") == "ASC") {
                $pars["table_order_dir"] = "DESC";
                $icon = " <i class=\"fa fa-caret-up\"></i>";
            } else {
                $icon = " <i class=\"fa fa-caret-down\"></i>";
            }
        }

        unset($_GET['table_order_dir'], $_GET['table_order_field'], $_GET['table_page']);
        $link = "?" . http_build_query($_GET) . (count($_GET) ? "&" : "") . http_build_query($pars);

        return "<a href=\"$link\">$name$icon</a>";
    }

    public function getHeader()
    {
        global $lang, $ari;
        $l = $lang['TABLE_CLASS'];

        ob_start();

        if (count($this->filter)) {
            $col = "12";
            if (count($this->filter) >= 2) {
                $col = "6";
            }
            if (count($this->filter) >= 3) {
                $col = "4";
            }

            $in = boolval($this->filterQry());

            ?>
            <div class="panel-group" id="table_accordion" role="tablist" aria-multiselectable="true">
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="table_headingOne">
                    <h4 class="panel-title">
                        <a role="button" data-toggle="collapse" href="#table_collapseOne" aria-expanded="<?=strval($in);?>" aria-controls="table_collapseOne">
                            <?=$l['TITLE'];?>
                        </a>
                    </h4>
                    </div>
                    <div id="table_collapseOne" class="panel-collapse collapse<?=$in ? ' in' : '';?>" role="tabpanel" aria-labelledby="table_headingOne">
                        <div class="panel-body">
                            <form method="GET">
                            <div class="row" style="margin-top: -10px;">
                                <?php foreach ($_GET as $k => $v) {if (substr($k, 0, 6) == "table_") {
                continue;
            }
                ?>
                                <input type="hidden" name="<?=htmlentities($k);?>" value="<?=htmlentities($v);?>">
                                <?php }?>

                                <?php foreach ($this->filter as $k => $v) {?>
                                    <div class="col-md-<?=$col;?>" style="margin-top: 10px;">
                                        <label><?=$v['name'];?></label>
                                        <?php if ($v['type'] == "select" || $v['type'] == "selectfirst") {?>
                                        <select name="table_<?=$k;?>" class="form-control">
                                            <option value=""><?=$l['NOLIMIT'];?></option>
                                            <?php foreach ($v['options'] as $ok => $ov) {?>
                                            <option value="<?=htmlentities($ok);?>"<?=isset($_REQUEST['table_' . $k]) && strval(trim($_REQUEST['table_' . $k])) == strval($ok) ? ' selected=""' : '';?>><?=htmlentities($ov);?></option>
                                            <?php }?>
                                        </select>
                                        <?php } else if ($v['type'] == "like") {?>
                                        <input type="text" name="table_<?=$k;?>" class="form-control" value="<?=isset($_REQUEST['table_' . $k]) ? strval(trim($_REQUEST['table_' . $k])) : '';?>" placeholder="<?=$l['NOLIMIT'];?>">
                                        <?php }?>
                                    </div>
                                <?php }?>
                            </div>

                            <input type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;" value="<?=$l['FILTER'];?>">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
}

        if ($ari->check(70) && is_object($sql = $this->qry(null, 1)) && $sql->num_rows) {
            $columns = array_keys($sql->fetch_assoc());
            if (count($columns)) {
                ?>
                <div class="panel-group" id="table_accordion2" role="tablist" aria-multiselectable="true">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="table_headingTwo">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" href="#table_collapseTwo" aria-expanded="false" aria-controls="table_collapseTwo">
                                <?=$l['EXPORT'];?>
                            </a>
                        </h4>
                        </div>
                        <div id="table_collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="table_headingTwo">
                            <div class="panel-body">
                                <form method="POST" target="_blank">
                                    <div class="form-group">
                                        <label><?=$l['FIELDS'];?></label>
                                        <select name="export_fields[]" multiple="" class="form-control">
                                            <?php foreach ($columns as $column) {?>
                                            <option selected=""><?=$column;?></option>
                                            <?php }?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label><?=$l['FORMAT'];?></label>
                                        <div class="checkbox" style="margin-top: 0;">
                                            <label class="radio-inline">
			                                    <input type="radio" name="export_format" value="csv" checked="">
                                                CSV
                                            </label>
                                            <label class="radio-inline">
			                                    <input type="radio" name="export_format" value="json">
                                                JSON
                                            </label>
                                            <label class="radio-inline">
			                                    <input type="radio" name="export_format" value="xml">
                                                XML
                                            </label>
                                            <label class="radio-inline">
			                                    <input type="radio" name="export_format" value="html">
                                                HTML
                                            </label>
                                        </div>
                                    </div>

                                    <script>
                                    $("[name=export_format]").click(function() {
                                        $("#export_header").hide();
                                        $("[name=export_format]").each(function () {
                                            if ($(this).is(":checked") && ($(this).val() == "csv" || $(this).val() == "html")) {
                                                $("#export_header").show();
                                            }
                                        });
                                    });
                                    </script>

                                    <div class="checkbox" id="export_header" style="margin-top: -10px;">
                                        <label class="checkbox-inline">
		                                    <input type="checkbox" name="export_header" value="1" checked="">
                                            <?=$l['HEADER'];?>
		                                </label>
                                    </div>

                                    <input type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;" value="<?=str_replace("%x", $this->itemNumber, $l['EXPORT_NOW' . ($this->itemNumber == 1 ? "1" : "")]);?>">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
}
        }

        echo '<div style="float: left;">';
        if ($this->itemNumber == 1) {
            echo $lang['LOGS']['ONE_ENTRY'];
        } else {
            echo str_replace("%x", $this->itemNumber, $lang['LOGS']['X_ENTRIES']);
        }
        echo '</div>';

        echo '<div style="float: right;">';

        if (($pages = $this->getPageNum()) > 1) {
            echo '<form method="GET">';
        }

        echo str_replace("%s", $pages, $lang['ADMIN_LOG']['PAGING']);

        if ($pages == 1) {
            echo " 1";
        } else {
            foreach ($_GET as $k => $v) {
                echo '<input type="hidden" name="' . htmlentities($k) . '" value="' . htmlentities($v) . '" />';
            }

            echo ' <select name="table_page" onchange="form.submit()">';
            for ($i = 1; $i <= $pages; $i++) {
                $selected = $this->page == $i ? ' selected=""' : '';
                echo '<option' . $selected . '>' . $i . '</option>';
            }
            echo '</select>';
        }

        echo " " . str_replace("%s", $pages, $lang['ADMIN_LOG']['PAGING_2']);

        if ($pages > 1) {
            echo '</form>';
        }

        echo '</div><br /><br />';

        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    public function getFooter()
    {
        global $lang;

        ob_start();

        unset($_GET['table_page']);
        $url = "?" . http_build_query($_GET) . "&";

        echo '<center>';

        echo '<a href="' . $url . 'table_page=' . ($this->page - 1) . '" class="btn btn-default"' . (($this->page - 1) <= 0 ? ' disabled=""' : '') . '>' . $lang['ADMIN_LOG']['PREVIOUS'] . '</a>';
        echo ' <a href="' . $url . 'table_page=' . ($this->page + 1) . '" class="btn btn-default"' . (($this->page + 1) > $this->getPageNum() ? ' disabled=""' : '') . '>' . $lang['ADMIN_LOG']['NEXT'] . '</a>';

        echo '</center>';

        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    protected function saveOrder($field, $dir)
    {
        global $db, $CFG;

        if (!$this->adminId || !$this->tableId) {
            return;
        }

        $field = $db->real_escape_string($field);
        $dir = $db->real_escape_string($dir);

        if ($this->getOrder()) {
            $db->query("UPDATE admin_order SET field = '$field', direction = '$dir' WHERE admin = {$this->adminId} AND `table` = '" . $db->real_escape_string($this->tableId) . "'");
        } else {
            $db->query("INSERT INTO admin_order (admin, `table`, field, direction) VALUES ({$this->adminId}, '" . $db->real_escape_string($this->tableId) . "', '$field', '$dir')");
        }
    }

    protected function getOrder()
    {
        global $db, $CFG;

        if (!$this->adminId || !$this->tableId) {
            return "";
        }

        $sql = $db->query("SELECT field, direction FROM admin_order WHERE admin = {$this->adminId} AND `table` = '" . $db->real_escape_string($this->tableId) . "' ORDER BY ID DESC LIMIT 1");
        if ($sql->num_rows) {
            $row = $sql->fetch_object();
            return $row->field . " " . $row->direction;
        }

        return "";
    }

    protected function getPageNum()
    {
        return max(1, ceil($this->itemNumber / $this->itemsPerPage));
    }

    protected function getOffset()
    {
        return ($this->page - 1) * $this->itemsPerPage;
    }

    protected function export()
    {
        ob_end_clean();

        $fields = (array) $_POST['export_fields'];
        $format = strval($_POST['export_format'] ?? "");

        if (!in_array($format, ["csv", "json", "xml", "html"])) {
            $format = "csv";
        }

        $header = boolval($_POST['export_header'] ?? false);

        $sql = $this->qry(null, $this->itemNumber, 0);

        switch ($format) {
            case "csv":
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="Export.csv"');

                $out = fopen('php://output', 'w');

                if ($header) {
                    fputcsv($out, $fields);
                }

                while ($row = $sql->fetch_object()) {
                    $myFields = [];

                    foreach ($fields as $field) {
                        $myFields[] = $row->$field;
                    }

                    fputcsv($out, $myFields);
                }

                fclose($out);
                break;

            case "json":
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="Export.json"');

                $data = [];

                while ($row = $sql->fetch_object()) {
                    $myData = [];

                    foreach ($fields as $field) {
                        $myData[$field] = $row->$field;
                    }

                    array_push($data, $myData);
                }

                echo json_encode($data);
                break;

            case "xml":
                header('Content-Type: text/xml');
                header('Content-Disposition: attachment; filename="Export.xml"');

                $xml = new SimpleXMLElement('<xml/>');

                while ($row = $sql->fetch_object()) {
                    $record = $xml->addChild('record');

                    foreach ($fields as $field) {
                        $record->addChild($field, $row->$field);
                    }
                }

                echo $xml->asXML();
                break;

            case "html":
                header('Content-Type: text/html');
                header('Content-Disposition: attachment; filename="Export.html"');
                ?>
                <style>
                table, th, td {
                    border: 1px solid black;
                    border-collapse: collapse;
                    padding: 10px;
                    text-align: left;
                }
                </style>

                <table>
                    <?php if ($header) {?>
                    <thead>
                        <tr>
                            <?php foreach ($fields as $field) {?>
                            <th><?=htmlentities($field);?></th>
                            <?php }?>
                        </tr>
                    </thead>
                    <?php }?>

                    <tbody>
                    <?php while ($row = $sql->fetch_object()) {?>
                        <tr>
                            <?php foreach ($fields as $field) {?>
                            <td><?=nl2br(htmlentities($row->$field));?></td>
                            <?php }?>
                        </tr>
                    <?php }?>
                    </tbody>
                </table>
                <?php
break;
        }

        exit;
    }
}