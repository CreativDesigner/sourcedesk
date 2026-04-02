<!DOCTYPE html>
<html>
    <head>
        <title>License Key Failure :: sourceDESK</title>
        <link rel="shortcut icon" href="../themes/favicon.ico" type="image/x-icon" />

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

        .box div {
            margin: 10px 0;
            padding: 5px;
            padding-top: 0;
            padding-bottom: 10px;
            border-bottom: black 1px solid;;
        }

        ul {
            margin-bottom: 0;
            text-align: left;
        }

        .box footer {
            font-size: 10px;
            padding-bottom: 10px;
        }
        </style>
    </head>
    <body>
        <div class="box">
            <h4>License Key Failure</h4>
            <div>
                Unfortunately, there was a problem verifying your sourceDESK license key.

                <?php if (!empty($_GET['reason'])) {?>
                This has the following reason:<br /><br /><?=htmlentities($_GET['reason']);?><br />
                <?php } else {?>
                This can have one of these reasons:

                <ul>
                    <li>Your server is unable to connect to sourceway.de</li>
                    <li>Your server declines the SSL certificate of sourceway.de</li>
                    <li>Your license limits are exceeded</li>
                    <li>Your license has expired or is locked</li>
                </ul>
                <?php }?>

                <br />You have several possibilities now:

                <ul>
                    <li><a href="./">Try it again</a></li>
                    <li><a href="#" onclick="window.location = './?new_license_key=' + prompt('New license key?'); return false;">Enter a new license key</a></li>
                    <li><a href="https://sourceway.de/de/tickets" target="_blank">Contact support</a></li>
                </ul>
            </div>
            <footer>&copy; Copyright sourceDESK <?=date("Y");?></footer>
        </div>
    </body>
</html>