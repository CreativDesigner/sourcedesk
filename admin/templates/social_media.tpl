{if isset($suc)}<br /><br /><div class="alert alert-success" style="margin-bottom: -20px;">{$suc}</div>{/if}
{if isset($err)}<br /><br /><div class="alert alert-danger" style="margin-bottom: -20px;">{$err}</div>{/if}

<div class="row">
	<div class="col-lg-6">
		<h1 class="page-header">{$l.FACEBOOK}</h1>

        <div class="panel panel-primary">
            <div class="panel-heading">
                {$l.CREATE_POST}
            </div>
            <div class="panel-body">
                <form method="POST">
                    <input type="hidden" name="action" value="fb_post">

                    <textarea name="fb_post" class="form-control" style="height: 120px; resize: vertical; margin-bottom: 10px;"></textarea>

                    <input type="submit" class="btn btn-primary btn-block" value="{$l.CREATE_POST}">
                </form>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                {$l.CONFIG}
            </div>
            <div class="panel-body">
                <form method="POST">
                    <input style="opacity: 0;position: absolute; display: none;">
                    <input type="password" autocomplete="new-password" style="display: none;">

                    <input type="hidden" name="action" value="save_sm">

                    <div class="form-group">
                        <label>{$l.FB_PAGE_ID}</label>
                        <input type="text" name="fb_page_id" value="{$cfg.SM_FB_PAGE_ID|htmlentities}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>{$l.FB_KEY}</label>
                        <input type="password" name="fb_key" value="{$cfg.SM_FB_KEY|htmlentities}" class="form-control">
                    </div>

                    <input type="submit" class="btn btn-primary btn-block" value="{$l.SAVE}">
                </form>
            </div>
        </div>
	</div>

    <div class="col-lg-6">
		<h1 class="page-header">{$l.TWITTER}</h1>

        <div class="panel panel-primary">
            <div class="panel-heading">
                {$l.CREATE_TWEET}
            </div>
            <div class="panel-body">
                <form method="POST">
                    <input type="hidden" name="action" value="twitter_post">

                    <textarea name="twitter_post" class="form-control" style="height: 120px; resize: vertical; margin-bottom: 10px;"></textarea>

                    <input type="submit" class="btn btn-primary btn-block" value="{$l.CREATE_TWEET}">
                </form>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                {$l.CONFIG}
            </div>
            <div class="panel-body">
                <form method="POST">
                    <input style="opacity: 0;position: absolute; display: none;">
                    <input type="password" autocomplete="new-password" style="display: none;">

                    <input type="hidden" name="action" value="save_sm">

                    <div class="form-group">
                        <label>{$l.TWITTER_CK}</label>
                        <input type="text" name="twitter_ck" value="{$cfg.SM_TWITTER_CK|htmlentities}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>{$l.TWITTER_CS}</label>
                        <input type="password" name="twitter_cs" value="{$cfg.SM_TWITTER_CS|htmlentities}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>{$l.TWITTER_AT}</label>
                        <input type="text" name="twitter_at" value="{$cfg.SM_TWITTER_AT|htmlentities}" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>{$l.TWITTER_ATS}</label>
                        <input type="password" name="twitter_ats" value="{$cfg.SM_TWITTER_ATS|htmlentities}" class="form-control">
                    </div>

                    <input type="submit" class="btn btn-primary btn-block" value="{$l.SAVE}">
                </form>
            </div>
        </div>
	</div>
</div>