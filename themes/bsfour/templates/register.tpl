<div class="container">
	<h1>{$lang.REGISTER.TITLE}</h1><hr>
	{if isset($steptwo)}
	<h1>{$lang.REGISTER.SUCCESS_TITLE}</h1><p>{$lang.REGISTER.SUCCESS}</p>
	{else}
		{if isset($error)}<div class="alert alert-danger">
		{$error}
		</div>{/if}

        <div class="row centered-form">
		<div class="col-sm-2 col-md-4">&nbsp;</div>
        <div class="col-xs-12 col-sm-8 col-md-4">
        	<div class="card">
        		<div class="card-body">
			    		<h5 class="card-title">{$lang.REGISTER.BOX_TITLE|replace:"%p":$cfg.PAGENAME} <small>{$lang.REGISTER.BOX_SUBTITLE}</small></h5>
			 			<form role="form" method="POST" class="captcha-form">
			    			<div class="row">
			    				<div class="col-sm-6">
					    			<div class="form-group">
					    				<input type="text" name="firstname" id="firstname" value="{if isset($smarty.request.firstname)}{$smarty.request.firstname|htmlentities}{/if}" class="form-control input-sm" placeholder="{$lang.REGISTER.YOUR_FIRSTNAME}">
					    			</div>
					    		</div>

					    		<div class="col-sm-6">
					    			<div class="form-group">
					    				<input type="text" name="lastname" id="lastname" value="{if isset($smarty.request.lastname)}{$smarty.request.lastname|htmlentities}{/if}" class="form-control input-sm" placeholder="{$lang.REGISTER.YOUR_LASTNAME}">
					    			</div>
					    		</div>
					    	</div>

			    			<div class="form-group">
			    				<input type="email" name="email" id="email" value="{if isset($smarty.request.email)}{$smarty.request.email|htmlentities}{/if}" class="form-control input-sm" placeholder="{$lang.REGISTER.YOUR_MAIL}">
			    			</div>

							{if isset($captchaText)}
							<div class="form-group">
			    				<input type="text" name="captcha" id="captcha" class="form-control input-sm" placeholder="{$captchaText}">
			    			</div>{/if}
			    			{if isset($captchaCode)}
			    			{$captchaCode}
			    			{/if}
			    			<input type="submit" name="doreg" value="{if isset($captchaModal)}{$lang.GENERAL.NEXTSTEP}{else}{$lang.REGISTER.TITLE}{/if}" class="btn btn-primary btn-block"{if isset($captchaExec)} onclick="{$captchaExec}"{/if}{if isset($captchaModal)} onclick="openCaptchaModal(); return false;"{/if}>

							{if isset($captchaModal)}
							<div class="modal fade" id="captchaModal" tabindex="-1" role="dialog">
							  <div class="modal-dialog">
							    <div class="modal-content">
							      <div class="modal-body">
							        {$captchaModal}
							        <button type="submit" name="doreg" class="btn btn-primary btn-block">{$lang.REGISTER.TITLE}</button>
							      </div>
							    </div>
							  </div>
							</div>

							<script type="text/javascript">
								function openCaptchaModal() {
									$('#captchaModal').modal({
										keyboard: false,
										backdrop: 'static',
										show: false,
									});

									$('#captchaModal').modal('show');
								}
							</script>
							{/if}

							{if $cfg.FACEBOOK_LOGIN || $cfg.TWITTER_LOGIN}
                            <br /><div class="row">
                                {if $cfg.FACEBOOK_LOGIN}
                                <div class="col-sm-{if $cfg.TWITTER_LOGIN}6{else}12{/if}">
                                    <a href="{$cfg.PAGEURL}social_login/facebook" class="btn btn-default btn-block" style="background-color: #4060A5; border: none; color: white;">{$lang.GENERAL.FACEBOOK}</a>
                                </div>
                                {/if}
                                {if $cfg.TWITTER_LOGIN}
                                <div class="col-sm-{if $cfg.FACEBOOK_LOGIN}6{else}12{/if}">
                                    <a href="{$cfg.PAGEURL}social_login/twitter" class="btn btn-default btn-block" style="background-color: #00ABE3; border: none; color: white;">{$lang.GENERAL.TWITTER}</a>
                                </div>
                                {/if}
                            </div>
                            {/if}
			    		</form>
			    	</div>
	    		</div>
    		</div>
    	</div>
	{/if}
    </div>
