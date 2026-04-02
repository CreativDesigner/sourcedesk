<div class="row">
	<div class="col-lg-12">
		{if $step == "overview"}
        <h1 class="page-header">{$l.TITLE}<span class="pull-right"><a href="?p=knowledgebase&add_cat=1"><i class="fa fa-plus-circle"></i></a></span></h1>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <tr>
                    <th width="47px"></th>
                    <th width="60%">{$l.CAT}</th>
                    <th><center>{$l.QUESTIONS}</center></th>
                    <th><center>{$l.PUBLIC}</center></th>
                </tr>

                {foreach from=$cats item=cat}
                <tr>
                    <td><img src="{$cfg.PAGEURL}languages/icons/{$cat->language}.png" alt="{$cat->language}" title="{$cat->language}"></td>
                    <td><a href="?p=knowledgebase&cat={$cat->ID}">{$cat->name|htmlentities}</a></td>
                    <td><center>{$cat->getQuestions()|count}</center></td>
                    <td><center>{if $cat->status}{$lang.DOMAINS.YES}{else}{$lang.DOMAINS.NO}{/if}</center></td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="4"><center>{$l.NOCATS}</center></td>
                </tr>
                {/foreach}
            </table>
        </div>
        {elseif $step == "add_cat"}
        <h1 class="page-header">{$l.TITLE} <small>{$l.ADDCAT}</small></h1>

        <form method="POST">
            <div class="form-group">
                <label>{$l.NAME}</label>
                <input type="text" name="name" value="{if isset($smarty.post.name)}{$smarty.post.name|htmlentities}{/if}" class="form-control" placeholder="{$l.NAMEP}">
            </div>

            <div class="form-group">
                <label>{$l.LANGUAGE}</label>
                <select name="language" class="form-control">
                    {foreach from=$languages key=k item=n}
                    <option value="{$k}"{if isset($smarty.post.language) && $smarty.post.language == $k} selected=""{/if}>{$n}</option>
                    {/foreach}
                </select>
            </div>

            <div class="form-group">
                <label>{$l.ORDER}</label>
                <input type="number" name="order" class="form-control" placeholder="0" value="{if isset($smarty.post.order)}{$smarty.post.order|intval}{else}0{/if}" style="max-width: 100px;">
            </div>

            <div class="form-group">
                <label>{$l.PUBLIC}</label><br />
                <label class="radio-inline">
                    <input type="radio" name="public" value="1"{if !isset($smarty.post.public) || !empty($smarty.post.public)} checked=""{/if}> {$lang.DOMAINS.YES}
                </label>
                <label class="radio-inline">
                    <input type="radio" name="public" value="0"{if isset($smarty.post.public) && empty($smarty.post.public)} checked=""{/if}> {$lang.DOMAINS.NO}
                </label>
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$l.ADDCAT}">
        </form>
        {elseif $step == "add_question"}
        <h1 class="page-header">{$l.TITLE} <small>{$l.ADDQ}</small></h1>

        <form method="POST">
            <div class="form-group">
                <label>{$l.TITLE2}</label>
                <input type="text" name="title" value="{if isset($smarty.post.title)}{$smarty.post.title|htmlentities}{/if}" class="form-control" placeholder="{$l.TITLEP}">
            </div>

            <div class="form-group">
                <label>{$l.ARTICLE}</label>
                <textarea name="article" class="form-control" style="resize: vertical; width: 100%; height: 200px;">{if isset($smarty.post.article)}{$smarty.post.article}{/if}</textarea>
            </div>

            <div class="form-group">
                <label>{$l.ORDER}</label>
                <input type="number" name="order" class="form-control" placeholder="0" value="{if isset($smarty.post.order)}{$smarty.post.order|intval}{else}0{/if}" style="max-width: 100px;">
            </div>

            <div class="form-group">
                <label>{$l.PUBLIC}</label><br />
                <label class="radio-inline">
                    <input type="radio" name="public" value="1"{if !isset($smarty.post.public) || !empty($smarty.post.public)} checked=""{/if}> {$lang.DOMAINS.YES}
                </label>
                <label class="radio-inline">
                    <input type="radio" name="public" value="0"{if isset($smarty.post.public) && empty($smarty.post.public)} checked=""{/if}> {$lang.DOMAINS.NO}
                </label>
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$l.ADDQ}">
        </form>
        {elseif $step == "question"}
        <h1 class="page-header">{$l.TITLE} <small>{$q->title|htmlentities}</small><span class="pull-right"><a href="?p=knowledgebase&cat={$q->category}&del_art={$q->ID}"><i class="fa fa-trash-o"></i></a></span></h1>

        <form method="POST">
            <div class="form-group">
                <label>{$l.TITLE2}</label>
                <input type="text" name="title" value="{if isset($smarty.post.title)}{$smarty.post.title|htmlentities}{else}{$q->title|htmlentities}{/if}" class="form-control" placeholder="{$l.TITLEP}">
            </div>

            <div class="form-group">
                <label>{$l.ARTICLE}</label>
                <textarea name="article" class="form-control" style="resize: vertical; width: 100%; height: 200px;">{if isset($smarty.post.article)}{$smarty.post.article}{else}{$q->article|htmlentities}{/if}</textarea>
            </div>

            <div class="form-group">
                <label>{$l.ORDER}</label>
                <input type="number" name="order" class="form-control" placeholder="0" value="{if isset($smarty.post.order)}{$smarty.post.order|intval}{else}{$q->order|intval}{/if}" style="max-width: 100px;">
            </div>

            <div class="form-group">
                <label>{$l.PUBLIC}</label><br />
                <label class="radio-inline">
                    <input type="radio" name="public" value="1"{if (isset($smarty.post.public) && !empty($smarty.post.public)) || (!isset($smarty.post.public) && $q->status)} checked=""{/if}> {$lang.DOMAINS.YES}
                </label>
                <label class="radio-inline">
                    <input type="radio" name="public" value="0"{if (isset($smarty.post.public) && empty($smarty.post.public)) || (!isset($smarty.post.public) && !$q->status)} checked=""{/if}> {$lang.DOMAINS.NO}
                </label>
            </div>

            <div class="form-group">
                <label>{$l.VIEWS}</label><br />
                {$q->views}
            </div>

            <div class="form-group">
                <label>{$l.RATINGS}</label><br />
                {$q->ratings}{if $q->ratings} ({$q->getSatisfaction()} {$l.POSITIVE}){/if}
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$l.SAVEQ}">
        </form>
        {elseif $step == "cat"}
        <h1 class="page-header">{$l.TITLE} <small>{$cat->name|htmlentities} <img src="{$cfg.PAGEURL}languages/icons/{$cat->language}.png" alt="{$cat->language}" title="{$cat->language}"></small><span class="pull-right"><a href="?p=knowledgebase&add_question={$cat->ID}"><i class="fa fa-plus-circle"></i></a></span></h1>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <tr>
                    <th width="60%">{$l.QUESTION}</th>
                    <th><center>{$l.VIEWS}</center></th>
                    <th><center>{$l.RATINGS}</center></th>
                    <th><center>{$l.SATISFACTION}</center></th>
                    <th><center>{$l.PUBLIC}</center></th>
                </tr>

                {foreach from=$cat->getQuestions() item=q}
                <tr>
                    <td><a href="?p=knowledgebase&question={$q->ID}">{$q->title|htmlentities}</a></td>
                    <td><center>{$q->views}</center></td>
                    <td><center>{$q->ratings}</center></td>
                    <td><center>{$q->getSatisfaction()}</center></td>
                    <td><center>{if $q->status}{$lang.DOMAINS.YES}{else}{$lang.DOMAINS.NO}{/if}</center></td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="5"><center>{$l.NOQUESTIONS}</center></td>
                </tr>
                {/foreach}
            </table>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">{$l.CAT} <span class="pull-right">{if $cat->getQuestions()|@count}<i class="fa fa-trash-o" data-toggle="tooltip" title="{$l.DNP}"></i>{else}<a href="?p=knowledgebase&del_cat={$cat->ID}"><i class="fa fa-trash-o"></i></a>{/if}</div>
            <div class="panel-body">
                <form method="POST">
                    <div class="form-group">
                        <label>{$l.NAME}</label>
                        <input type="text" name="name" value="{if isset($smarty.post.name)}{$smarty.post.name|htmlentities}{else}{$cat->name|htmlentities}{/if}" class="form-control" placeholder="{$l.NAMEP}">
                    </div>

                    <div class="form-group">
                        <label>{$l.LANGUAGE}</label>
                        <select name="language" class="form-control">
                            {foreach from=$languages key=k item=n}
                            <option value="{$k}"{if (isset($smarty.post.language) && $smarty.post.language == $k) || (!isset($smarty.post.language) && $cat->language == $k)} selected=""{/if}>{$n}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="form-group">
                        <label>{$l.ORDER}</label>
                        <input type="number" name="order" class="form-control" placeholder="0" value="{if isset($smarty.post.order)}{$smarty.post.order|intval}{else}{$cat->order|intval}{/if}" style="max-width: 100px;">
                    </div>

                    <div class="form-group">
                        <label>{$l.PUBLIC}</label><br />
                        <label class="radio-inline">
                            <input type="radio" name="public" value="1"{if (isset($smarty.post.public) && !empty($smarty.post.public)) || (!isset($smarty.post.public) && $cat->status)} checked=""{/if}> {$lang.DOMAINS.YES}
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="public" value="0"{if (isset($smarty.post.public) && empty($smarty.post.public)) || (!isset($smarty.post.public) && !$cat->status)} checked=""{/if}> {$lang.DOMAINS.NO}
                        </label>
                    </div>

                    <input type="submit" class="btn btn-primary btn-block" value="{$l.EDITCAT}">
                </form>
            </div>
        </div>
        {/if}
	</div>
</div>