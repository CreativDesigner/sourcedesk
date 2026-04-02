<style>
.contact-form{ margin-top:15px;}
.contact-form .textarea{ min-height:220px; resize:none;}
.form-control{ box-shadow:none; border-color:#eee; height:49px;}
.form-control:focus{ box-shadow:none; }
</style>

<div class="container">
	<h1>{$lang.CONTACT.SHORT}</h1><hr />
	{if $step == 1}<p style="text-align: justify;">{$lang.CONTACT.INTRO}</p>

	{if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

	<form role="form" id="contact-form" class="contact-form captcha-form" method="POST">
	    <div class="row">
			<div class="col-md-6">
	  		<div class="form-group">
	            <input type="text" class="form-control enter-disallow" name="name" id="name" placeholder="{$lang.CONTACT.NAME}"{if isset($smarty.post.name)} value="{$smarty.post.name|htmlentities}"{/if}>
	  		</div>
	  	</div>
	    	<div class="col-md-6">
	  		<div class="form-group">
	            <input type="email" class="form-control enter-disallow" name="email" id="email" placeholder="{$lang.CONTACT.MAIL}"{if isset($smarty.post.email)} value="{$smarty.post.email|htmlentities}"{/if}>
	  		</div>
	  	</div>
	  	</div>
	  	<div class="row">
	  		<div class="col-md-12">
	  		<div class="form-group">
	            <textarea class="form-control textarea" rows="3" name="message" id="message" placeholder="{$lang.CONTACT.MESSAGE}">{if isset($smarty.post.message)}{$smarty.post.message|htmlentities}{/if}</textarea>
	  		</div>
	  		{if !$logged_in}
	  		{if isset($captchaText)}
			<div class="form-group">
				<input type="text" name="captcha" id="captcha" class="form-control" placeholder="{$captchaText}" autocomplete="off">
			</div>{/if}
			{if isset($captchaCode)}
			<div class="pull-right">{$captchaCode}</div>
			{/if}
			{/if}
	  	</div>
	    </div>
	    <div class="row">
	    <div class="col-md-12">
	    <input type="hidden" name="token" value="{$token}" />
	  <button type="{if isset($captchaModal)}button{else}submit{/if}" class="btn btn-primary pull-right enter-disallow"{if isset($captchaExec) && !$logged_in} onclick="if(form_validate('contact-form')){ {$captchaExec} } else { return false; }"{/if}{if isset($captchaModal)} onclick="if(form_validate('contact-form')){ openCaptchaModal(); } else { return false; }"{/if}>{$lang.CONTACT.DO}</button>
	  </div>
	  </div>

	{if isset($captchaModal)}
	<div class="modal fade" id="captchaModal" tabindex="-1" role="dialog">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-body">
	        {$captchaModal}
	        <button type="submit" class="btn btn-primary btn-block" id="captchaSubmit">{$lang.CONTACT.DO}</button>
	      </div>
	    </div>
	  </div>
	</div>
	{/if}
	</form>
	{else}
	<div class="alert alert-success">{$lang.CONTACT.THANKS}</div>
	<p style="text-align: justify;">{$lang.CONTACT.INTRO2}</p>

	<div class="table-responsive">
		<table class="table table-bordered">
			<tr>
				<th>{$lang.CONTACT.NAME2}</th>
				<td>{$smarty.post.name|htmlentities}</td>
			</tr>

			<tr>
				<th>{$lang.CONTACT.MAIL2}</th>
				<td>{$smarty.post.email|htmlentities}</td>
			</tr>

			<tr>
				<th style="vertical-align: middle;">{$lang.CONTACT.MESSAGE2}</th>
				<td>{$smarty.post.message|htmlentities|nl2br}</td>
			</tr>
		</table>
	</div>
	{/if}
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery.bootstrapvalidator/0.5.2/css/bootstrapValidator.min.css"/><br />
