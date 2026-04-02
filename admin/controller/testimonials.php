<?php
// Global some variables for security reasons
global $var, $lang, $ari, $CFG, $db;

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

title($lang['TESTIMONIALS']['TITLE']);
menu("customers");

if ($ari->check(58)) {
    $tpl = "testimonials";

    if (isset($_POST['answer']) && isset($_POST['id'])) {
        $db->query("UPDATE testimonials SET answer = '" . $db->real_escape_string($_POST['answer']) . "' WHERE ID = " . intval($_POST['id']));
        header('Location: ?p=testimonials');
        exit;
    }

    if (isset($_GET['publish']) && is_numeric($_GET['publish']) && Testimonials::fetch($_GET['publish'])->activate(true)) {
        alog("testimonials", "published", $_GET['publish']);
        $var['msg'] = $lang['TESTIMONIALS']['MSG_PUBLISHED'];
    } else if (isset($_GET['delete']) && is_numeric($_GET['delete']) && Testimonials::fetch($_GET['delete'])->delete()) {
        alog("testimonials", "deleted", $_GET['delete']);
        $var['msg'] = $lang['TESTIMONIALS']['MSG_DELETED'];
    }

    $table = new Table("SELECT * FROM testimonials", [
        "active" => [
            "name" => $lang['TESTIMONIALS']['STATUS'],
            "type" => "select",
            "options" => [
                "0" => $lang['TESTIMONIALS']['WAITING'],
                "1" => $lang['TESTIMONIALS']['PUBLISHED'],
            ],
        ],
        "rating" => [
            "name" => $lang['TESTIMONIALS']['RATING'],
            "type" => "select",
            "options" => [
                "5" => $lang['TESTIMONIALS']['R5'],
                "4" => $lang['TESTIMONIALS']['R4'],
                "3" => $lang['TESTIMONIALS']['R3'],
                "2" => $lang['TESTIMONIALS']['R2'],
                "1" => $lang['TESTIMONIALS']['R1'],
            ],
        ],
        "subject" => [
            "name" => $lang['TESTIMONIALS']['TITLE_COL'],
            "type" => "like",
        ],
    ], ["time", "DESC"], "testimonials");
    $var['th'] = $table->getHeader();
    $var['tf'] = $table->getFooter();

    $var['table_order'] = [
        $table->orderHeader("time", $lang["TESTIMONIALS"]["DATE"]),
        $table->orderHeader("author", $lang["TESTIMONIALS"]["CUSTOMER"]),
        $table->orderHeader("rating", $lang["TESTIMONIALS"]["RATING"]),
        $table->orderHeader("title", $lang["TESTIMONIALS"]["TITLE_COL"]),
    ];

    $sql = $table->qry("time DESC, ID DESC");

    $var['active'] = isset($_GET['active']) && $_GET['active'] == "1" ? 1 : 0;
    $var['testimonials'] = [];

    $t = new Testimonials;
    while ($row = $sql->fetch_object()) {
        $var['testimonials'][$row->ID] = new Testimonial($row->ID);
    }

    $var['additionalJS'] = 'function loadTestimonial(id){
		$.post("?p=ajax", {
			action: "load_testimonial",
			id: id,
			csrf_token: "' . CSRF::raw() . '",
		}, function (response) {
			var obj = $.parseJSON(response);
			$("#testimonialLabel").html(obj.title);
			$("#testimonialContent").html(obj.text);
		});
	}

	function loadAnswer(id){
		$.post("?p=ajax", {
			action: "load_testimonial_answer",
			id: id,
			csrf_token: "' . CSRF::raw() . '",
		}, function (response) {
			$("#testimonialAnswer").html(response);
		});
	}';
} else {
    alog("general", "insufficient_page_rights", "testimonials");
    $tpl = "error";
}
