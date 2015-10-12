(function ( $ ) {

	$(function () {

	    // Location Model
		var Location = Backbone.Model.extend();

		// Locations Collection
		var Locations = Backbone.Collection.extend({
			model: Location,
			url: ajax_object.ajax_url+'?action=get_locations',
			initialize: function () {
				this.fetch();
			},
			comparator: function(model) {
				return model.get('title');
			}
		});

		// Map View
		var MapView = Backbone.View.extend({
			el: $('body'),
			cat: $('.terms li').first().data('id'),
			initialize: function() {
				var mapOptions = {
					zoom: 12,
					center: new google.maps.LatLng(32.328187, -111.218671)
				};

				this.locations = new Locations();
				this.userLat = docCookies.getItem('lat');
				this.userLng = docCookies.getItem('lng');

				if(this.userLat === null) {
					this.html5location();
				} else {

					this.getLocationsWithDistance();
				}

				this.listenToOnce(this.locations, 'sync', this.addMarkers);
				this.listenTo(this.locations, 'sync', this.render);

			 	this.map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
			},

			events: {
				"submit #map_search":    "searchLocation",
				"click .map-item": "goToLocation",
				"click .terms li": "showCatList"
			},

			template: '<% _.each(models,function(item,key,arr) { %><li data-cat="<%= item.attributes.category %>" data-id="<%= item.attributes.id %>" class="map-item"> <% if (item.attributes.distance !== "0.00") { %> <span class="distance"> Distance: <%= item.attributes.distance %> mi </span> <% } %>   <span class="title"><%= item.attributes.title %></span><span class="address"><%= item.attributes.sidebar_address %></span><span class="phone">Phone: <%= item.attributes.phone %></span> <%= item.attributes.appointment_link %>  <%= item.attributes.direction_link %>  </li><% }); %>',

			render: function() {
				  // sort by Distance
				 if(this.userLat && this.userLng) {
				 	this.locations.comparator = function( model ) {
					  var dis = model.get('distance');
					  return Math.round(dis * Math.pow(10, 2)) / Math.pow(10, 2);
					}
					this.locations.sort();

				 } else {
				 	this.addMarkers();
				 }
				this.renderList();
			},
			/**
			 * Adds Markers to map
			 * @Since 1.0
			 */
			addMarkers: function() {
				_.each(this.locations.models, this.addMarker, this);
			},

			/**
			 * Show categories in tab
			 * @since 1.0
			 */
			@todo what if we dont have any categories
			renderList: function() {
				$('#map-items').html(_.template(this.template, { 'models': this.locations.where({'category': this.cat}) }));
			},
			/**
			 * When clicked shows cat list of desired category
			 * @param  {obj} e click event
			 * @since 1.0
			 */
			showCatList: function(e) {
				e.preventDefault();
				this.cat = $(e.currentTarget).data('id');
				this.render();
			},
			/**
			 * When side item is clicked go to locaiton
			 * @param  {obj} event click event
			 * @since 1.0
			 */
			goToLocation: function(event) {
				var id = $(event.currentTarget).data('id');
				var model = this.locations.get(id);
				var pos = new google.maps.LatLng(model.attributes.lat, model.attributes.lng);
				this.map.panTo(pos);
				this.map.setZoom(16);
				var info = model.get('info_popup');
				var marker = model.get('marker');
				this.openInfoWindow(marker, info);
			},
			/**
			 * Pan to users nearest location and open info window
			 * @since 1.0
			 */
			goToNearestLocation: function() {
				this.locations.comparator = function( model ) {
				  var dis = model.get('distance');
				  return Math.round(dis * Math.pow(10, 2)) / Math.pow(10, 2);
				}
				this.locations.sort();
				var nearestLocation = this.locations.at(0);

				docCookies.setItem('location-name', nearestLocation.get('title'), Infinity, '/');
				$('#my-location').html('<strong>'+nearestLocation.get('title')+'</strong>');
				var locationsInCat = this.locations.where({'category': this.cat});

				var pos = new google.maps.LatLng(locationsInCat[0].attributes.lat, locationsInCat[0].attributes.lng);
				this.map.panTo(pos);
				this.map.setZoom(16);
				var info = locationsInCat[0].attributes.info_popup;
				var marker = locationsInCat[0].attributes.marker;
				this.openInfoWindow(marker, info);
			},
			/**
			 * [searchLocation description]
			 * @param  {[type]} event [description]
			 * @return {[type]}       [description]
			 */
			searchLocation: function(event) {
				event.preventDefault();
				var q = $(event.currentTarget).find('input[type="search"]').val();
				$.get( "https://maps.googleapis.com/maps/api/geocode/json?address="+q+"&sensor=false&key="+ajax_object.api_key, $.proxy(this.completedGeoLocation, this));
			},
			/** Handle geolocation response from google
			* @since 1.0
			*/
			completedGeoLocation: function(response) {
				if(response.status === "OK") {
					this.userLat = response.results[0].geometry.location.lat;
					this.userLng = response.results[0].geometry.location.lng;
					var pos = new google.maps.LatLng(this.userLat, this.userLng);
					this.map.panTo(pos);
					this.locations.url = ajax_object.ajax_url+'?action=get_locations&lat='+this.userLat+'&lng='+this.userLng;
					this.locations.fetch();
				}
			},
			/**
			 * Add marker to map
			 * @param {object} location - geo info
			 * @since 1.0
			 */
			addMarker: function(location, index) {
				var latLng = new google.maps.LatLng(location.get('lat'),location.get('lng'));
				var markerOptions = {
			    	position: latLng,
			    	map: this.map,
			    	title: location.get('title'),
			    	icon: location.get('map_icon')
				}

				var marker = new google.maps.Marker(markerOptions);

				location.set({'marker': marker});

				this.infowindow = new google.maps.InfoWindow({
				    content: location.get('info_popup')
				});

				var info = location.get('info_popup');
				google.maps.event.addListener(marker, 'click', $.proxy(this.openInfoWindow, this, marker, info));
			},
			/**
			 * Open info window
			 * @param  {obj} marker marker object
			 * @param  {obj} info   info object
			 * @since 1.0
			 */
			openInfoWindow: function(marker, info) {
				this.infowindow.content = info;
				this.infowindow.open(this.map, marker);
			},
			/**
			 * User Geolocation
			 * @since 1.0
			 */
			html5location: function() {
				if(navigator.geolocation) {
				  navigator.geolocation.getCurrentPosition($.proxy(this.setUserPos, this));
				}
			},
			/**
			 * Get locations distance from Backend
			 * @todo give some notification of errors
			 */
			getLocationsWithDistance: function() {

				if(this.userLat && this.userLng) {
					this.locations.url = ajax_object.ajax_url+'?action=get_locations&lat='+this.userLat+'&lng='+this.userLng;
					this.locations.fetch({
						success: $.proxy(this.goToNearestLocation, this)
					});
				}
			},
			/**
			 * Add cooker for users location
			 * @param {obj} position Users position
			 */
			setUserPos: function(position) {
				this.userLat = position.coords.latitude;
				this.userLng = position.coords.longitude;
				docCookies.setItem('lat', this.userLat, Infinity, '/');
				docCookies.setItem('lng', this.userLng, Infinity, '/');
				this.getLocationsWithDistance();
			}
		});

		/* not to happy with mucking up the global space here.  @todo Setup some namespacing. */
		window.initialize = function() {
			var gMapp = new MapView;
		}

		function loadScript() {
		  var script = document.createElement('script');
		  script.type = 'text/javascript';
		  script.src = 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&' +
		      'callback=initialize';

		 	$('head').append(script);
		}

		loadScript();

	});

}(jQuery));
