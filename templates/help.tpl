<div id="content">
	<div class="container">
			<h1>{$lang.HELP.TITLE} <small>{$license.name}</small></h1><hr>
			{if isset($msg)}{$msg}{else}<div class="alert alert-warning">{$lang.GENERAL.EMAIL_PASSWORDS_HELP}</div>{/if}
			<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
			  <div class="panel panel-default">
			    <div class="panel-heading" role="tab" id="headingOne">
			      <h4 class="panel-title">
			        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
			          {$lang.HELP.QUESTIONS}
			        </a>
			      </h4>
			    </div>
			    <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
			      <div class="panel-body">
			        {$lang.HELP.QUESTIONS_INTRO|replace:"%m":$cfg.PAGEMAIL|replace:"%s":$license.name}<br /><br />
			        <form method="POST">
			        	<textarea name="message" class="form-control" style="resize:none;height:150px;">{$smarty.post.message|htmlentities}</textarea>
			        	<input type="hidden" name="aid" value="{$aid}">
			        	<br /><input type="submit" name="send_message" value="{$lang.HELP.QUESTIONS_SEND}" class="btn btn-block btn-primary">
			        </form>
			      </div>
			    </div>
			  </div>
			  <div class="panel panel-default">
			    <div class="panel-heading" role="tab" id="headingTwo">
			      <h4 class="panel-title">
			        <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
			          {$lang.HELP.BUG}
			        </a>
			      </h4>
			    </div>
			    <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
			      <div class="panel-body">
			        {$lang.HELP.BUG_INTRO}

			        <br /><br />

			        <form role="form" action="{$cfg.PAGEURL}bugtracker" method="POST" enctype="multipart/form-data">
					  <div class="form-group">
					    <label>{$lang.GENERAL.DESCRIPTION}</label>
					    <textarea class="form-control" name="description" style="height:130px;">{if isset($smarty.post.description)}{$smarty.post.description|htmlentities}{/if}</textarea>
					  </div>
					  <div class="form-group">
					    <label>{$lang.BUGTRACKER.STEPS_REPRODUCE}</label>
					    <textarea class="form-control" name="reproduce" style="height:130px;">{if isset($smarty.post.reproduce)}{$smarty.post.reproduce|htmlentities}{/if}</textarea>
					  </div>
					  <div class="form-group">
					    <label>{$lang.BUGTRACKER.FILES}</label>
					    <input type="hidden" name="MAX_FILE_SIZE" value="3145728" />
					    <input type="file" name="files[]" multiple="multiple">
					    <p class="help-block">{$lang.BUGTRACKER.FILES_HINT}</p>
					  </div>
					  <input type="hidden" name="aid" value="{$aid}">
					  <input type="hidden" name="pid" value="{$license.product}">
					  <input type="hidden" name="new" value="1">
					  <center><button name="submit" type="submit" class="btn btn-block btn-primary">{$lang.BUGTRACKER.SEND}</button></center>
					</form>
			      </div>
			    </div>
			  </div>
			  <div class="panel panel-default">
			    <div class="panel-heading" role="tab" id="headingThree">
			      <h4 class="panel-title">
			        <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
			          {$lang.HELP.CREDENTIALS}
			        </a>
			      </h4>
			    </div>
			    <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
			      <div class="panel-body">
			        {$lang.HELP.CREDENTIALS_INTRO}<br /><br />

			        <form method="POST" onsubmit="$('#credentialsModal').modal('show'); return false;">
			        	<textarea name="credentials" class="form-control" style="resize:none;height:150px;">{$smarty.post.credentials|htmlentities}</textarea>
			        	<input type="hidden" name="aid" value="{$aid}"><input type="hidden" name="save_credentials" value="yes" />
			        	<br /><input type="button" value="{$lang.HELP.CREDENTIALS_SEND}" class="btn btn-block btn-primary" data-toggle="modal" data-target="#credentialsModal">

			        	<div class="modal fade" id="credentialsModal" tabindex="-1" role="dialog">
						  <div class="modal-dialog" role="document">
						    <div class="modal-content">
						      <div class="modal-header">
						        <h4 class="modal-title">{$lang.HELP.CREDENTIALS}</h4>
						      </div>
						      <div class="modal-body">
						        <p style="text-align: justify;">{$lang.HELP.CREDENTIALS_MODAL}</p>
						      </div>
						      <div class="modal-footer">
						        <button type="button" class="btn btn-default" data-dismiss="modal">{$lang.GENERAL.CLOSE}</button>
						        <button type="button" class="btn btn-primary" onclick="form.submit();">{$lang.HELP.CREDENTIALS_SEND}</button>
						      </div>
						    </div>
						  </div>
						</div>
			        </form>
			      </div>
			    </div>
			  </div>
			</div>
	</div>
</div><br />