<div id="content">
	<div class="container">
        <h1>{$title}{if $step == "thread" && $t.lock} <small><i class="fa fa-lock"></i></small>{/if}{if isset($f) && $f.mod && $step == "threads"} <small><span class="label label-primary">{$l.IAMMOD}</span></small>{/if}</h1><hr style="margin-bottom: 10px;">
        {if $nick_warning}
        <div class="alert alert-warning"><a href="{$cfg.PAGEURL}profile" target="_blank">{$l.REQNICK}</a></div>
        {/if}

        {if $step == "overview"}
        <span style="font-size: 13px;">{$l.YAH}: {$l.OVERVIEW}</span><br /><br />

        {if $forums|@count}
        <form method="POST">
            <div class="input-group">
                <input type="text" class="form-control input-lg" placeholder="{$l.SEARCHWORD}" name="searchword">
                <span class="input-group-btn">
                    <input type="submit" class="btn btn-primary btn-block btn-lg" style="height: 46px;" value="{$l.SEARCH}">
                </span>
            </div>
        </form><br /><br />
        {/if}

        <div style="margin-bottom: -30px;">
            {foreach from=$forums item=f}
            <a href="{$cfg.PAGEURL}forum/{$f.ID}"><i class="fa fa-align-left fa-3x" style="float: left; margin-right: 20px;"></i></a>
            <div class="float: left;"><b><a href="{$cfg.PAGEURL}forum/{$f.ID}">{$f.name|htmlentities}</a></b>{if $f.mod} <span class="label label-primary">{$l.IAMMOD}</span>{/if}<span class="pull-right">{$l.THREADS}: {$f.threads}</span><br />{$f.description|htmlentities}<span class="pull-right">{$l.ENTRIES}: {$f.entries}</span></div>
            <br /><br />
            {foreachelse}
            {$l.NOTHING}<br /><br /><br />
            {/foreach}
        </div>
        {elseif $step == "nickpage"}
        <span style="font-size: 13px;">
            {$l.YAH}: <a href="{$cfg.PAGEURL}forum">{$l.OVERVIEW}</a> &raquo; {$title}
        </span><br /><br />

        {if !$has_posts}
        <div class="alert alert-warning" style="margin: 0;">{$l.UHNP}</div>
        {else}
        <div class="row">
            <div class="col-md-3">
                <div style="background-color: #f7f7f7; text-align: center; padding: 20px;">
                    <img src="{$avatar}" title="{$nickname|htmlentities}" alt="{$nickname|htmlentities}" style="border-radius: 50%;"><br /><br />
                    <b>{$nickname|htmlentities}</b>{if $mod} <span class="label label-primary">{$l.MOD}</span>{/if}
                    <br />{$l.ENTRIES}: {$count}
                </div>
            </div>

            <div class="col-md-9">
                <div class="card">
                    <div class="card-body" style="margin-bottom: -50px;"><h5 class="card-title">{$l.RECENT_ENTRIES}</h5>
                        {foreach from=$entries item=e}
                        <a href="{$cfg.PAGEURL}forum/{$e.forum}/{$e.thread}"><i class="fa fa-align-left fa-3x" style="float: left; margin-right: 20px;"></i></a>
                        <div class="float: left;"><b><a href="{$cfg.PAGEURL}forum/{$e.forum}/{$e.thread}">{$e.title|htmlentities}</a></b><br />{dfo d=$e.time s=1}</div>
                        <br /><br />
                        {foreachelse}
                        {$l.NORE}<br /><br /><br />
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
        {/if}
        {elseif $step == "threads"}
        <span style="font-size: 13px;">
            {$l.YAH}: <a href="{$cfg.PAGEURL}forum">{$l.OVERVIEW}</a> &raquo; {$f.name|htmlentities}
            {if $logged_in}
            <span class="pull-right"><a href="{$cfg.PAGEURL}forum/{$f.ID}/add"><i class="fa fa-plus-circle"></i></a> <a href="{$cfg.PAGEURL}forum/{$f.ID}/add">{$l.ADD_THREAD}</a></span>
            {/if}
        </span><br /><br />

        <div style="margin-bottom: -30px;">
        {foreach from=$threads item=t}
            {if isset($t.author.2)}<a href="{$t.author.2}">{/if}<img src="{$t.author.0}" alt="{$t.author.1|htmlentities}" title="{$t.author.1|htmlentities}" style="float: left; margin-right: 15px; border-radius: 50%; width: 40px;">{if isset($t.author.2)}</a>{/if}
            <div class="float: left;"><b><a href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}">{$t.title|htmlentities}</a></b>{if $t.lock} <i class="fa fa-lock"></i>{/if}<span class="pull-right">{$l.ENTRIES}: {$t.entries}</span><br />{$l.LEO} {if isset($t.author.2)}<a href="{$t.author.2}">{/if}{$t.author.1|htmlentities}{if isset($t.author.2)}</a>{/if} ({dfo d={$t.time} s=1})</div>
            <br /><br />
        {foreachelse}
            {$l.NOTHINGT}<br /><br /><br />
        {/foreach}

        {if $threads|count}
        <center>
            <div class="page-nation" style="margin-top: -25px;">
                <ul class="pagination pagination-large">
                    {if ($cPage - 1) <= 0}
                    <li class="page-item disabled"><a class="page-link">&laquo;</a></li>
                    {else}
                    <li class="page-item><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/p/{$cPage - 1}">&laquo;</a></li>
                    {/if}
                    {if ($cPage - 3) > 1}
                    <li class="page-item><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/p/1">1</a></li>
                    {if ($cPage - 3) > 2}
                    <li class="page-item disabled"><a class="page-link">...</a></li>
                    {/if}
                    {/if}
                    {for $i=($cPage - 3) to ($cPage - 1)}
                    {if $i > 0 && $i <= $pages}
                    <li class="page-item><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/p/{$i}">{$i}</a></li>
                    {/if}
                    {/for}
                    <li class="active"><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/p/{$cPage}">{$cPage}</a></li>
                    {for $i=($cPage + 1) to ($cPage + 3)}
                    {if $i > 0 && $i <= $pages}
                    <li class="page-item><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/p/{$i}">{$i}</a></li>
                    {/if}
                    {/for}
                    {if ($cPage + 4) < $pages}
                    <li class="page-item disabled"><a class="page-link">...</a></li>
                    {/if}
                    {if ($cPage + 3) < $pages}
                    <li class="page-item><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/p/{$pages}">{$pages}</a></li>
                    {/if}
                    {if ($cPage + 1) > $pages}
                    <li class="page-item disabled"><a class="page-link">&raquo;</a></li>
                    {else}
                    <li class="page-item><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/p/{$cPage + 1}">&raquo;</a></li>
                    {/if}
                </ul>
            </div>
        </center>
        {/if}
        </div>
        {elseif $step == "add_thread"}
        <span style="font-size: 13px;">{$l.YAH}: <a href="{$cfg.PAGEURL}forum">{$l.OVERVIEW}</a> &raquo; <a href="{$cfg.PAGEURL}forum/{$f.ID}">{$f.name|htmlentities}</a> &raquo; {$l.ADD_THREAD}</span><br /><br />
        <form method="POST">
            {if !empty($error)}<div class="alert alert-danger">{$error}</div>{/if}

            <div class="form-group">
                <label>{$l.TITLE2}</label>
                <input type="text" name="title" class="form-control" value="{if !empty($smarty.post.title)}{$smarty.post.title|htmlentities}{/if}">
            </div>

            <div class="form-group">
                <label>{$l.TEXT}</label>
                <textarea name="text" class="form-control" style="resize: vertical; width: 100%; height: 250px;">{if !empty($smarty.post.text)}{$smarty.post.text|htmlentities}{/if}</textarea>
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$l.ADD_THREAD}">
        </form>
        {elseif $step == "thread"}
        <span style="font-size: 13px;">{$l.YAH}: <a href="{$cfg.PAGEURL}forum">{$l.OVERVIEW}</a> &raquo; <a href="{$cfg.PAGEURL}forum/{$f.ID}">{$f.name|htmlentities}</a> &raquo; {$t.title|htmlentities}</span>
        {if $f.mod}<span class="pull-right" style="font-size: 13px;">{if !$t.lock}<a href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/lock/1"><i class="fa fa-lock"></i> {$l.LOCK_THREAD}</a>{else}<a href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/lock/0"><i class="fa fa-unlock"></i> {$l.OPEN_THREAD}</a>{/if} &nbsp; &nbsp; &nbsp; &nbsp; <a href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/delete" onclick="return confirm('{$l.SURE}');"><i class="fa fa-trash-o"></i> {$l.DELETE_THREAD}</a></span>{/if}<br /><br />

        {foreach from=$entries item=e}
        <div class="row">
            <div class="col-md-3">
                <div style="background-color: #f7f7f7; text-align: center; padding: 20px;">
                    {if isset($e.author.2)}<a href="{$e.author.2}">{/if}<img src="{$e.author.0}" title="{$e.author.1|htmlentities}" alt="{$e.author.1|htmlentities}" style="border-radius: 50%;">{if isset($e.author.2)}</a>{/if}<br /><br />
                    {if isset($e.author.2)}<a href="{$e.author.2}">{/if}<b>{$e.author.1|htmlentities}</b>{if isset($e.author.2)}</a>{/if}{if !empty($e.author.4)} <span class="label label-primary">{$l.MOD}</span>{/if}
                    {if isset($e.author.3)}<br />{$l.ENTRIES}: {$e.author.3}{/if}
                </div><br />
            </div>

            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <small>{dfo d=$e.time s=1}{if $f.mod}<span class="pull-right"><a href="#" class="edit-post" data-id="{$e.ID}"><i class="fa fa-pencil"></i></a> &nbsp; <a href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/delpost/{$e.ID}" onclick="return confirm('{$l.SURE}');"><i class="fa fa-trash-o"></i></a></span>{/if}</small><br /><br />

                        <div id="post-{$e.ID}">{$e.text|htmlentities|nl2br}</div>
                        {if $f.mod}
                        <form method="POST" id="edit-{$e.ID}" style="display: none;">
                            <textarea name="post" class="form-control" style="margin-bottom: 10px; resize: vertical; width: 100%; height: 250px;">{$e.text|htmlentities}</textarea>
                            <input type="hidden" name="edit" value="{$e.ID}">
                            <input type="submit" class="btn btn-primary btn-block" value="{$l.EDIT}">
                        </form>
                        {/if}
                    </div>
                </div><br />
            </div>
        </div>
        {/foreach}

        <script>
        $(".edit-post").click(function(e) {
            e.preventDefault();
            var id = $(this).hide().data("id");
            $("#post-" + id).hide();
            $("#edit-" + id).show();
        });
        </script>

        <div class="card">
            <div class="card-body">
            <h5 class="card-title">{$l.ANSWER}</h5>
                {if !$logged_in}
                <div class="alert alert-warning" style="margin-bottom: 0;">{$l.NTLI}</div>
                {elseif $t.lock && !$f.mod}
                <div class="alert alert-warning" style="margin-bottom: 0;">{$l.TIL}</div>
                {elseif empty($user.nickname)}
                <div class="alert alert-warning" style="margin-bottom: 0;"><a href="{$cfg.PAGEURL}profile" target="_blank">{$l.REQNICK}</a></div>
                {else}
                {if !empty($error)}<div class="alert alert-danger">{$error}</div>{/if}

                <form method="POST">
                    <textarea name="answer" class="form-control" style="width: 100%; height: 250px; margin-bottom: 10px; resize: vertical;">{if $smarty.post.answer}{$smarty.post.answer|htmlentities}{/if}</textarea>
                    <input type="submit" class="btn btn-primary btn-block" value="{$l.ANSWER}">
                </form>
                {/if}
            </div>
        </div><br />

        <form method="POST" id="switch_form" class="form-inline">
            <select name="forum" class="form-control">
                {foreach from=$forums item=mf}
                <option value="{$mf.ID}"{if $mf.ID == $f.ID} selected=""{/if}>{$mf.name|htmlentities}</option>
                {/foreach}
            </select>&nbsp;
            <input type="hidden" name="action" value="go">&nbsp;
            <input type="submit" class="btn btn-default" value="{$l.GOTO}">&nbsp;
            {if $f.mod}<input type="button" onclick="$('#switch_form').find('[name=action]').val('move'); $('#switch_form').submit();" class="btn btn-default" value="{$l.MOVE}">{/if}
        </form><br /><br />

        
            <div class="page-nation" style="margin-top: -25px;">
                <ul class="pagination" style="margin-bottom: -10px;">
                    {if ($cPage - 1) <= 0}
                    <li class="page-item disabled"><a class="page-link">&laquo;</a></li>
                    {else}
                    <li class="page-item"><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/p/{$cPage - 1}">&laquo;</a></li>
                    {/if}
                    {if ($cPage - 3) > 1}
                    <li class="page-item"><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/p/1">1</a></li>
                    {if ($cPage - 3) > 2}
                    <li class="page-item disabled"><a class="page-link">...</a></li>
                    {/if}
                    {/if}
                    {for $i=($cPage - 3) to ($cPage - 1)}
                    {if $i > 0 && $i <= $pages}
                    <li class="page-item"><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/p/{$i}">{$i}</a></li>
                    {/if}
                    {/for}
                    <li class="active"><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/p/{$cPage}">{$cPage}</a></li>
                    {for $i=($cPage + 1) to ($cPage + 3)}
                    {if $i > 0 && $i <= $pages}
                    <li class="page-item"><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/p/{$i}">{$i}</a></li>
                    {/if}
                    {/for}
                    {if ($cPage + 4) < $pages}
                    <li class="page-item disabled"><a class="page-link">...</a></li>
                    {/if}
                    {if ($cPage + 3) < $pages}
                    <li class="page-item"><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/p/{$pages}">{$pages}</a></li>
                    {/if}
                    {if ($cPage + 1) > $pages}
                    <li class="page-item disabled"><a class="page-link">&raquo;</a></li>
                    {else}
                    <li class="page-item"><a class="page-link" href="{$cfg.PAGEURL}forum/{$f.ID}/{$t.ID}/p/{$cPage + 1}">&raquo;</a></li>
                    {/if}
                </ul>
            </div>
        {elseif $step == "search"}
        <span style="font-size: 13px;">{$l.YAH}: <a href="{$cfg.PAGEURL}forum">{$l.OVERVIEW}</a> &raquo; {$l.SEARCHYAH}</span><br /><br />

        <form method="POST">
            <div class="input-group">
                <input type="text" class="form-control input-lg" placeholder="{$l.SEARCHWORD}" value="{$smarty.post.searchword|htmlentities}" name="searchword">
                <span class="input-group-btn">
                    <input type="submit" class="btn btn-primary btn-block btn-lg" style="height: 46px;" value="{$l.SEARCH}">
                </span>
            </div>
        </form><br />

        <div style="margin-bottom: -30px;">
            {foreach from=$threads item=t}
            <br /><a href="{$cfg.PAGEURL}forum/{$t.forum}/{$t.ID}"><i class="fa fa-align-left fa-3x" style="float: left; margin-right: 20px;"></i></a>
            <div class="float: left;"><b><a href="{$cfg.PAGEURL}forum/{$t.forum}/{$t.ID}">{$t.title|htmlentities}</a></b><br />{$forums.{$t.forum}.name|htmlentities}</div>
            <br />
            {foreachelse}
            {$l.NOSRES}<br /><br /><br />
            {/foreach}
        </div>
        {/if}
    </div>
</div>