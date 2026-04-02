<div class="row">
	<div class="col-lg-12">
        <form method="POST">
            <h1 class="page-header">{$lang.TOP.SETTINGS}</h1>

            {if !empty($suc)}
            <div class="alert alert-success">{$lang.GENERAL.SAVED}</div>
            {/if}

            <div class="form-group">
                <label>{$lang.ADD_ADMIN.PER_PAGE}</label>
                <input type="number" min="10" name="per_page" class="form-control" value="{$per_page}" style="max-width: 150px;">
            </div>

            <div class="checkbox">
                <label>
                    <input type="checkbox" name="hide_sidebar" value="1"{if $hide_sidebar} checked=""{/if}> {$lang.ADD_ADMIN.SIDEBAR}
                </label>
            </div>

            <div class="checkbox">
                <label>
                    <input type="checkbox" name="open_menu" value="1"{if $open_menu} checked=""{/if}> {$lang.ADD_ADMIN.MENU}
                </label>
            </div>

            <div class="checkbox">
                <label>
                    <input type="checkbox" name="next_ticket" value="1"{if $next_ticket} checked=""{/if}> {$lang.ADD_ADMIN.NEXT_TICKET}
                </label>
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$lang.GENERAL.SAVE}">
        </form>
	</div>
</div>