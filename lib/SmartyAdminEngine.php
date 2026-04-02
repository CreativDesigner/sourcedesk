<?php

// Class for Smarty in admin area

class SmartyAdminEngine extends SmartyEngine {

	// Method to display a page
	// Requires the title and template (without extension)
	public function show($controller) {
		global $addons, $tpl, $var;
		
		// Check if controller exists
		if (file_exists("controller/$controller.php")) {
			// Require the specified controller and display the page with title and template name from controller
			require_once "controller/$controller.php";
			$this->displayPage($tpl);
		} else if ($addons->routePage($controller, "admin", false)) {
			$this->displayPage($tpl);
		} else {
			// If specified controller does not exist, display the error page
			$this->displayPage("error");
		}
	}

	// Method to show a page within a controller

	protected function displayPage($pTpl, $unusedVar = null) {
		// Global template variables for security reasons
		global $var, $db, $CFG, $menuToOpen, $currentPageTitle, $adminInfo;

		$this->template = $pTpl;
		if (!is_string($pTpl)) {
			$pTpl = "error";
		}

		if (file_exists("templates/$pTpl.tpl")) {
			$this->tpl->assign('tpl', $pTpl . '.tpl');
		} else if (file_exists($pTpl)) {
			$this->tpl->assign('tpl', $pTpl);
		} else {
			$this->tpl->assign('tpl', 'error.tpl');
		}

		// Get intermediate page variables
		$var['waiting_testimonials'] = Testimonials::num(0);
		$var['waiting_emails'] = $db->query("SELECT COUNT(*) AS num FROM client_mails WHERE wait = 1 AND sent = 0")->fetch_object()->num;

		// Assign all default variables to template engine
		$var['menuToOpen'] = $adminInfo->open_menu ? $menuToOpen : "";
		$var['currentPageTitle'] = $currentPageTitle;

		if (isset($var) && is_array($var)) {
			foreach ($var as $k => $v) {
				$this->tpl->assign($k, $v);
			}
		}

		$html = $this->tpl->fetch('layout.tpl');
		CSRF::auto($html);
		echo $html;
	}

}
