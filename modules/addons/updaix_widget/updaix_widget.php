<?php
// Addon for a widget for updAIX server updates

class updAIXWidget extends Addon
{
    public static $shortName = "updaix_widget";

    public function __construct($language)
    {
        $this->language = $language;
        $this->name = self::$shortName;
        parent::__construct();

        if (!include (__DIR__ . "/language/$language.php")) {
            throw new ModuleException();
        }

        if (!is_array($addonlang) || !isset($addonlang["NAME"])) {
            throw new ModuleException();
        }

        $this->lang = $addonlang;

        $this->info = array(
            'name' => $this->getLang('NAME'),
            'version' => "1.0",
            'company' => "sourceWAY.de",
            'url' => "https://sourceway.de/",
        );
    }

    public function getSettings()
    {
        return array(
            "userid" => array("placeholder" => "9qKLKo", "label" => $this->getLang('USERID'), "type" => "text"),
            "apikey" => array("placeholder" => "uam9go11fpbv1aupzr8ni5l3uic6tvse", "label" => $this->getLang('APIKEY'), "type" => "password"),
        );
    }

    public function delete()
    {
        return $this->deleteDir(realpath(__DIR__));
    }

    public function getWidgets()
    {
        return array(
            "updaix_widget" => array("Server-Updates", $this->statusWidget()),
        );
    }

    public function clientPages()
    {
        return array(
            "updaix_widget_cron" => "cronjob",
        );
    }

    public function hooks()
    {
        return array(
            array("MaintenanceAllowedPages", "maintenance", 0),
        );
    }

    public function maintenance()
    {
        return ["updaix_widget_cron"];
    }

    public function activate()
    {
        parent::activate();
        $this->dbSchema();
    }

    public function deactivate()
    {
        global $db, $CFG;
        parent::deactivate();
        $db->query("DROP TABLE `mod_updaix_widget`");
    }

    public function cronjob()
    {
        global $db, $CFG;
        $this->dbSchema();

        $ch = curl_init("https://app.updaix.de/external?server");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->options['userid'] . ":" . $this->options['apikey']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["active" => "1", "open_updates" => "1", "list_updates" => "1"]));
        $res = json_decode(curl_exec($ch));
        curl_close($ch);

        $db->query("DELETE FROM `mod_updaix_widget`");

        if ($res->status != 100) {
            exit;
        }

        if (count($res->data) == 0) {
            exit;
        }

        foreach ($res->data as $srv) {
            $packages = "";
            foreach ($srv->updates->list as $upd) {
                if (!$upd->status) {
                    $packages .= $upd->package . ", ";
                }
            }
            $packages = rtrim($packages, ", ");

            $db->query("INSERT INTO `mod_updaix_widget` (`id`, `hostname`, `updates`, `packages`) VALUES ('" . $db->real_escape_string($srv->id) . "', '" . $db->real_escape_string($srv->hostname) . "', " . intval($srv->updates->open) . ", '" . $db->real_escape_string($packages) . "')");
        }

        exit;
    }

    public function dbSchema()
    {
        global $db, $CFG;

        if (!$db->query("SELECT 1 FROM mod_updaix_widget")) {
            $db->query("CREATE TABLE `mod_updaix_widget` (
			`id` varchar(255) NOT NULL,
			`hostname` varchar(255) NOT NULL DEFAULT '',
			`updates` int(11) NOT NULL DEFAULT '0'
			)");
            $db->query("ALTER TABLE `mod_updaix_widget` ADD PRIMARY KEY (`id`);");
        }

        if (!$db->query("SELECT `packages` FROM mod_updaix_widget")) {
            $db->query("ALTER TABLE `mod_updaix_widget` ADD `packages` LONGTEXT;");
        }
    }

    public function statusWidget()
    {
        global $db, $CFG;

        if (isset($_GET['updaix_status_widget_do'])) {
            $ch = curl_init("https://app.updaix.de/external?updates");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $this->options['userid'] . ":" . $this->options['apikey']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(["server" => $_GET['updaix_status_widget_do'], "status" => "1", "packages" => "not_ignored"]));
            $res = json_decode(curl_exec($ch));
            curl_close($ch);

            alog("general", "updaix_do", $_GET['updaix_status_widget_do']);

            if ($_GET['updaix_status_widget_do'] == "all") {
                $db->query("DELETE FROM `mod_updaix_widget`");
            } else {
                $db->query("DELETE FROM `mod_updaix_widget` WHERE id = '" . $db->real_escape_string($_GET['updaix_status_widget_do']) . "'");
            }

            die($res->status == 100 ? "ok" : "nok");
        }

        @$sql = $db->query("SELECT * FROM `mod_updaix_widget`");
        if (!$sql || $sql->num_rows == 0) {
            return "";
        }

        ob_start();
        ?>
		<div class="table-responsive">
			<table class="table table-striped table-bordered" style="margin-bottom: 0;">
				<tr>
					<th><?=$this->getLang('HOSTNAME');?></th>
					<th width="180px"><center><?=$this->getLang('OPEN');?></center></th>
					<th width="250px" style="text-align: right;"><a href="#" id="updaix_status_widget_do_all"><?=$this->getLang('DOALL');?></a></th>
				</tr>
				<?php while ($srv = $sql->fetch_object()) {?>
				<tr>
					<td><a href="https://app.updaix.de/server?<?=$srv->id;?>" target="_blank"><?=htmlentities($srv->hostname);?></a></td>
					<td><center><a href="#" onclick="return false;" data-toggle="tooltip" title="<?=htmlentities($srv->packages);?>"><?=number_format($srv->updates, 0, ',', '.');?></a></center></td>
					<td style="text-align: right;" class="updaix_status_widget_third_row"><a href="#" class="updaix_status_widget_do" data-id="<?=$srv->id;?>"><?=$this->getLang('DO');?></a></td>
				</tr>
				<?php }?>
			</table>
		</div>

		<script>
		$("#updaix_status_widget_do_all").click(function(e){
			e.preventDefault();
			var p = $(this).parent();
			p.html("<i class='fa fa-spinner fa-spin'></i> <?=$this->getLang('DOING');?>");
			$(".updaix_status_widget_third_row").html("<i class='fa fa-spinner fa-spin'></i> <?=$this->getLang('DOING');?>");

			$.get("?updaix_status_widget_do=all", function(r){
				if(r == "ok"){
					p.html("<i class='fa fa-check' style='color: green;'></i> <span style='color: green;'><?=$this->getLang('OK');?></span>");
					$(".updaix_status_widget_third_row").html("<i class='fa fa-check' style='color: green;'></i> <span style='color: green;'><?=$this->getLang('OK');?></span>");
				} else {
					p.html("<i class='fa fa-times' style='color: red;'></i> <span style='color: red;'><?=$this->getLang('NOK');?></span>");
					$(".updaix_status_widget_third_row").html("<i class='fa fa-times' style='color: red;'></i> <span style='color: red;'><?=$this->getLang('NOK');?></span>");
				}
			})
		});

		$(".updaix_status_widget_do").click(function(e){
			e.preventDefault();
			var i = $(this).data("id");
			var p = $(this).parent();
			p.html("<i class='fa fa-spinner fa-spin'></i> <?=$this->getLang('DOING');?>");

			$.get("?updaix_status_widget_do=" + i, function(r){
				if(r == "ok"){
					p.html("<i class='fa fa-check' style='color: green;'></i> <span style='color: green;'><?=$this->getLang('OK');?></span>");
				} else {
					p.html("<i class='fa fa-times' style='color: red;'></i> <span style='color: red;'><?=$this->getLang('NOK');?></span>");
				}
			})
		});
		</script>
		<?php
$c = ob_get_contents();
        ob_end_clean();
        return $c;
    }
}
