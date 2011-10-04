<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">// <![CDATA[
	var marker;
	function google_map_users_initialize()
	{
		marker = new google.maps.Marker();
		var bounds = new google.maps.LatLngBounds();
		var center = new google.maps.LatLng({$?,{$IS_EMPTY,{LATITUDE}},0,{LATITUDE;}},{$?,{$IS_EMPTY,{LONGITUDE}},0,{LONGITUDE;}});
		var map = new google.maps.Map(document.getElementById('map_position_{NAME;}'),
		{
			zoom: {$?,{$IS_NON_EMPTY,{LATITUDE}},12,1},
			center: center,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			overviewMapControl: true,
			overviewMapControlOptions:
			{
				opened: true
			},
		});
		
		var infoWindow = new google.maps.InfoWindow();

		{$,Close InfoWindow when clicking anywhere on the map.}
		google.maps.event.addListener(map, 'click', function ()
		{
			infoWindow.close();
		});

		{$,Show marker for current position}
		{+START,IF,{$AND,{$IS_NON_EMPTY,{LATITUDE}},{REQUIRED}}}
			place_marker({LATITUDE;},{LONGITUDE;});
			marker.setMap(map);
		{+END}

		{$,Save into hidden fields}
		var lastPoint;
		google.maps.event.addListener(map, "mousemove", function(point) {
			lastPoint = point.latLng;
		});
		google.maps.event.addListener(map, "click", function() {
			document.getElementById('{NAME;}_latitude').value=lastPoint.lat();
			document.getElementById('{NAME;}_longitude').value=lastPoint.lng();
			place_marker(lastPoint.lat(),lastPoint.lng());
			marker.setMap(map);
		});
	}
	
	function place_marker(latitude,longitude)
	{
		var latLng = new google.maps.LatLng(latitude,longitude);
		marker.setPosition(latLng);
	}
	
	google.load("maps", "3",  {callback: google_map_users_initialize, other_params:"sensor=false"});
//]]></script>

<div id="map_position_{NAME*}" style="width:100%; height:300px"></div>

<div style="display:none">
	<input type="text" {+START,IF,{REQUIRED}}class="hidden_required" {+END}id="{NAME*}_latitude" name="{NAME*}_latitude" value="{LATITUDE*}" />
	<input type="text" {+START,IF,{REQUIRED}}class="hidden_required" {+END}id="{NAME*}_longitude" name="{NAME*}_longitude" value="{LONGITUDE*}" />
</div>
