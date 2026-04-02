<div class="container">
    <div class="row" style="padding-top:20px;">
    	<div class="col-md-6 col-md-offset-3">
			{if empty($alert) && isset($smarty.get.redirect_to)}<div class="alert alert-info">{$lang.LOGIN.PLTC}</div>{/if}
    		<div class="panel panel-default">
			  	<div class="panel-heading">
			    	<h3 class="panel-title">{$lang.GENERAL.LOGIN} <small>{$lang.LOGIN.SUBTITLE}</small></h3>
			 	</div>
			  	<div class="panel-body">
			  		{if isset($alert)}{$alert}{/if}
			    	<form accept-charset="UTF-8" id="login-form" role="form" method="POST">
                    <fieldset>
			    	  	<div class="form-group">
			    		    <input class="form-control" placeholder="{$lang.GENERAL.MAIL}" required="" name="email" value="{if isset($smarty.post.email)}{$smarty.post.email|htmlentities}{/if}" type="email">
			    		</div>
			    		<div class="form-group">
			    			<input class="form-control" placeholder="{$lang.GENERAL.PW}" name="password" type="password" value="" id="login-password">
			    			<input type="hidden" id="login-hashed" value="" />
			    		</div>
			    		<div class="checkbox">
			    	    	<label>
			    	    		<input name="cookie" type="checkbox" value="true" {if isset($smarty.post.cookie)}checked{/if}> {$lang.GENERAL.SET_COOKIE}
			    	    	</label>
			    	    </div>
			    	    {if isset($smarty.request.redirect_to)}<input type="hidden" name="redirect_to" value="{$smarty.request.redirect_to|urlencode}" />
			    	    {/if}
			    		<input class="btn btn-lg btn-success btn-block" type="submit" id="login-do" name="login" value="{$lang.GENERAL.LOGIN_DO}">
			    		<input class="btn btn-sm btn-warning btn-block" type="submit" name="pwreset" value="{$lang.GENERAL.GET_PW}">

			    		{if $cfg.FACEBOOK_LOGIN || $cfg.TWITTER_LOGIN}
                            <br /><div class="row">
                                {if $cfg.FACEBOOK_LOGIN}
                                <div class="col-xs-{if $cfg.TWITTER_LOGIN}6{else}12{/if}">
                                    <a href="{$cfg.PAGEURL}social_login/facebook" class="btn btn-default btn-block" style="background-color: #4060A5; border: none; color: white;">{$lang.GENERAL.FACEBOOK}</a>
                                </div>
                                {/if}
                                {if $cfg.TWITTER_LOGIN}
                                <div class="col-xs-{if $cfg.FACEBOOK_LOGIN}6{else}12{/if}">
                                    <a href="{$cfg.PAGEURL}social_login/twitter" class="btn btn-default btn-block" style="background-color: #00ABE3; border: none; color: white;">{$lang.GENERAL.TWITTER}</a>
                                </div>
                                {/if}
                            </div>
                            {/if}
			    	</fieldset>
			      	</form>
			    </div>
			</div>
		</div>
	</div>
</div>