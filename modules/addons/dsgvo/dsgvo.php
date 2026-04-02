<?php
// Addon for supporting DSGVO
use setasign\Fpdi\TcpdfFpdi;

class DSGVOAddon extends Addon {
	public static $shortName = "dsgvo";

	public function __construct($language) {
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

		$this->info = Array(
			'name' => $this->getLang("NAME"),
			'version' => "1.1",
			'company' => "sourceWAY.de",
			'url' => "https://sourceway.de/",
		);
	}

	public function getSettings() {
		return Array(
			"show_warning" => Array("default" => "0", "label" => $this->getLang("SHOWW"), "type" => "checkbox"),
			"exclude_ips" => Array("default" => "", "label" => $this->getLang("INTERNALN"), "type" => "text", "placeholder" => $this->getLang("INTERNALNP")),
		);
	}

	public function activate() {
		global $CFG, $db;
		parent::activate();

		$db->query("ALTER TABLE `clients` ADD `dsgvo_av` INT(11) NOT NULL DEFAULT '0';");
	}

	public function delete() {
		return $this->deleteDir(realpath(__DIR__));
	}

	public function clientPages() {
		return Array("dsgvo" => "displayClientPage");
	}

	public function hooks() {
		return [
			["AdminCustomerDetailTable", "clientDetailTable", 0],
			["AdminClientProfileTicketLink", "clientRevoke", 0],
			["AdminAreaAdditionalJS", "adminWarning", 0],
		];
	}

	public function displayClientPage() {
		global $pars, $CFG, $title, $tpl, $var, $cur, $nfo, $user, $db, $dfo;

		if (!is_array($pars) || count($pars) < 1 || $pars[0] != "av") {
			header('Location: ' . $CFG['PAGEURL'] . 'dsgvo/av');
			exit;
		}

		switch ($pars[0]) {
		case 'av':
			if (isset($pars[1]) && $pars[1] == "pdf") {
				if (!$var['logged_in'] || !$user->get()['dsgvo_av']) {
					header('Content-type: application/pdf');
					header('Content-Disposition: inline; filename="' . $this->getLang("EXAMPLEF") . '.pdf"');
					header('Content-Transfer-Encoding: binary');
					header('Accept-Ranges: bytes');

					echo file_get_contents(__DIR__ . "/files/avexample.pdf");
				} else {
					require_once __DIR__ . "/../../../lib/tcpdf/tcpdf.php";
					require_once __DIR__ . "/../../../lib/fpdi2/autoload.php";

					$pdf = new TcpdfFpdi;
					$pages = $pdf->setSourceFile(__DIR__ . "/files/avtemplate.pdf");
					$pdf->SetPrintHeader(false);
					$pdf->SetPrintFooter(false);

					for ($i = 1; $i <= $pages; $i++) {
						$id = $pdf->importPage($i);
						$pdf->AddPage();
						$pdf->useTemplate($id);
					}

					$pdf->setPage(1);
					$pdf->SetFont('', 'B', 11);
					$pdf->SetXY(90, 80);
					$pdf->writeHTML($user->get()['company'], true, false, false, false, '');

					$pdf->SetFont('', '', 11);
					$pdf->SetXY(90, 85);
					$pdf->writeHTML($user->get()['name'], true, false, false, false, '');

					$pdf->SetXY(90, 90);
					$pdf->writeHTML($user->get()['street'] . " " . $user->get()['street_number'], true, false, false, false, '');

					$pdf->SetXY(90, 95);
					$pdf->writeHTML($user->get()['postcode'] . " " . $user->get()['city'], true, false, false, false, '');

					$pdf->SetXY(90, 100);
					$pdf->writeHTML($user->get()['country_name'], true, false, false, false, '');

					$signpage = 10;

					$pdf->setPage($signpage);

					$pdf->SetXY(29, 130);
					$pdf->writeHTML("Elektronisch unterzeichnet", true, false, false, false, '');

					$sql = $db->query("SELECT * FROM client_log WHERE user = " . $user->get()['ID'] . " AND `action` = 'AV-Vertrag geschlossen' ORDER BY ID DESC LIMIT 1");
					if ($sql->num_rows == 1) {
						$i = $sql->fetch_object();
						$timestamp = $dfo->format($i->time, true, true);
						$ip = $i->ip != "Admin" ? $i->ip : "";
					} else {
						$timestamp = "";
						$ip = "";
					}

					$pdf->SetXY(29, 135);
					$pdf->writeHTML($timestamp, true, false, false, false, '');

					$pdf->SetXY(29, 140);
					$pdf->writeHTML($ip, true, false, false, false, '');

					$pdf->SetXY(29, 146);
					$pdf->writeHTML($user->get()['name'], true, false, false, false, '');

					$pdf->SetXY(29, 151);
					$pdf->writeHTML($user->get()['company'], true, false, false, false, '');

					$pdf->SetXY(29, 156);
					$pdf->writeHTML("KdNr: " . $user->get()['ID'], true, false, false, false, '');

					$pdf->SetXY(115, 130);
					$pdf->writeHTML("Elektronisch unterzeichnet", true, false, false, false, '');

					$pdf->SetXY(115, 135);
					$pdf->writeHTML($timestamp, true, false, false, false, '');

					$pdf->SetXY(115, 140);
					$pdf->writeHTML(substr(hash("sha256", $CFG['HASH'] . "dsgvo-av" . $user->get()['ID'] . $timestamp . $ip), 0, 15), true, false, false, false, '');

					$pdf->Output($this->getLang("PDFF") . $user->get()['ID'] . ".pdf", "I");
				}
				exit;
			}

			$title = $this->getLang("AV");
			$tpl = __DIR__ . "/templates/av.tpl";

			$var['l'] = $this->getLang();
			$var['av'] = false;

			if ($var['logged_in']) {
				$var['av'] = $user->get()['dsgvo_av'] > 0;

				if (!$var['av'] && isset($pars[1]) && $pars[1] == "order") {
					$user->log("AV-Vertrag geschlossen", false, true);

					$user->set([
						"dsgvo_av" => "1",
					]);

					$var['av'] = true;
				}
			}

			break;
		}
	}

	public function clientRevoke($pars) {
		if (isset($_POST['dsgvo_av_revoke'])) {
			$pars["user"]->set(["dsgvo_av" => 0]);
			die("ok");
		}
	}

	public function clientDetailTable($pars) {
		return "<tr><td>AV-Vertrag</td><td><font id='dsgvo_av_status' color='" . ($pars['userinfo']->dsgvo_av ? 'green' : 'red') . "'>" . ($pars['userinfo']->dsgvo_av ? 'unterzeichnet [ <a href="#" id="dsgvo_av_revoke">entfernen</a> ]' : 'nicht unterzeichnet') . "</font><script>$('#dsgvo_av_revoke').click(function(e) { e.preventDefault(); $('#dsgvo_av_status').html('<i class=\"fa fa-spinner fa-spin\"></i>'); $.post('', { 'csrf_token': '" . CSRF::raw() . "', 'dsgvo_av_revoke': 1 }, function (r) { if (r == 'ok') { $('#dsgvo_av_status').css('color', 'red').html('nicht unterzeichnet'); } }); });</script></td></tr>";
	}

	public function adminWarning($pars) {
		global $var;

		if (!$this->getOption("show_warning")) {
			return;
		}

		$ip = ip();
		$ex = explode(",", $this->getOption("exclude_ips"));

		foreach ($ex as &$v) {
			$v = trim($v);
		}

		if (in_array($ip, $ex)) {
			return;
		}

		foreach ($ex as $v) {
			if (!filter_var($v, FILTER_VALIDATE_IP)) {
				if (gethostbyname($v) == $ip) {
					return;
				}
			}
		}

		if(isset($_COOKIE['dsgvo_dismiss']) || $_COOKIE['dsgvo_dismiss'] == "yes") {
			return;
		}

		$var['hide_content'] = true;

		return "
			$('#content_before').html('<br /><br /><br /><br /><br /><center><i class=\"fa fa-exclamation-triangle\" style=\"font-size: 15ex; color: #989898;\"></i><br /><br /><h4>{$this->getLang('W1')}</h4><br /><p style=\"max-width: 550px;\">{$this->getLang('W2')}</p><br /><a href=\"#\" id=\"dsgvo_confirm\" class=\"btn btn-primary\">{$this->getLang('W3')}</a><br /><small>{$this->getLang('W4')}</small></center>');
			$('#dsgvo_confirm').click(function(e) {
				e.preventDefault();
			
				$('#content_hidden').show();
				$('#content_before').remove();
				
				var d = new Date();
				d.setTime(d.getTime() + (1000*60*15));
				document.cookie = \"dsgvo_dismiss=yes;expires=\"+ d.toUTCString() + \";path=/\";
			});
		";
	}
}