<div id="content">
    <div class="container">
        {if $view == "home"}
        <h1><small><a href="{$cfg.PAGEURL}blog/rss"><i class="fa fa-rss fa-sm"></i></a></small> {$lang.BLOG.TITLE}{if $page != 1} <small>{$lang.BLOG.ARCHIVE}</small>{/if}</h1><hr />

        {foreach from=$entries item=e key=i}
        {if $i != 0}<br /><br /><br />
        {/if}<h2><a class="blog-entry" href="{$cfg.PAGEURL}blog/{$e.ID}">{$e.title}</a></h2>
        <div class="blog-meta">{$e.time}{if !empty($e.admin)} &nbsp; / &nbsp; {$e.admin}{/if}{if !empty($cfg.DISQUS)}<span class="blog-comments"><i class="fa fa-comments"></i> <a href="{$cfg.PAGEURL}blog/{$e.ID}#disqus_thread">{$lang.BLOG.COMMENTS}</a></span>{/if}</div>

        <p class="blog-text">{$e.text}</p>

        <a class="text-primary" href="{$cfg.PAGEURL}blog/{$e.ID}">{$lang.BLOG.MORE} <i class="fa fa-long-arrow-right"></i></a>
        {foreachelse}
        <div class="alert alert-info">{$lang.BLOG.NOTHING}</div>
        {/foreach}
        {if !empty($cfg.DISQUS)}<script id="dsq-count-scr" src="//{$cfg.DISQUS}.disqus.com/count.js" async></script>{/if}

        <nav>
          <ul class="pager">
            <li class="previous{if $pages <= $page} disabled{/if}"><a href="{$cfg.PAGEURL}blog/page/{$page+1}"><span aria-hidden="true">&larr;</span> {$lang.BLOG.PREV}</a></li>
            <li class="next{if $page == 1} disabled{/if}"><a href="{$cfg.PAGEURL}blog/page/{$page-1}">{$lang.BLOG.NEXT} <span aria-hidden="true">&rarr;</span></a></li>
          </ul>
        </nav>
        {else if $view == "post"}
        <h1>{$e.title}</h1><hr />
        <div class="blog-meta">{$e.time}{if !empty($e.admin)} &nbsp; / &nbsp; {$e.admin}{/if}{if !empty($cfg.DISQUS)}<span class="blog-comments"><i class="fa fa-comments"></i> <a href="{$cfg.PAGEURL}blog/{$e.ID}#disqus_thread">{$lang.BLOG.COMMENTS}</a></span>{/if}</div>
        {if !empty($cfg.DISQUS)}<script id="dsq-count-scr" src="//{$cfg.DISQUS}.disqus.com/count.js" async></script>{/if}

        <p style="text-align: justify;">{$e.text|nl2br}</p>

        {if !empty($previous) || !empty($next)}<nav>
          <ul class="pager">
            {if !empty($previous)}<li class="previous"><a href="{$cfg.PAGEURL}blog/{$previous.id}"><span aria-hidden="true">&larr;</span> {$previous.title}</a></li>{/if}
            {if !empty($next)}<li class="next"><a href="{$cfg.PAGEURL}blog/{$next.id}">{$next.title} <span aria-hidden="true">&rarr;</span></a></li>{/if}
          </ul>
        </nav>{/if}

        {if !empty($cfg.DISQUS)}<a name="disqus_thread"></a><div id="disqus_thread"></div>
        <script>
            var disqus_config = function () {
                this.page.identifier = 'blog-{$lang_active}-{$e.ID}';
                this.page.title = '{$e.title}';
            };
            (function() {
                var d = document, s = d.createElement('script');
                
                s.src = '//{$cfg.DISQUS}.disqus.com/embed.js';
                
                s.setAttribute('data-timestamp', +new Date());
                (d.head || d.body).appendChild(s);
            })();
        </script>{/if}

        <br />
        {/if}
    </div>
</div>