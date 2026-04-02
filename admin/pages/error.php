<?php
if (!defined("SOURCEDESK")) {
	die("Direct access to this file is not permitted.");
}

title($lang['ERROR']['TITLE']);
menu("");
?>

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header"><?=$lang['ERROR']['TITLE']; ?></h1>
	</div>
	<!-- /.col-lg-12 -->
</div>
<?php if (isset($var) && is_array($var) && array_key_exists("error", $var)) { echo $var['error']; } else { ?>
<?=$lang['ERROR']['INTRO']; ?><br />
<ul>
	<li><?=$lang['ERROR']['REASONS']['1']; ?></li>
	<li><?=$lang['ERROR']['REASONS']['2']; ?></li>
	<li><?=$lang['ERROR']['REASONS']['3']; ?></li>
	<li><?=$lang['ERROR']['REASONS']['4']; ?></li>
</ul><br />
<?=$lang['ERROR']['FOOTER']; ?>
<?php } ?>