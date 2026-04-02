<div class="container">
	<h1>{$lang.RESET.TITLE}</h1><hr>

	{if isset($error)}{$error}{/if}
	{if $step == 2}
	<div class="alert alert-success">{$lang.RESET.SUCCESS}</div>
	{else}
        <div class="row centered-form">
        <div class="col-xs-12 col-sm-8 col-md-4 col-sm-offset-2 col-md-offset-4">
        	<div class="panel panel-default">
        		<div class="panel-heading">
			    		<h3 class="panel-title">{$lang.GENERAL.HELLO}, {$ruser.firstname|htmlentities} {$ruser.lastname|htmlentities} <small>{$lang.RESET.TITLE}</small></h3>
			 			</div>
			 			<div class="panel-body">
			    		<form role="form" method="POST" id="reset-password-form">
			    			<div class="form-group">
			    				<input type="password" name="newpw" id="reset-password-newpw" class="form-control input-sm" placeholder="{$lang.REGISTER.CHOOSE_PW}">
			    				<input type="hidden" id="reset-password-newpw-hashed" value="" />
			    			</div>
			    			
							<div class="form-group">
			    				<input type="password" name="newpw2" id="reset-password-newpw2" class="form-control input-sm" placeholder="{$lang.REGISTER.REPEAT_PW}">
			    				<input type="hidden" id="reset-password-newpw2-hashed" value="" />
			    			</div>
							
							<input type="hidden" id="password_type" name="password_type" value="plain" />
							<input type="submit" name="change" value="{$lang.RESET.SET}" class="btn btn-primary btn-block">
			    		</form>
			    	</div>
	    		</div>
    		</div>
    	</div>
	{/if}
    </div>