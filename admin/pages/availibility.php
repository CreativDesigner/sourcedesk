<?php
$l = $lang['AVAILIBILITY'];
title($l['TITLE']);
menu("statistics");

if (!defined("SOURCEDESK")) {
    die("Direct access to this file is not permitted.");
}

if (!$ari->check(40)) {
    require __DIR__ . "/error.php";
    alog("general", "insufficient_page_rights", "availibility");
} else {
    function pixel($date, $hour)
    {
        global $db, $CFG;
        while (strlen($hour) < 2) {
            $hour = "0" . $hour;
        }

        $dend = $date . " " . $hour . ":59:59";
        if (date("Y-m-d H:i:s") < $dend) {
            $dend = date("Y-m-d H:i:s");
        }

        $arr = array();
        $x = 0;
        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":05:00' AND (`end` >= '" . $date . " " . $hour . ":00:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":05:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":10:00' AND (`end` >= '" . $date . " " . $hour . ":05:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":10:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":15:00' AND (`end` >= '" . $date . " " . $hour . ":10:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":15:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":20:00' AND (`end` >= '" . $date . " " . $hour . ":15:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":20:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":25:00' AND (`end` >= '" . $date . " " . $hour . ":20:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":25:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":30:00' AND (`end` >= '" . $date . " " . $hour . ":25:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":30:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":35:00' AND (`end` >= '" . $date . " " . $hour . ":30:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":35:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":40:00' AND (`end` >= '" . $date . " " . $hour . ":35:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":40:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":45:00' AND (`end` >= '" . $date . " " . $hour . ":40:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":45:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":50:00' AND (`end` >= '" . $date . " " . $hour . ":45:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":50:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":55:00' AND (`end` >= '" . $date . " " . $hour . ":50:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":55:00" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        if ($db->query("SELECT 1 FROM admin_times WHERE `start` < '" . $date . " " . $hour . ":59:59' AND (`end` >= '" . $date . " " . $hour . ":55:00' OR (`end` = '0000-00-00 00:00:00' AND " . intval($date . " " . $hour . ":59:59" <= date("Y-m-d H:i:s")) . "))")->num_rows > 0) {
            array_push($arr, array("0"+$x, "3"));
            $x = 0;
        } else {
            $x += 3;
        }

        return $arr;
    }
    ?>
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header"><?=$l['TITLE'];?></h1>

	    <div class="col-md-4 col-md-offset-4 col-sm-12" style="margin-bottom: 30px;"><div class="date-picker" data-date="<?=date("Y/m/d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("d.m.Y")));?>" data-keyboard="true">
            <div class="date-container pull-left">
                <h4 class="weekday"></h4>
                <h2 class="date"></h2>
                <h4 class="year pull-right"></h4>
            </div>
            <span data-toggle="datepicker" data-type="subtract" class="fa fa-angle-left"></span>
            <span data-toggle="datepicker" data-type="add" class="fa fa-angle-right"></span>
        </div></div>

        <div class="table-responsive">
        	<table class="table table-bordered">
        		<tr>
        			<th style="width: 50px; height: 36px;">00:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 0);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">01:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 1);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">02:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 2);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">03:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 3);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">04:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 4);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">05:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 5);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">06:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 6);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">07:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 7);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">08:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 8);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">09:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 9);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">10:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 10);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">11:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 11);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">12:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 12);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">13:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 13);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">14:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 14);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">15:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 15);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">16:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 16);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">17:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 17);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">18:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 18);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">19:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 19);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">20:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 20);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">21:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 21);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">22:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 22);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>

        		<tr>
        			<th style="width: 50px; height: 36px;">23:00</th>
        			<td style="padding: 0; vertical-align: top;">
        				<?php $p = pixel(date("Y-m-d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"))), 23);foreach ($p as $i) {?>
        				<div style="background-color: #00BFFF; margin-top: <?=$i[0];?>px; height: <?=$i[1];?>px; width: 100%;">&nbsp;</div>
        				<?php }?>
        			</td>
        		</tr>
        	</table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.date-picker').each(function () {
        var $datepicker = $(this),
            cur_date = ($datepicker.data('date') ? moment($datepicker.data('date'), "YYYY/MM/DD") : moment()),
            format = {
                "weekday" : ($datepicker.find('.weekday').data('format') ? $datepicker.find('.weekday').data('format') : "dddd"),
                "date" : ($datepicker.find('.date').data('format') ? $datepicker.find('.date').data('format') : "MMMM Do"),
                "year" : ($datepicker.find('.year').data('year') ? $datepicker.find('.weekday').data('format') : "YYYY")
            };

        function updateDisplay(cur_date) {
            $datepicker.find('.date-container > .weekday').text(cur_date.format(format.weekday));
            $datepicker.find('.date-container > .date').text(cur_date.format(format.date));
            $datepicker.find('.date-container > .year').text(cur_date.format(format.year));
            $datepicker.data('date', cur_date.format('YYYY/MM/DD'));
            $datepicker.find('.input-datepicker').removeClass('show-input');

            if(cur_date.format('YYYY/MM/DD') != '<?=date("Y/m/d", strtotime(isset($_GET['d']) ? $_GET['d'] : date("d.m.Y")));?>') window.location = "?p=availibility&d=" + cur_date.format('YYYY/MM/DD');
        }

        updateDisplay(cur_date);

        $datepicker.on('click', '[data-toggle="calendar"]', function(event) {
            event.preventDefault();
            $datepicker.find('.input-datepicker').toggleClass('show-input');
        });

        $datepicker.on('click', '[data-toggle="datepicker"]', function(event) {
            event.preventDefault();

            var cur_date = moment($(this).closest('.date-picker').data('date'), "YYYY/MM/DD"),
                date_type = ($datepicker.data('type') ? $datepicker.data('type') : "days"),
                type = ($(this).data('type') ? $(this).data('type') : "add"),
                amt = ($(this).data('amt') ? $(this).data('amt') : 1);

            if (type == "add") {
                cur_date = cur_date.add(date_type, amt);
            }else if (type == "subtract") {
                cur_date = cur_date.subtract(date_type, amt);
            }

            updateDisplay(cur_date);
        });

        if ($datepicker.data('keyboard') == true) {
            $(window).on('keydown', function(event) {
                if (event.which == 37) {
                    $datepicker.find('span:eq(0)').trigger('click');
                }else if (event.which == 39) {
                    $datepicker.find('span:eq(1)').trigger('click');
                }
            });
        }
    });
});
</script>

<style>
@import url(http://fonts.googleapis.com/css?family=Roboto:400,300);
@import url(http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css);

.fa.pull-right {
    margin-left: 0.1em;
}

.date-picker,
.date-container {
    position: relative;
    display: inline-block;
    width: 100%;
    color: rgb(75, 77, 78);
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}
.date-container {
    padding: 0px 40px;
}
.date-picker h2, .date-picker h4 {
    margin: 0px;
    padding: 0px;
    font-family: 'Roboto', sans-serif;
    font-weight: 200;
}
.date-container .date {
    text-align: center;
}
.date-picker span.fa {
    position: absolute;
    font-size: 4em;
    font-weight: 100;
    padding: 8px 0px 7px;
    cursor: pointer;
    top: 0px;
}
.date-picker span.fa[data-type="subtract"] {
    left: 0px;
}
.date-picker span.fa[data-type="add"] {
    right: 0px;
}
.date-picker span[data-toggle="calendar"] {
    display: block;
    position: absolute;
    top: -7px;
    right: 45px;
    font-size: 1em !important;
    cursor: pointer;
}

.date-picker .input-datepicker {
    display: none;
    position: absolute;
    top: 50%;
    margin-top: -17px;
    width:100%;
}
.date-picker .input-datepicker.show-input {
    display: table;
}

@media (min-width: 768px) and (max-width: 1010px) {
    .date-picker h2{
        font-size: 1.5em;
        font-weight: 400;
    }
    .date-picker h4 {
        font-size: 1.1em;
    }
    .date-picker span.fa {
        font-size: 3em;
    }
}
</style>
<?php }?>