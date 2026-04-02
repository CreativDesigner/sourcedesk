</a><!DOCTYPE html>
<html>
    <head>
<title>Oops! An exception encountered.<?php if ($CFG ?? false && $CFG['PAGENAME'] ?? "") {?> :: <?=$CFG['PAGENAME'];?><?php }?></title>
        <link rel="shortcut icon" href="<?=$raw_cfg['PAGEURL'];?>themes/favicon.ico" type="image/x-icon" />

        <style>
        body {
            background: #f3f3f3;
        }

        .box {
            margin: auto;
            margin-top: 50px;
            max-width: 500px;
            width: 100%;
            -webkit-box-shadow: 3px 3px 8px 4px #ccc;
            -moz-box-shadow: 3px 3px 8px 4px #ccc;
            box-shadow: 3px 3px 8px 4px #ccc;
            border: black 1px solid;
            border-radius: 10px;
            line-height: 1.4;
            text-align: center;
            font-family: 'Lucida Grande', sans-serif;
        }

        .box h4 {
            margin: 0;
            padding: 10px 0;
            border-bottom: black 1px solid;
        }

        .box p {
            margin: 10px 0;
            padding: 5px;
            padding-bottom: 10px;
            border-bottom: black 1px solid;;
        }

        .box footer {
            font-size: 10px;
            padding-bottom: 10px;
        }
        </style>
    </head>
    <body>
        <div class="box">
            <h4>Oops! An exception encountered.</h4>
            <p><?=htmlentities($ex->getMessage()) ?: "No details available";?></p>
            <footer>&copy; Copyright haseDESK <?=date("Y");?></footer>
        </div>
    </body>
</html>
<?php
exit;
?>