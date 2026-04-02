<div class="container">
	
	<h1>{$lang.REGISTER.TITLE}</h1><hr>
	<div class="row form-group">
        <div class="col-xs-12">
            <ul class="nav nav-pills nav-justified thumbnail setup-panel">
                <li class="disabled"><a href="#step-1">
                    <h4 class="list-group-item-heading">{$lang.REGISTER.STEP1}</h4>
                    <p class="list-group-item-text">{$lang.REGISTER.STEP1D}</p>
                </a></li>
                <li class="disabled"><a href="#step-2">
                    <h4 class="list-group-item-heading">{$lang.REGISTER.STEP2}</h4>
                    <p class="list-group-item-text">{$lang.REGISTER.STEP2D}</p>
                </a></li>
				<li class="active"><a href="#step-3">
                    <h4 class="list-group-item-heading">{$lang.REGISTER.STEP3}</h4>
                    <p class="list-group-item-text" style="color:white;">{$lang.REGISTER.STEP3D}</p>
                </a></li>
            </ul>
        </div>
	</div>
	
	{if isset($error)}<div class="alert alert-danger">
		{$error}
	</div>{/if}
	
	{if isset($success)}<div class="alert alert-success">
		{$success}
	</div>{/if}
	{if !isset($donotshow)}
        <div class="row centered-form">
        <div class="col-xs-12 col-sm-8 col-md-4 col-sm-offset-2 col-md-offset-4">
        	<div class="panel panel-default">
        		<div class="panel-heading">
			    		<h3 class="panel-title">{$lang.REGISTER.CHOOSE_PW}</h3>
			 			</div>
			 			<div class="panel-body">
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