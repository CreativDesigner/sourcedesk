<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.FORUM.TITLE}</h1>

        {if $step == "overview"}
        <form method="POST" class="form-inline">
            <input type="text" class="form-control" name="new_name" placeholder="{$l.NEW_NAME}">
            <input type="submit" class="btn btn-primary" value="{$l.ADD_FORUM}">
        </form><br />
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th>{$l.FORUM}</th>
                    <th width="15%"><center>{$l.VISIBLE}</center></th>
                    <th width="10%"><center>{$l.THREADS}</center></th>
                    <th width="10%"><center>{$l.ENTRIES}</center></th>
                    <th width="10%"><center>{$l.MODS}</center></th>
                    <th width="10%"><center>{$l.ORDER}</center></th>
                    <th width="30px"></th>
                    <th width="28px"></th>
                </tr>

                {foreach from=$forums item=f}
                <tr>
                    <td>{$f.name|htmlentities}</td>
                    <td><center>{if $f.pids}{$l.PRODUCTFORUM}{else}{if $f.public}{$l.PUBLIC}{else}{$l.PRIVATE}{/if}{/if}</center></td>
                    <td><center>{$f.threads}</center></td>
                    <td><center>{$f.entries}</center></td>
                    <td><center>{$f.mods}</center></td>
                    <td><center>{$f.order}</center></td>
                    <td><a href="?p=forum&edit={$f.ID}"><i class="fa fa-edit"></i></a></td>
                    <td>{if $f.threads == 0}<a href="?p=forum&delete={$f.ID}"><i class="fa fa-trash-o"></i></a>{/if}</td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="7"><center>{$l.NOTHING}</center></td>
                </tr>
                {/foreach}
            </table>
        </div>
        {elseif $step == "edit"}
        <form method="POST">
            <div class="form-group">
                <label>{$l.NAME}</label>
                <input type="text" name="name" class="form-control" value="{$f.name|htmlentities}">
            </div>

            <div class="form-group">
                <label>{$l.DESCRIPTION}</label>
                <input type="text" name="description" class="form-control" value="{$f.description|htmlentities}">
            </div>

            <div class="form-group">
                <label>{$l.MODS}</label>
                <select name="mods[]" multiple="" class="form-control">
                    {foreach from=$users item=u key=id}
                    <option value="{$id}"{if in_array($id, $mods)} selected=""{/if}>{$u|htmlentities}</option>
                    {/foreach}
                </select>
            </div>

            <div class="form-group">
                <label>{$l.VISIBLE}</label><br />
                <label class="radio-inline">
                    <input type="radio" name="public" value="1"{if ($f.public && !count($f.pids))} checked=""{/if}> {$l.PUBLIC}
                </label>
                <label class="radio-inline">
                    <input type="radio" name="public" value="0"{if (!$f.public && !count($f.pids))} checked=""{/if}> {$l.PRIVATE}
                </label>
                <label class="radio-inline">
                    <input type="radio" name="public" value="2"{if count($f.pids)} checked=""{/if}> {$l.PRODUCTFORUM}
                </label>
            </div>

            <div class="form-group" id="pids"{if !count($f.pids)} style="display: none;"{/if}>
                <label>{$l.REQPROD}</label>
                <select name="pids[]" multiple="" class="form-control">
                    {foreach from=$products item=p key=id}
                    <option value="{$id}"{if in_array($id, $f.pids)} selected=""{/if}>{$p|htmlentities}</option>
                    {/foreach}
                </select>
            </div>

            <script>
            function pidSelect() {
                $("#pids").hide();

                $("[name=public]").each(function() {
                    if ($(this).is(":checked") && $(this).val() == "2") {
                        $("#pids").show();
                    }
                });
            }

            $(document).ready(pidSelect);
            $("[name=public]").click(pidSelect);
            </script>

            <div class="form-group">
                <label>{$l.ORDER}</label>
                <input type="number" name="order" class="form-control" placeholder="0" value="{$f.order|intval}" style="max-width: 100px;">
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$l.SAVE}">
        </form>
        {/if}
	</div>
</div>