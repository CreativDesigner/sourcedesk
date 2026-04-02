<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$l.TITLE}</h1>

        <form method="POST" class="form-inline">
            <div class="form-group" style="position: relative;">
                <input type="text" class="form-control datetimepicker" name="from" value="{dfo d=$tf.0 s=1 t=''}">
            </div>
            -
            <div class="form-group" style="position: relative;">
                <input type="text" class="form-control datetimepicker" name="to" value="{dfo d=$tf.1 s=1 t=''}">
            </div>
            <input type="submit" class="btn btn-primary" value="{$l.CHANGE}">
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <h1 class="page-header">{$l.VISITORS}</h1>

        <div class="row">
            <div class="col-sm-4">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <h3 style="margin: 0;" class="pull-right">{$visitors}</h3><br /><br />
                        <span class="pull-right">{$l.VISITORS}</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="panel panel-danger">
                    <div class="panel-body">
                        <h3 style="margin: 0;" class="pull-right">{$pages}</h3><br /><br />
                        <span class="pull-right">{$l.PAGES}</span>
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="panel panel-warning">
                    <div class="panel-body">
                        <h3 style="margin: 0;" class="pull-right">{($pages / $visitors)|round}</h3><br /><br />
                        <span class="pull-right">{$l.PAGESAV}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <h1 class="page-header">{$l.GEOLOCATION}</h1>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="20%"><center>{$l.COUNT}</center></th>
                    <th>{$l.COUNTRY}</th>
                </tr>

                {if is_object($country)}
                {while ($row = $country->fetch_object())}
                <tr>
                    <td><center>{$row->c}</center></td>
                    <td>{$row->p|htmlentities}</td>
                </tr>
                {/while}
                {/if}
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <h1 class="page-header">{$l.BROWSER}</h1>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="20%"><center>{$l.COUNT}</center></th>
                    <th>{$l.BROWSER}</th>
                </tr>

                {if is_object($browser)}
                {while ($row = $browser->fetch_object())}
                <tr>
                    <td><center>{$row->c}</center></td>
                    <td>{$row->p|htmlentities}</td>
                </tr>
                {/while}
                {/if}
            </table>
        </div>
    </div>

    <div class="col-md-6">
        <h1 class="page-header">{$l.OS}</h1>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="20%"><center>{$l.COUNT}</center></th>
                    <th>{$l.SYSTEM}</th>
                </tr>

                {if is_object($os)}
                {while ($row = $os->fetch_object())}
                <tr>
                    <td><center>{$row->c}</center></td>
                    <td>{$row->p|htmlentities}</td>
                </tr>
                {/while}
                {/if}
            </table>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <h1 class="page-header">{$l.START}</h1>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="20%"><center>{$l.COUNT}</center></th>
                    <th>{$l.PAGE}</th>
                </tr>

                {if is_object($start_page)}
                {while ($row = $start_page->fetch_object())}
                <tr>
                    <td><center>{$row->c}</center></td>
                    <td>{$row->p|htmlentities}</td>
                </tr>
                {/while}
                {/if}
            </table>
        </div>
    </div>

    <div class="col-md-6">
        <h1 class="page-header">{$l.END}</h1>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="20%"><center>{$l.COUNT}</center></th>
                    <th>{$l.PAGE}</th>
                </tr>

                {if is_object($end_page)}
                {while ($row = $end_page->fetch_object())}
                <tr>
                    <td><center>{$row->c}</center></td>
                    <td>{$row->p|htmlentities}</td>
                </tr>
                {/while}
                {/if}
            </table>
        </div>
    </div>
</div>