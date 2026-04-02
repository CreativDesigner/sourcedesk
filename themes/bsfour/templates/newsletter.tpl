<div class="container">
	<h1>{$lang.NEWSLETTER.TITLE}</h1><hr />

	<p style="text-align: justify;">{$lang.NEWSLETTER.INTRO} {if $logged_in}{$lang.NEWSLETTER.ILI}{else}{$lang.NEWSLETTER.INLI}{/if}</p>

	{if isset($err)}<div class="alert alert-danger">{$err}</div>{/if}
	{if isset($suc)}<div class="alert alert-success">{$suc}</div>{/if}

	<br />

	{if !$logged_in}
	<form method="POST">
	  <div class="form-group">
	    <label>{$lang.NEWSLETTER.NAME}</label>
	    <input type="text" class="form-control" name="name" placeholder="{$lang.NEWSLETTER.NAMEP}" value="{if isset($smarty.post.name)}{$smarty.post.name|htmlentities}{/if}">
	  </div>
	  <div class="form-group">
	    <label>{$lang.NEWSLETTER.MAIL}</label>
	    <input type="email" class="form-control" name="mail" placeholder="{$lang.NEWSLETTER.MAILP}" value="{if isset($smarty.post.mail)}{$smarty.post.mail|htmlentities}{/if}">
	  </div>
	  <div class="form-group">
	    <label>{$lang.NEWSLETTER.LANG}</label>
	    <p class="form-control-static">{$lang.NAME} - <a href="#" data-toggle="modal" data-target="#languageModal">{$lang.NEWSLETTER.LANGC}</a></p>
	  </div>
		<div class="form-group">
	  	<label>{$lang.NEWSLETTER.CAT}</label>
	    <div style="text-align: left;">
	      <div class="row">
	      	{foreach from=$nl item=name key=id}
	      	<div class="col-md-4">
	      	  <div class="checkbox">
	      	  	<label>
	      	  	  <input type="checkbox" name="nl[{$id}]" value="1"{if is_array($smarty.post.nl) && in_array($id, $smarty.post.nl)} checked=""{/if}>
	      	  	  {$name|htmlentities}
	      	  	</label>
	      	  </div>
	      	</div>
	      	{/foreach}
	      </div>
	    </div>
	  </div>
		<div class="form-group">
	  	<label>{$lang.NEWSLETTER.IA}</label>
	    <div style="text-align: justify;">
	      <div class="checkbox">
					<label>
						<input type="checkbox" name="disclaimer" value="1">

						{$lang.NEWSLETTER.IAT}
					</label>
				</div>
	    </div>
	  </div>
		<div class="form-group">
	  	<div>
	      <button type="submit" class="btn btn-primary btn-block" name="save">{$lang.NEWSLETTER.SAVE}</button>
	    </div>
	  </div>
	</form>{else}

	<form class="form-horizontal" method="POST">
	  <div class="form-group">
	    <label>{$lang.NEWSLETTER.NAME}</label>
	    <p class="form-control-static">{$user.name|htmlentities}</p>
	  </div>
	  <div class="form-group">
	    <label>{$lang.NEWSLETTER.MAIL}</label>
	    <p class="form-control-static">{$user.mail|htmlentities}</p>
	  </div>
	  <div class="form-group">
	    <label>{$lang.NEWSLETTER.LANG}</label>
	    <p class="form-control-static">{$langs.{$user.language}} - <a href="#" data-toggle="modal" data-target="#languageModal">{$lang.NEWSLETTER.LANGC}</a></p>
	  </div>
	  <div class="form-group">
	  	<label>{$lang.NEWSLETTER.CAT}</label>
	    <div style="text-align: left;">
	      <div class="row">
	      	{foreach from=$nl item=name key=id}
	      	<div class="col-md-4">
	      	  <div class="checkbox">
	      	  	<label>
	      	  	  <input type="checkbox" name="nl[{$id}]" value="1"{if in_array($id, explode("|", $user.newsletter))} checked=""{/if}>
	      	  	  {$name|htmlentities}
	      	  	</label>
	      	  </div>
	      	</div>
	      	{/foreach}
	      </div>
	    </div>
	  </div>
	  <div class="form-group">
	  	<div>
	      <button type="submit" class="btn btn-primary btn-block" name="save">{$lang.NEWSLETTER.SAVE}</button>
	    </div>
	  </div>
	</form>{/if}

	<div></div>
</div>
