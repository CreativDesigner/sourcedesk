<div class="container">
	<h1>{$lang.WISHLIST.TITLE}</h1><hr />

	{if $task == "home"}
	<p style="text-align: justify; margin-bottom: 0;">Die Wunschliste erm&ouml;glicht es Ihnen, Vorschl&auml;ge f&uuml;r die Weiterentwicklung unserer Produkte zu &auml;u&szlig;ern. Sie k&ouml;nnen Ihren Vorschlag senden und wir werden beurteilen, ob wir den Vorschlag f&uuml;r sinnvoll halten. Wir werden Ihnen dann zeitnah entweder eine Absage erteilen, weil der Vorschlag zum Beispiel nicht umsetzbar ist, oder Ihnen mitteilen, dass wir die Funktion entwickeln k&ouml;nnen.<br /><br />Jenachdem wie interessant der Vorschlag ist, werden wir die Idee entweder auf eigene Kosten umsetzen und allen Kunden bereitstellen oder Ihnen ein Angebot f&uuml;r die Beteiligung an den Programmierkosten oder f&uuml;r die exklusive Programmierung machen. Auch die Diskussion von Vorschl&auml;gen auf unserer Homepage ist m&ouml;glich.</p>
	
	<div class="row">
		<div class="col-md-6 list-category text-primary">
			<h3 class="title">
			    Ihre Produkte
			</h3>
			 
			<div class="list-group">
				{foreach from=$products item=info key=id}
				<a href="{$cfg.PAGEURL}wishlist/product/{$id}" class="list-group-item"><div class="truncate pull-left">{$info.name}</div><span class="label label-primary pull-right">{$info.count} {if $info.count == 0 || $info.count > 1}W&uuml;nsche{else}Wunsch{/if}</span></a>
				{foreachelse}
				<div class="list-group-item no-border"><div class="truncate">Sie haben leider keine Produkte.</div></div>
				{/foreach}
			</div>
		</div>

		<div class="col-md-6 list-category text-info">
			<h3 class="title">
			    Ihre W&uuml;nsche
			</h3>
			 
			<div class="list-group">
				{foreach from=$wishes item=info key=id}
				<a href="{$cfg.PAGEURL}wishlist/wish/{$id}" class="list-group-item"><div class="truncate pull-left">{$info.title|htmlentities}</div><span class="label label-info pull-right">{$info.likes} Like{if $info.likes != 1}s{/if}</span></a>
				{foreachelse}
				<div class="list-group-item no-border"><div class="truncate">Sie haben noch keine W&uuml;nsche ge&auml;u&szlig;ert.</div></div>
				{/foreach}
			</div>
		</div>
	</div>

	<style>
		div.list-category h3.title {
			display: block;
			padding: 0px 0 15px 0;
			font-size: 20px;
			font-weight: 100;
			margin-bottom: 0px;
			border-bottom: 1px solid;
		}

		div.list-category .list-group-item:first-child {
			border-top: 0;
		}

		div.list-category .list-group-item {
			border-left: 0;
			border-right: 0;
			border-radius: 0;
			border-bottom: 1px solid #E0E4E9;
			color: #4c4c4c; 
			text-decoration: none;
			padding-left: 0;
			padding-right: 0;
			overflow: hidden;
		}

		.no-border {
			border-bottom: none !important;
		}

		.truncate {
			width: 80%;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			padding-left: 5px;
		}

		.no-border .truncate {
			padding-left: 0;
		}
		
		.pull-right {
			margin-right: 5px;
		}
	</style>
	{elseif $task == "product"}
	<a href="{$cfg.PAGEURL}wishlist/add/{$pid}" class="btn btn-primary">Wunsch anlegen</a> <a href="{$cfg.PAGEURL}wishlist" class="btn btn-default">Zur&uuml;ck zur &Uuml;bersicht</a><br /><br />

	<form method="POST" class="form-inline" action="{$cfg.PAGEURL}wishlist/product/{$pid}">
		<div class="input-group">
			<input type="text" name="searchword" class="form-control" value="{if isset($smarty.post.searchword)}{$smarty.post.searchword|htmlentities}{/if}" placeholder="{$lang.WISHLIST.SEARCH}" minlength="5" required="">
			<div class="input-group-btn">
               <button class="btn btn-primary" type="submit">
                   <span class="fa fa-search"></span>
               </button>
            </div>
		</div>
	</form><br />

	<div class="panel panel-default">
	  <div class="panel-heading">{if $search}{$lang.WISHLIST.SEARCHRESULTS}{else}W&uuml;nsche f&uuml;r {$product_name} <a href="#" id="abo" class="pull-right"{if $abo} style="display: none;"{/if}><i class="fa fa-envelope-o"></i> Abonnieren</a><a href="#" id="deabo" class="pull-right"{if !$abo} style="display: none;"{/if}><i class="fa fa-envelope"></i> Deabonnieren</a>{/if}</div>
	  <div class="panel-body">
	  	{assign "i" "0"}
	    {foreach from=$pwishes item=info key=id}
	    {assign var="i" value=$i+1}
	    {if empty($info->answer)}<h4 style="margin-left: -5px; margin-top: 0; margin-bottom: 5px; display: inline;"><i class="fa fa-clock-o fa-fw"></i></h4>{/if}
	    {if substr($info->answer, 0, 1) == "1" || substr($info->answer, 0, 1) == "5" || substr($info->answer, 0, 1) == "6"}<h4 style="margin-left: -5px; margin-top: 0; margin-bottom: 5px; display: inline;"><i class="fa fa-cogs fa-fw"></i></h4>{/if}
	    {if substr($info->answer, 0, 1) == "2" || substr($info->answer, 0, 1) == "3"}<h4 style="margin-left: -5px; margin-top: 0; margin-bottom: 5px; display: inline;"><i class="fa fa-money fa-fw"></i></h4>{/if}
	    {if substr($info->answer, 0, 1) == "7" || substr($info->answer, 0, 1) == "8"}<h4 style="margin-left: -5px; margin-top: 0; margin-bottom: 5px; display: inline;"><i class="fa fa-check fa-fw"></i></h4>{/if}
	    {if substr($info->answer, 0, 1) == "4"}<h4 style="margin-left: -5px; margin-top: 0; margin-bottom: 5px; display: inline;"><i class="fa fa-times fa-fw"></i></h4>{/if}
		<a href="{$cfg.PAGEURL}wishlist/wish/{$id}"><h4 style="margin-top: 0; margin-bottom: 5px; display: inline;">{$info->title|htmlentities}</h4></a><br />
	    <i class="fa fa-calendar"></i> {dfo d=$info->date m=0} &nbsp;
	    <i class="fa fa-thumbs-{if !$info->like}o-{/if}up"></i> {$info->likes} &nbsp;
	    <i class="fa fa-comments{if !$info->commented}-o{/if}"></i> {$info->comments} &nbsp;
	    <i class="fa fa-bell{if !$info->abo}-o{/if}"></i>
	    {if $i < $pwishes|count}<hr style="margin-top: 10px; margin-bottom: 10px;" />{/if}
	    {foreachelse}
		{if $search}
		{$lang.WISHLIST.NORESULTS}
		{else}
	    Es sind noch keine W&uuml;nsche f&uuml;r dieses Produkt vorhanden.<br />
	    Legen Sie doch <a href="{$cfg.PAGEURL}wishlist/add/{$pid}">einen an</a>.
		{/if}
	    {/foreach}
	  </div>
		{if $pages > 1}
		<div class="panel-footer">
			<center>
				{if $page - 1 >= 1}<a href="{$cfg.PAGEURL}wishlist/product/{$pid}/{$page - 1}" class="pull-left"><i class="fa fa-arrow-left"></i> Vorherige Seite</a>{/if}
				Seite {$page} / {$pages}
				{if $page + 1 <= $pages}<a href="{$cfg.PAGEURL}wishlist/product/{$pid}/{$page + 1}" class="pull-right">Nächste Seite <i class="fa fa-arrow-right"></i></a>{/if}
			</center>
		</div>
		{/if}
	</div>
	<a href="{$cfg.PAGEURL}wishlist/add/{$pid}" class="btn btn-primary">Wunsch anlegen</a> <a href="{$cfg.PAGEURL}wishlist" class="btn btn-default">Zur&uuml;ck zur &Uuml;bersicht</a>
	{elseif $task == "add"}
	<a href="{$cfg.PAGEURL}wishlist/product/{$pid}" class="btn btn-default">Zur&uuml;ck zu den Produkt-W&uuml;nschen</a> <a href="{$cfg.PAGEURL}wishlist" class="btn btn-default">Zur&uuml;ck zur &Uuml;bersicht</a><br /><br />

	{if !empty($error)}<div class="alert alert-danger">{$error}</div>{/if}

	<div class="panel panel-default">
	  <div class="panel-heading">Wunsch f&uuml;r {$product_name} anlegen</div>
	  <div class="panel-body">
	  	<form method="POST">
	  		<div class="form-group">
	  			<label>Titel</label>
	  			<input type="text" name="title" value="{if isset($smarty.post.title)}{$smarty.post.title|htmlentities}{/if}" placeholder="Sortierung der Spalten" class="form-control" maxlength="128" />
	  			<p class="help-block">Fassen Sie Ihr Anliegen kurz und pregnant zusammen.</p>
	  		</div>

	  		<div class="form-group">
	  			<label>Text</label>
	  			<textarea name="description" placeholder="Die Spalten sollen sortierbar sein, dazu..." class="form-control" style="height: 200px; resize: none;">{if isset($smarty.post.description)}{$smarty.post.description|htmlentities}{/if}</textarea>
	  			<p class="help-block">Beschreiben Sie genau, was Sie gerne h&auml;tten.</p>
	  		</div>

	  		<input type="submit" value="Wunsch erstellen" class="btn btn-primary btn-block" />
	  	</form>
	  </div>
	</div>
	{elseif $task == "wish"}
	<a href="{$cfg.PAGEURL}wishlist/product/{$wish->product}" class="btn btn-default">Zur&uuml;ck zu den Produkt-W&uuml;nschen</a> <a href="{$cfg.PAGEURL}wishlist" class="btn btn-default">Zur&uuml;ck zur &Uuml;bersicht</a><br /><br />

	{if !empty($suc)}
	<div class="alert alert-success">{$suc}</div>
	{/if}

	<div class="panel panel-primary">
	  <div class="panel-heading">{$wish->title|htmlentities} <span class="label label-default pull-right"><span id="likes">{$likes}</span> gef&auml;llt das</span></div>
	  <div class="panel-body">
	  	<i class="fa fa-calendar"></i> {dfo d=$wish->date m=0} &nbsp; <i class="fa fa-flag"></i> <a href="{$cfg.PAGEURL}product/{$wish->product}" target="_blank">{$wish->product_name}</a><br /><br />
	    {$wish->description|htmlentities|nl2br}<br /><br />
	    <a href="#" id="like"{if $like} style="display: none;"{/if}><i class="fa fa-thumbs-o-up"></i> Gef&auml;llt mir</a><a href="#" id="unlike"{if !$like} style="display: none;"{/if}><i class="fa fa-thumbs-o-down"></i> Gef&auml;llt mir nicht mehr</a> &nbsp;
	    <a href="#" id="abo"{if $abo} style="display: none;"{/if}><i class="fa fa-envelope-o"></i> Abonnieren</a><a href="#" id="deabo"{if !$abo} style="display: none;"{/if}><i class="fa fa-envelope"></i> Deabonnieren</a>
	  </div>
	  <div class="panel-footer">
	  	{if empty($wish->answer)}
	  	<i class="fa fa-clock-o"></i> Warte auf Antwort <a data-toggle="tooltip" title="Wir haben den Wunsch noch nicht geprüft. In Kürze werden wir den Status ändern."><i class="fa fa-question"></i></a>
	  	{elseif $wish->answer == "1"}
	  	<i class="fa fa-check"></i> Wird kostenfrei entwickelt <a data-toggle="tooltip" title="Der Wunsch ist interessant für alle Kunden. Wir werden ihn zeitnah und kostenfrei umsetzen."><i class="fa fa-question"></i></a>
	  	{elseif substr($wish->answer, 0, 1) == "2"}
	  	<i class="fa fa-money"></i> Kann f&uuml;r {$amount} Beteiligung entwickelt werden <a data-toggle="tooltip" title="Der Wunsch ist zwar interessant, jedoch auch zeitaufwändig. Gegen eine Beteiligung können wir ihn für alle Kunden umsetzen."><i class="fa fa-question"></i></a>
	  	<span class="pull-right">
	  		<a href="{$cfg.PAGEURL}wishlist/wish/{$wish->ID}/buy" onclick="{if ($user.credit < substr($wish->answer, 1))}alert('Ihr Guthaben reicht nicht aus. Bitte laden Sie zuerst Ihr Guthaben entsprechend auf.'); return false;{else}return confirm('Wollen Sie uns wirklich mit der Entwicklung beauftragen? Der Betrag wird sofort und unwiderruflich von Ihrem Konto abgebucht.');{/if}">
		  		<i class="fa fa-check"></i>
		  		Angebot akzeptieren
	  		</a>
	  	</span>
	  	{elseif substr($wish->answer, 0, 1) == "3"}
	  	<i class="fa fa-money"></i> Kann f&uuml;r {$amount} exklusiv entwickelt werden <a data-toggle="tooltip" title="Der Wünscher kann die Umsetzung bei uns beauftragen. Leider wird der Wunsch nicht für alle Kunden zur Verfügung stehen, da er zu speziell ist."><i class="fa fa-question"></i></a>
	  	{if $wish->user == $user.ID}
	  	<span class="pull-right">
	  		<a href="{$cfg.PAGEURL}wishlist/wish/{$wish->ID}/buy" onclick="{if ($user.credit < substr($wish->answer, 1))}alert('Ihr Guthaben reicht nicht aus. Bitte laden Sie zuerst Ihr Guthaben entsprechend auf.'); return false;{else}return confirm('Wollen Sie uns wirklich mit der Entwicklung beauftragen? Der Betrag wird sofort und unwiderruflich von Ihrem Konto abgebucht.');{/if}">
		  		<i class="fa fa-check"></i>
		  		Angebot akzeptieren
	  		</a>
	  	</span>
	  	{/if}
	  	{elseif substr($wish->answer, 0, 1) == "4"}
	  	<i class="fa fa-times"></i> {substr($wish->answer, 1)|htmlentities} <a data-toggle="tooltip" title="Leider kann der Wunsch so nicht umgesetzt werden."><i class="fa fa-question"></i></a>
	  	{elseif $wish->answer == "5"}
	  	<i class="fa fa-check"></i> Beteiligung wurde bezahlt, in Entwicklung <a data-toggle="tooltip" title="Die Entwicklungsarbeit wurde finanziert und der Wunsch wird umgesetzt. In Kürze steht die Funktion allen Kunden zur Verfügung."><i class="fa fa-question"></i></a>
	  	{elseif $wish->answer == "6"}
	  	<i class="fa fa-check"></i> Exklusive Entwicklung wurde bezahlt <a data-toggle="tooltip" title="Die Entwicklungsarbeit wurde finanziert und der Wunsch wird für den Kunden umgesetzt. Die Funktion wird exklusiv entwickelt und steht leider nur dem Wünscher zur Verfügung."><i class="fa fa-question"></i></a>
	  	{elseif $wish->answer == "7"}
	  	<i class="fa fa-check"></i> Entwicklung abgeschlossen <a data-toggle="tooltip" title="Die Funktion wurde implementiert und steht nun allen Kunden zur Verfügung."><i class="fa fa-question"></i></a>
	  	{elseif $wish->answer == "8"}
	  	<i class="fa fa-check"></i> Exklusive Entwicklung abgeschlossen <a data-toggle="tooltip" title="Die Funktion wurde exklusiv für den Wünscher implementiert. Leider steht sie nicht für alle zur Verfügung."><i class="fa fa-question"></i></a>
	  	{/if}
	  </div>
	</div>

	<div class="panel panel-default">
	  <div class="panel-heading"><i class="fa fa-comments"></i> Kommentare <span class="label label-default pull-right">{$comments|count}</span></div>
	  <div class="panel-body">
	  	{assign "i" "0"}
	  	{foreach from=$comments key=id item=info}
	  	{assign var="i" value=$i+1}
	  	{$info->message|htmlentities}<br /><small>Am {dfo d=$info->time t="um"} Uhr von {if $info->author != "admin"}{$info->author|htmlentities}{else}{$cfg.PAGENAME} <span class="label label-primary">{$lang.WISHLIST.OFFICIAL}</span>{/if} geschrieben</small>
	  	{if $i < $comments|count}<hr />{/if}
	  	{foreachelse}
	  	Es sind noch keine Kommentare vorhanden.
	  	{/foreach}
	  </div>
	  <div class="panel-footer text-primary">
	  	<a href="#" data-toggle="modal" data-target="#comment">Kommentar hinterlassen</a>
	  </div>
	</div>

	<div class="modal fade" id="comment" tabindex="-1" role="dialog">
	  <div class="modal-dialog" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">Kommentar schreiben</h4>
	      </div>
	      <div class="modal-body">
	      	<div class="alert alert-danger" style="display: none;"></div>

	      	<div class="form-group">
	      		<label>Name</label>
	      		<input type="text" id="name" value="{$lastname|htmlentities}" placeholder="{$user.firstname|htmlentities}" class="form-control" />
	      	</div>

	      	<div class="form-group">
	      		<label>Kommentar</label>
	      		<textarea id="text" placeholder="Vielen Dank f&uuml;r den Vorschlag, allerdings w&uuml;rde ich..." style="height: 100px; resize: none;" class="form-control"></textarea>
	      	</div>

	        <div class="checkbox">
	        	<label>
	        		<input type="checkbox" id="confirm" value="1">
	        		Ich best&auml;tige, dass mein Name und mein Kommentar keine obszönen, beleidigenden oder illegalen Inhalte enth&auml;lt.
	        	</label>
	        </div>
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-default" data-dismiss="modal">Schlie&szlig;en</button>
	        <button type="button" id="send" class="btn btn-primary">Kommentar absenden</button>
	      </div>
	    </div>
	  </div>
	</div>

	<a href="{$cfg.PAGEURL}wishlist/product/{$wish->product}" class="btn btn-default">Zur&uuml;ck zu den Produkt-W&uuml;nschen</a> <a href="{$cfg.PAGEURL}wishlist" class="btn btn-default">Zur&uuml;ck zur &Uuml;bersicht</a>
	{/if}<br /><br />
</div>