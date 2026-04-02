<?php
if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

$l = $lang['STAT_TRANSACTIONS'];

title($l['TITLE']);
menu("statistics");

if (!$ari->check(40)) {require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "stat_transactions");} else {

    $year = isset($_GET['y']) ? $_GET['y'] : date("Y");

    function getDeposits($month)
    {
        global $db, $year, $CFG;

        $first = strtotime("01.$month.$year");
        $last = strtotime(date("t", $first) . ".$month.$year 23:59:59");

        $sql = $db->query("SELECT SUM(amount) AS s FROM client_transactions WHERE `amount` > 0 AND `time` >= '$first' AND `time` <= '$last'");
        $sum = $sql->fetch_object()->s;
        if ($sum == null) {
            $sum = 0;
        }

        return $sum;
    }

    function getPayments($month)
    {
        global $db, $year, $CFG;

        if ($year == 0) {
            $year = date("Y");
        }

        $first = strtotime("01.$month.$year");
        $last = strtotime(date("t", $first) . ".$month.$year 23:59:59");

        $sql = $db->query("SELECT SUM(amount) AS s FROM client_transactions WHERE `amount` < 0 AND `time` >= '$first' AND `time` <= '$last'");
        $sum = $sql->fetch_object()->s;
        if ($sum == null) {
            $sum = 0;
        }

        return $sum;
    }
    ?>

            <div class="row">
                <div class="col-lg-12">
                    <h1 class="page-header"><?=$l['TITLE'];?> <small><?=$year;?></small></h1>
                </div>
                <!-- /.col-lg-12 -->
            </div>

			<center>
				<form method="GET" role="form">
					<div class="form-inline">
						<input type="text" class="form-control" value="<?=$year;?>" style="max-width:80px" name="y" placeholder="<?=$l['YEAR'];?>">
						<input type="hidden" name="p" value="<?=$_GET['p'];?>">
						<input type="submit" value="<?=$l['YEARC'];?>" class="btn btn-primary">
					</div>
				</form>
			</center>

			<script type="text/javascript" src="https://www.google.com/jsapi"></script>
			<script type="text/javascript">
			  google.load("visualization", "1", {packages:["corechart"]});
		google.setOnLoadCallback(drawChart);
		function drawChart() {

		  var data = google.visualization.arrayToDataTable([
			['<?=$l['MONTH'];?>', '<?=$l['DEPOSITS'];?>', '<?=$l['ORDERS'];?>'],
			<?php for ($i = 1; $i <= 12; $i++) {?>
			['<?=$l['MONTH' . $i];?>', <?=getDeposits($i = str_pad($i, 2, "0", STR_PAD_LEFT));?>, <?=getPayments($i);?>],
			<?php }?>
		  ]);

		  var options = {
			title: '',
			hAxis: {title: '<?=$l['MONTH'];?>'},
			legend: { position: "none" }
		  };

		  var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));

		  chart.draw(data, options);

		}
			</script>

			<div id="chart_div" style="width: 100%; height: 600px;"></div>
<?php }?>