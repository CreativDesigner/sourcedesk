<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.USERMAP.TITLE}</h1>

		{if isset($msg)}{$msg}{/if}

		<p style="text-align: justify;">{$lang.USERMAP.INTRO}</p>

		<center><div id="map" style="width: 100%; height: 600px"></div></center><br />
		<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key={$cfg.GMAP_KEY}"></script>
		<script type="text/javascript">
		function addMarker (content, position, title) {ldelim}
			  var infowindow = new google.maps.InfoWindow({ldelim}
			      content: content
			  {rdelim});

			  var marker = new google.maps.Marker({ldelim}
			      position: position,
			      map: map,
			      title: title
			  {rdelim});
			  google.maps.event.addListener(marker, 'click', function() {ldelim}
			    infowindow.open(map, marker);
			  {rdelim});
		{rdelim}

		function initialize() {ldelim}
	        var mapOptions = {ldelim}
			    zoom: 6,
			    center: new google.maps.LatLng(51.163375, 10.477683)
			  {rdelim};
			map = new google.maps.Map(document.getElementById('map'), mapOptions);
			
			{foreach from=$customers item=info key=id}
			addMarker('<div id="content">'+
			      '<div id="siteNotice">'+
			      '</div>'+
			      '<h1 id="firstHeading" class="firstHeading">{$info.name|htmlentities|replace:"'":"\\'"}</h1>'+
			      '<div id="bodyContent">'+
			      '{if !empty($info.cname)}<b>{$info.cname|htmlentities|replace:"'":"\\'"}</b>{/if}<p>{$info.address|htmlentities|replace:"'":"\\'"|replace:"###br###":"<br />"}</p>'+
			      '<p><a href="./?p=customers&edit={$id}">'+
			      '{$lang.USERMAP.PROFILE}</a></p>'+
			      '</div>'+
			      '</div>', new google.maps.LatLng({$info.lat}, {$info.lng}), '{$info.name|replace:"'":"\\'"}{if !empty($info.cname)} ({$info.cname|htmlentities|replace:"'":"\\'"}){/if}');
			{/foreach}
		{rdelim}

		google.maps.event.addDomListener(window, "load", initialize);
	    </script>
	</div>
</div>