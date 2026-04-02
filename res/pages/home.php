<style>
body {
  padding-top: 40px;
  padding-bottom: 40px;
}
</style>

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <h3><?=$pageName; ?> <span class="pull-right"><a href="?p=logout"><i class="fa fa-sign-out"></i></a></h3>

            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3" style="text-align: center;">
                            <i class="fa fa-files-o" style="font-size: 80pt;"></i>
                            <div class="hidden-lg hidden-md"><br /><br /></div>
                        </div>
                        <div class="col-md-9">
                            <?php if (!count($contracts)) { ?>
                            Es existieren keine aktiven Verträge.<br /><i>There are no active contracts.</i>
                            <?php } else { ?>
                            <div id="contracts"><center><i class="fa fa-spinner fa-spin" style="font-size: 50pt;"></i><br /><br />Bitte warten...<br /><i>Please wait...</i></center></div>
                            <script>
                            var req = new XMLHttpRequest();
                            req.onreadystatechange = function() {
                                if (this.readyState == 4 && this.status == 200) {
                                    document.getElementById("contracts").innerHTML = this.responseText;
                                }
                            };
                            req.open("GET", "?p=list", true);
                            req.send();
                            </script>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>