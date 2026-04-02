<div id="content">
	<div class="container">
        {if $step == "home"}
        <h1>{$l.TITLE}</h1><hr style="margin-bottom: 10px;">
        <span style="font-size: 13px;">{$l.YAH}: {$l.TITLE}</span><br /><br />

		<form method="POST">
            <div class="input-group">
                <input type="text" class="form-control input-lg" placeholder="{$l.SEARCHWORD}" name="searchword">
                <span class="input-group-btn">
                    <input type="submit" class="btn btn-primary btn-block btn-lg" style="height: 46px;" value="{$l.SEARCH}">
                </span>
            </div>
        </form>

        <h3>{$l.CATS}</h3>
        {if $cats|@count}
        <div class="row">
            {foreach from=$cats item=cat}
            <div class="col-md-6">
                <a href="{$cfg.PAGEURL}knowledgebase/{$cat->ID}">
                    <i class="fa fa-folder-o"></i> {$cat->name|htmlentities}
                </a>
            </div>
            {/foreach}
        </div>
        {else} 
        {$l.NOCATS}
        {/if}

        <h3>{$l.POPQ}</h3>
        {if $popq|@count}
        {foreach from=$popq item=q}
        <a href="{$cfg.PAGEURL}knowledgebase/{$q->category}/{$q->ID}">
            <i class="fa fa-file-o"></i> {$q->title|htmlentities}
        </a><br />
        {/foreach}
        {else}
        {$l.NOPOPQ}
        {/if}
        {elseif $step == "search"}
        <h1>{$l.TITLE}</h1><hr style="margin-bottom: 10px;">
        <span style="font-size: 13px;">{$l.YAH}: <a href="{$cfg.PAGEURL}knowledgebase">{$l.TITLE}</a> &raquo; {$l.SEARCHRES}</span><br /><br />

		<form method="POST">
            <div class="input-group">
                <input type="text" class="form-control input-lg" placeholder="{$l.SEARCHWORD}" value="{$smarty.post.searchword|htmlentities}" name="searchword">
                <span class="input-group-btn">
                    <input type="submit" class="btn btn-primary btn-block btn-lg" style="height: 46px;" value="{$l.SEARCH}">
                </span>
            </div>
        </form><br />

        {foreach from=$res item=q}
        <a href="{$cfg.PAGEURL}knowledgebase/{$q->category}/{$q->ID}">
            <i class="fa fa-file-o"></i> {$q->category_name|htmlentities}: {$q->title|htmlentities}
        </a><br />
        {foreachelse}
        {$l.NOARTS}
        {/foreach}
        {elseif $step == "category"}
        <h1>{$cat->name|htmlentities}</h1><hr style="margin-bottom: 10px;">
        <span style="font-size: 13px;">{$l.YAH}: <a href="{$cfg.PAGEURL}knowledgebase">{$l.TITLE}</a> &raquo; {$cat->name|htmlentities}</span><br /><br />

        {foreach from=$qs item=q}
        <a href="{$cfg.PAGEURL}knowledgebase/{$cat->ID}/{$q->ID}"><i class="fa fa-file-o"></i> {$q->title|htmlentities}</a><br />
        {foreachelse}
        {$l.NOCATQ}
        {/foreach}
        {elseif $step == "question"}
        <h1>{$q->title|htmlentities}</h1><hr style="margin-bottom: 10px;">
        <span style="font-size: 13px;">{$l.YAH}: <a href="{$cfg.PAGEURL}knowledgebase">{$l.TITLE}</a> &raquo; <a href="{$cfg.PAGEURL}knowledgebase/{$cat->ID}">{$cat->name|htmlentities}</a> &raquo; {$q->title|htmlentities}</span><br /><br />

        {$q->article|nl2br}

        <div class="alert alert-info" style="margin-bottom: 0; margin-top: 20px;">
            {if $can_rate}
            <span id="should_rate"><b>{$l.HDYR}</b> <a href="#" class="btn btn-xs btn-success btn-rate" data-rating="1">{$l.RGOOD}</a> <a href="#" class="btn btn-xs btn-warning btn-rate" data-rating="0">{$l.RBAD}</a></span>
            <span id="is_rating" style="display: none;"><i class="fa fa-spinner fa-spin"></i> {$lang.GENERAL.PLEASE_WAIT}</span>
            <span id="has_rated" style="display: none;">{$l.TYFR}</span>

            <script>
            $(".btn-rate").click(function(e) {
                e.preventDefault();

                $("#should_rate").hide();
                $("#is_rating").show();

                $.post("", {
                    rating: $(this).data("rating"),
                    csrf_token: "{ct}"
                }, function() {
                    $("#is_rating").hide();
                    $("#has_rated").show();
                });
            });
            </script>
            {else}
            {$l.TYFR}
            {/if}
        </div>
        {/if}
	</div>
</div><br />