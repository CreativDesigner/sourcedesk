<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.TESTIMONIALS.TITLE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

{if isset($msg)}<div class="alert alert-success">{$msg}</div>{/if}

{$th}

<div class="table-responsive">
	<table class="table table-bordered table-striped">
		<tr>
			<th>{$table_order.0}</th>
			<th>{$table_order.1}</th>
			<th>{$table_order.2}</th>
			<th>{$table_order.3}</th>
			<th width="{if $active == 0}47{else}29{/if}px"></th>
		</tr>

		{foreach from=$testimonials item=testimonial}
			<tr>
				<td>{dfo d=$testimonial->getTimestamp() m=0}</td>
				<td><a href="?p=customers&edit={$testimonial->getAuthor(false)}">{$testimonial->getAuthor()|htmlentities}</a></td>
				<td>{assign "missing" 5}{assign "rating" $testimonial->getRating()}{while $rating >= 1}<i class="fa fa-star"></i>{assign "rating" $rating-1}{assign "missing" $missing-1}{/while}{while $missing > 0}<i class="fa fa-star-o"></i>{assign "missing" $missing-1}{/while}</td>
				<td><a href="#" data-toggle="modal" data-target="#testimonial" onclick="loadTestimonial({$testimonial->getId()});">{$testimonial->getSubject()|htmlentities}</a></td>
				<td>{if $testimonial->isActive() == 0}<a href="?p=testimonials&publish={$testimonial->getId()}"><i class="fa fa-check"></i></a>{else}<a href="#" data-toggle="modal" data-target="#testimonial_answer" onclick="loadAnswer({$testimonial->getId()});"><i class="fa fa-comment{if !$testimonial->getAnswer()}-o{/if}"></i></a>{/if} <a href="?p=testimonials&active={$active}&delete={$testimonial->getId()}"><i class="fa fa-times"></i></a></td>
			</tr>
		{foreachelse}
		<tr>
			<td colspan="5"><center>
				{if $active == 0}{$lang.TESTIMONIALS.NOTHING_WAITING}
				{else}{$lang.TESTIMONIALS.NOTHING_PUBLISHED}{/if}
			</center></td>
		</tr>
		{/foreach}
	</table>
</div>

{$tf}

<div class="modal fade" id="testimonial" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="testimonialLabel">{$lang.TESTIMONIALS.WAIT}</h4>
      </div>
      <div class="modal-body" id="testimonialContent">
        {$lang.TESTIMONIALS.WAIT2}
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="testimonial_answer" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="{$lang.GENERAL.CLOSE}"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="testimonialLabel">{$lang.TESTIMONIALS.ANSWER}</h4>
      </div>
      <div class="modal-body" id="testimonialAnswer">
        {$lang.TESTIMONIALS.WAIT2}
      </div>
    </div>
  </div>
</div>