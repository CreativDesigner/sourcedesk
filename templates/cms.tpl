{if $container}<div id="content">
	<div class="container">
			<h1>{$title}</h1><hr>{/if}{eval var=$content}{if $container}
	</div>
</div>{/if}