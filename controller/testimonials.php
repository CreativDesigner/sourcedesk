<?php
global $var, $user, $session, $pars;

if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

$title = $lang['TESTIMONIALS']['TITLE'];
$tpl = "testimonials";

// Add new rating
if (isset($pars[0]) && $pars[0] == "add") {
	$var['add'] = true;

	if ($var['logged_in']) {
		$var['uname'] = $name = $user->get()['firstname'] . " " . substr($user->get()['lastname'], 0, 1) . ".";

		if (isset($_POST['submit'])) {
			try {
				if (!isset($_POST['rating']) || !is_numeric($_POST['rating']) || $_POST['rating'] < 1 || $_POST['rating'] > 5) {
					throw new Exception($lang['TESTIMONIALS']['ERROR_RATING']);
				}

				if (empty($_POST['subject'])) {
					throw new Exception($lang['TESTIMONIALS']['ERROR_TITLE']);
				}

				if (strlen(trim($_POST['subject'])) > 35) {
					throw new Exception($lang['TESTIMONIALS']['ERROR_LENGTH']);
				}

				if (empty($_POST['text'])) {
					throw new Exception($lang['TESTIMONIALS']['ERROR_TEXT']);
				}

				if (strlen($_POST['text']) < 80) {
					throw new Exception($lang['TESTIMONIALS']['ERROR_LENGTH2']);
				}

				if (!isset($_POST['agreement']) || $_POST['agreement'] != "ok") {
					throw new Exception($lang['TESTIMONIALS']['ERROR_ACCEPT']);
				}

				$tst = Testimonials::create();
				$tst->setAuthor($user->get()['ID']);
				$tst->setRating($_POST['rating']);
				$tst->setSubject($_POST['subject']);
				$tst->setText($_POST['text']);
				$tst->save();

				$session->set("tst_added", "1");
				header('Location: ' . $CFG['PAGEURL'] . 'testimonials');
				exit;
			} catch (Exception $ex) {
				$var['error'] = $ex->getMessage();
			}
		}
	}
} else {
	// Page handling
	$rows = Testimonials::num();

	$perPage = 10;
	$page = isset($pars[0]) && is_numeric($pars[0]) ? $pars[0] : 1;
	if ($page > ceil($rows / $perPage)) {
		$page = ceil($rows / $perPage);
	}

	if ($page < 1) {
		$page = 1;
	}

	$offset = ($page - 1) * $perPage;

	$var['page'] = $page;
	$var['pages'] = ceil($rows / $perPage);

	// Order handling
	$var['order'] = $order = isset($pars[1]) && $pars[1] == "rating" ? "rating" : "time";

	// Get testimonials
	$var['testimonials'] = Testimonials::get("$order DESC, ID DESC", $perPage, $offset);

	// Get average
	$var['average'] = (int) ($av = Testimonials::average());
	$var['half'] = $av - $var['average'] > 0.3;

	// Testimonial added
	if ($session->get('tst_added')) {
		$var['added'] = 1;
		$session->remove('tst_added');
	}
}