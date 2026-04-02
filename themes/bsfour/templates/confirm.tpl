<div class="container">
	
	<h1>{$lang.REGISTER.TITLE}</h1><hr>
	
	{if isset($error)}<div class="alert alert-danger">
		{$error}
	</div>{/if}
	
	{if isset($success)}<div class="alert alert-success">
		{$success}
	</div>{/if}
	{if !isset($donotshow)}
        <div class="row centered-form">
		<div class="col-sm-2 col-md-3">&nbsp;</div>
        <div class="col-xs-12 col-sm-8 col-md-6">
        	<div class="card">
        		<div class="card-body">
			    		<h5 class="card-title">{$lang.REGISTER.CHOOSE_PW}</h5>
			 			<form role="form" method="POST" id="set-password-form">
			    			<div class="form-group">
								<label for="pwd">
                    				{$lang.REGISTER.YOUR_PW}
                    			</label>
			    				<input type="password" name="pwd" id="pwd" class="form-control input-sm" placeholder="{$lang.REGISTER.YOUR_PW}">
			    				<input type="hidden" id="set-password-pwd-hashed" value="" />
			    			</div>

			    			<div class="form-group">
								<label for="pwd2">
                    				{$lang.REGISTER.REPEAT_PW}
                    			</label>
			    				<input type="password" name="pwd2" id="pwd2" class="form-control input-sm" placeholder="{$lang.REGISTER.REPEAT_PW}">
			    				<input type="hidden" id="set-password-pwd2-hashed" value="" />
							</div>

							{if $cust_source}
							<div class="form-group">
                        		<label for="cust_source">
                    				{$lang.CUST_SOURCE.TITLE}
                    			</label>
								<select id="cust_source" name="cust_source" class="form-control">
									{if !$cust_source_duty}
									<option value="">{$lang.CUST_SOURCE.NONE}</option>
									{/if}
									{foreach from=$cso item=cs}
									<option{if isset($smarty.post.cust_source) && $smarty.post.cust_source == $cs} selected=""{/if}>{$cs|htmlentities}</option>
									{/foreach}
								</select>
                        	</div>
							{/if}
							
							<div class="checkbox">
								<label>
									<input type="checkbox" name="newsletter" value="1" />
									{$lang.GENERAL.NEWSLETTER_CB}
								</label>
							</div>
							
							<input type="hidden" id="password_type" name="password_type" value="plain" />
			    			<input type="submit" name="setpw" value="{$lang.REGISTER.CREATE_ACCOUNT}" class="btn btn-primary btn-block">
			    		</form>
			    	</div>
	    		</div>
    		</div>
    	</div>{/if}
    </div>