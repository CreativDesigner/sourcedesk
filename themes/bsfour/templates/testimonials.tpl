<div id="content">
  <div class="container">
      <h1>{if !isset($add)}{$lang.TESTIMONIALS.TITLE} <small><a href="{$cfg.PAGEURL}testimonials/add">{$lang.TESTIMONIALS.RATE}</a></small>{else}{$lang.TESTIMONIALS.RATING} <small><a href="{$cfg.PAGEURL}testimonials">{$lang.TESTIMONIALS.ALL}</a></small>{/if}</h1><hr>

      {if isset($add)}
      <p style="text-align: justify;">{$lang.TESTIMONIALS.INTRO}</p>

      {if $logged_in}
        {if isset($error)}<div class="alert alert-danger">{$error}</div>{/if}

        <form role="form" method="POST">
          <div class="form-group">
            <label>{$lang.TESTIMONIALS.NAME}</label>
            <input class="form-control" type="text" value="{$uname|htmlentities}" readonly="readonly" />
          </div>

          <div class="form-group">
            <label>{$lang.TESTIMONIALS.STARS}</label><br />

            <style>
            .starrr {
              display: inline-block; 
            }
            
            .starrr a {
              font-size: 16px;
              padding: 0 1px;
              cursor: pointer;
              color: #FFD119;
              text-decoration: none;
            }
            </style>

            <div id="stars" class="starrr"></div>
            <input type="hidden" name="rating" value="{if isset($smarty.post.rating) && is_numeric($smarty.post.rating) && $smarty.post.rating >= 1 && $smarty.post.rating <= 5}{$smarty.post.rating|round}{else}5{/if}">

            <script>
            $(document).ready(function() {
              var slice = [].slice;

              (function($, window) {
                var Starrr;
                window.Starrr = Starrr = (function() {
                  Starrr.prototype.defaults = {
                    rating: void 0,
                    max: 5,
                    readOnly: false,
                    emptyClass: 'fa fa-star-o',
                    fullClass: 'fa fa-star',
                    change: function(e, value) {}
                  };

                  function Starrr($el, options) {
                    this.options = $.extend({}, this.defaults, options);
                    this.$el = $el;
                    this.createStars();
                    this.syncRating();
                    if (this.options.readOnly) {
                      return;
                    }
                    this.$el.on('mouseover.starrr', 'a', (function(_this) {
                      return function(e) {
                        return _this.syncRating(_this.getStars().index(e.currentTarget) + 1);
                      };
                    })(this));
                    this.$el.on('mouseout.starrr', (function(_this) {
                      return function() {
                        return _this.syncRating();
                      };
                    })(this));
                    this.$el.on('click.starrr', 'a', (function(_this) {
                      return function(e) {
                        e.preventDefault();
                        return _this.setRating(_this.getStars().index(e.currentTarget) + 1);
                      };
                    })(this));
                    this.$el.on('starrr:change', this.options.change);
                  }

                  Starrr.prototype.getStars = function() {
                    return this.$el.find('a');
                  };

                  Starrr.prototype.createStars = function() {
                    var j, ref, results;
                    results = [];
                    for (j = 1, ref = this.options.max; 1 <= ref ? j <= ref : j >= ref; 1 <= ref ? j++ : j--) {
                      results.push(this.$el.append("<a href='#' />"));
                    }
                    return results;
                  };

                  Starrr.prototype.setRating = function(rating) {
                    if (this.options.rating === rating) {
                      rating = void 0;
                    }
                    this.options.rating = rating;
                    this.syncRating();
                    return this.$el.trigger('starrr:change', rating);
                  };

                  Starrr.prototype.getRating = function() {
                    return this.options.rating;
                  };

                  Starrr.prototype.syncRating = function(rating) {
                    var $stars, i, j, ref, results;
                    rating || (rating = this.options.rating);
                    $stars = this.getStars();
                    results = [];
                    for (i = j = 1, ref = this.options.max; 1 <= ref ? j <= ref : j >= ref; i = 1 <= ref ? ++j : --j) {
                      results.push($stars.eq(i - 1).removeClass(rating >= i ? this.options.emptyClass : this.options.fullClass).addClass(rating >= i ? this.options.fullClass : this.options.emptyClass));
                    }
                    return results;
                  };

                  return Starrr;

                })();
                return $.fn.extend({
                  starrr: function() {
                    var args, option;
                    option = arguments[0], args = 2 <= arguments.length ? slice.call(arguments, 1) : [];
                    return this.each(function() {
                      var data;
                      data = $(this).data('starrr');
                      if (!data) {
                        $(this).data('starrr', (data = new Starrr($(this), option)));
                      }
                      if (typeof option === 'string') {
                        return data[option].apply(data, args);
                      }
                    });
                  }
                });
              })(window.jQuery, window);

              $('.starrr').starrr({
                rating: {if isset($smarty.post.rating) && is_numeric($smarty.post.rating) && $smarty.post.rating >= 1 && $smarty.post.rating <= 5}{$smarty.post.rating|round}{else}5{/if},
                change: function(e, value){
                  $("[name=rating]").val(value);
                }
              });
            });
            </script>
          </div>

          <div class="form-group">
            <label>{$lang.TESTIMONIALS.TITLE_FORM}</label>
            <input class="form-control" name="subject" type="text" value="{if isset($smarty.post.subject)}{$smarty.post.subject|htmlentities}{/if}" placeholder="{$lang.TESTIMONIALS.TITLE_PH}" maxlength="35" />
          </div>

          <div class="form-group">
            <label>{$lang.TESTIMONIALS.TEXT}</label>
            <textarea name="text" class="form-control" minlength="80" placeholder="{$lang.TESTIMONIALS.TEXT_PH}" style="width: 100%; height: 200px; resize: none;">{if isset($smarty.post.text)}{$smarty.post.text|htmlentities}{/if}</textarea>
          </div>

          <div class="checkbox">
            <label>
              <input type="checkbox" value="ok" name="agreement"{if isset($smarty.post.agreement) && $smarty.post.agreement == "ok"} checked="checked"{/if}>
              {$lang.TESTIMONIALS.OK|replace:"%p":$cfg.PAGENAME}
            </label>
          </div>

          <button name="submit" type="submit" class="btn btn-primary btn-block">{$lang.TESTIMONIALS.ADD}</button>
        </form>
      {else}
        <div class="alert alert-warning">{$lang.TESTIMONIALS.LOGIN}</div>
      {/if}
      {else}{if $testimonials|@count > 0}<div class="row" style="margin-bottom: 20px;">
        <div class="col-sm-6">
          <a class="btn btn-{if $order == "time"}primary{else}default{/if}" href="{$cfg.PAGEURL}testimonials/{$page}/time">{$lang.TESTIMONIALS.TIME}</a>
          <a class="btn btn-{if $order == "rating"}primary{else}default{/if}" href="{$cfg.PAGEURL}testimonials/{$page}/rating">{$lang.TESTIMONIALS.SS}</a>
        </div>
        {if $pages > 1}<div class="col-sm-6">
          <nav>
            <ul class="pagination" style="margin-top: 5px; margin-bottom: 0; float: right;">
              <li>
                <a href="{$cfg.PAGEURL}testimonials/{max(1, $page - 1)}/{$order}" aria-label="{$lang.GENERAL.PREVIOUS}">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
              {for $i=1 to $pages}
              <li{if $i == $page} class="active"{/if}><a href="{$cfg.PAGEURL}testimonials/{$i}/{$order}">{$i}</a></li>
              {/for}
              <li>
                <a href="{$cfg.PAGEURL}testimonials/{min($pages, $page + 1)}/{$order}" aria-label="{$lang.GENERAL.NEXT}">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            </ul>
          </nav>
        </div>{/if}
      </div>{/if}

      {if $added}<div class="alert alert-success">{$lang.TESTIMONIALS.DONE}</div>{else if $testimonials|@count > 0}<div class="alert alert-info">{$lang.TESTIMONIALS.AVERAGE}: {for $i=1 to 5}{if $average >= $i}<i class="fa fa-star"></i>{/if}{/for}{if $half}{assign var="average" value=$average+1}<i class="fa fa-star-half-o"></i>{/if}{for $i=$average to 4}<i class="fa fa-star-o"></i>{/for}</div>{/if}

      {foreach from=$testimonials key=id item=testimonial}
      {if $id % 2 == 0}<div class="row" style="margin-bottom: 15px;">{assign "row_open" "1"}{/if}
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">{$testimonial->getSubject()|htmlentities} {assign var="rating" value=$testimonial->getRating()}{for $i=1 to 5}{if $rating >= $i}<i class="fa fa-star"></i>{/if}{/for}{for $i=$rating to 4}<i class="fa fa-star-o"></i>{/for} <small{if isset($user.ID) && $testimonial->getAuthor(0) == $user.ID} style="color: white;"{/if}>{dfo d=$testimonial->getTimestamp() m=0}</small></h5>
              {$testimonial->getText()|htmlentities|nl2br}<br />
              <small style="float: right;">~ {$testimonial->getAuthor()|htmlentities}</small>
            </div>
          </div>
        </div>
      {if $id % 2 == 1}</div>{assign "row_open" "0"}{/if}
      {foreachelse}
      <div class="jumbotron">
        <h1>{$lang.TESTIMONIALS.NOTHING}</h1>
        <p>{$lang.TESTIMONIALS.NOTHING_INTRO}</p>
        <p><a class="btn btn-primary btn-lg" href="{$cfg.PAGEURL}testimonials/add" role="button">{$lang.TESTIMONIALS.NOTHING_ACTION|replace:"%p":$cfg.PAGENAME}</a></p>
      </div>
      {/foreach}
      {if isset($row_open) && $row_open}</div>{/if}

      {if $testimonials|@count > 0}<div class="row">
        <div class="col-sm-6">
          <a class="btn btn-{if $order == "time"}primary{else}default{/if}" href="{$cfg.PAGEURL}testimonials/{$page}/time">{$lang.TESTIMONIALS.TIME}</a>
          <a class="btn btn-{if $order == "rating"}primary{else}default{/if}" href="{$cfg.PAGEURL}testimonials/{$page}/rating">{$lang.TESTIMONIALS.SS}</a>
        </div>
        {if $pages > 1}<div class="col-sm-6">
          <nav>
            <ul class="pagination" style="margin-top: 5px; margin-bottom: 0; float: right;">
              <li>
                <a href="{$cfg.PAGEURL}testimonials/{max(1, $page - 1)}/{$order}" aria-label="{$lang.GENERAL.PREVIOUS}">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
              {for $i=1 to $pages}
              <li{if $i == $page} class="active"{/if}><a href="{$cfg.PAGEURL}testimonials/{$i}/{$order}">{$i}</a></li>
              {/for}
              <li>
                <a href="{$cfg.PAGEURL}testimonials/{min($pages, $page + 1)}/{$order}" aria-label="{$lang.GENERAL.NEXT}">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            </ul>
          </nav>
        </div>{/if}
      </div>{/if}{/if}
  </div>
</div><br />