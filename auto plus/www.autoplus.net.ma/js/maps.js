function formatPhoneNumber(phoneNumber) {
    // Remove non-digit characters
    phoneNumber = phoneNumber.replace(/\D/g, '');

    // Check if phoneNumber is long enough for the desired format
    if (phoneNumber.length === 10) {
        return phoneNumber.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
    }
    return phoneNumber;
}

function getGoogleMapsUrl(latitude, longitude) {
    return `https://www.google.com/maps?q=${latitude},${longitude}`;
}

function initMap() {
    var locations = [];
    var urlParams = new URLSearchParams(window.location.search);

    var currentLocation = urlParams.get('lat') && urlParams.get('lon') ? [urlParams.get('lat'), urlParams.get('lon')] : null;
    var currentCity = urlParams.get('city');

    $.getJSON("/allCenters", function(result) {
        var baseUrl = window.location.origin;

        result.forEach(function(center, i) {
            var icon_phone = '<svg xmlns="http://www.w3.org/2000/svg" style="fill: #fec714; margin-right: 3px;" width="21" height="21" viewBox="0 0 32 32"><g id="Layer_3" data-name="Layer 3"><path d="M16,1A15,15,0,1,0,31,16,15,15,0,0,0,16,1Zm.643,9.214a5.149,5.149,0,0,1,5.143,5.143H20.5A3.861,3.861,0,0,0,16.643,11.5ZM20.808,25c-.039,0-3.9-.066-8.865-4.943C7.066,15.094,7,11.231,7,11.192,7,10.711,10,7,10.663,7c.23,0,.409.156.605.39.472.562,2.559,3.856,2.463,4.405-.046.258-.265.454-.893.981a7.416,7.416,0,0,0-.866.8,6.906,6.906,0,0,0,.566,1.526A9.435,9.435,0,0,0,16.9,19.462a6.906,6.906,0,0,0,1.526.566,7.416,7.416,0,0,0,.8-.866c.527-.628.723-.847.981-.893.549-.1,3.843,1.991,4.405,2.463.234.2.39.375.39.605C25,22,21.289,25,20.808,25Zm2.906-9.643a7.079,7.079,0,0,0-7.071-7.071V7A8.367,8.367,0,0,1,25,15.357Z"/></g></svg>';
            var icon_address = '<svg xmlns="http://www.w3.org/2000/svg" style="margin-right: 2px;" width="22" height="22" viewBox="0 0 254000 254000"><g transform="matrix(0.8999999999999998,0,0,0.8999999999999998,12700.00000000003,12700.00000000003)"><path d="M127000 254000C56871 254000 0 197129 0 127000S56871 0 127000 0s127000 56871 127000 127000-56871 127000-127000 127000zm0-209606c34742 0 62905 28163 62905 62905 0 34741-62905 102306-62905 102306s-62905-67565-62905-102306c0-34742 28163-62905 62905-62905zm0 37912c13172 0 23854 10682 23854 23855 0 13172-10682 23854-23854 23854s-23854-10682-23854-23854c0-13173 10682-23855 23854-23855z" fill="#fec714" opacity="1"></path></g></svg>';
            
            var logo_show = baseUrl + "/assets/varta_logo.png";
            var class_logo = "";
            var html_logo = '<center><img src="' + logo_show + '" alt="" class="imgLogo" width="120"></center>';

            if (center.url_photo != "") {
                logo_show = baseUrl + '/' + center.url_photo;
                class_logo = 'style="background: url(' + logo_show + '); width: 100%; height: 100px; margin-top: 20px; background-size: cover;"';
                html_logo = '<div ' + class_logo + '></div>';
            }

            var googleMapsUrl = getGoogleMapsUrl(center.latitude, center.longitude);

/*
            locations[i] = [
                html_logo +
                '<div class="popup-maps">' +
                '<p style="margin-bottom: 5px; margin-top: 5px;">' +
                '<a href="#" target="_blank" style="font-size: 14px">' +
                '<b style="font-weight: bold; font-size: 15px; color: #11487f;">' + center.name + '</b></a></p>' +
                '<a href="' + googleMapsUrl + '" target="_blank" style="font-size: 14px">' +
                '<div style="color: #000; font-size: 14px; font-weight: 500; margin-bottom: 5px; display: flex;">' +
                '<div>' + icon_address + '</div>' +
                '<div>' + center.address + '</div></div></a>' +
                '<a href="tel:' + center.phone + '" class="tel" style="color: #fec714; font-size: 14px; font-weight: 500; display: flex; align-items: center; text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.1)">' +
                '<div>' + icon_phone + '</div>' + formatPhoneNumber(center.phone) + '</a>' +
                '</div>',
                center.latitude,
                center.longitude
            ];
*/

            locations[i] = [
                html_logo +
                '<div class="popup-maps">' +
                '<p style="margin-bottom: 5px; margin-top: 5px;">' +
                '<a href="#" target="_blank" style="font-size: 14px">' +
                '<b style="font-weight: bold; font-size: 15px; color: #11487f;">' + center.name + '</b></a></p>' +
                '<a href="' + googleMapsUrl + '" target="_blank" style="font-size: 14px">' +
                '<div style="color: #000; font-size: 14px; font-weight: 500; margin-bottom: 5px; display: flex;">' +
                '<div>' + icon_address + '</div>' +
                '<div>' + center.address + '</div></div></a>' +
                '' +
                '' +
                '</div>',
                center.latitude,
                center.longitude
            ];

        });
    }).done(function() {

      /*  var mapStyles = [
            {
                "featureType": "administrative",
                "elementType": "geometry",
                "stylers": [
                    { "visibility": "off" }
                ]
            }
        ];*/

     /*   var mapStyles = [
            {
                "featureType": "administrative",
                "elementType": "geometry",
                "stylers": [
                    { "visibility": "off" }
                ]
            },
            {
                "featureType": "landscape",
                "elementType": "geometry",
                "stylers": [
                    { "visibility": "simplified" }
                ]
            },
            {
                "featureType": "poi",
                "elementType": "geometry",
                "stylers": [
                    { "visibility": "off" }
                ]
            },
            {
                "featureType": "road",
                "elementType": "geometry",
                "stylers": [
                    { "visibility": "simplified" }
                ]
            },
            {
                "featureType": "water",
                "elementType": "geometry",
                "stylers": [
                    { "visibility": "on" }
                ]
            }
        ];*/

       
        var mapStyles = [
            {
                "featureType": "administrative",
                "elementType": "labels.text.fill",
                "stylers": [
                    { "visibility": "off" }
                ]
            },
            {
                "featureType": "administrative.province",
                "elementType": "geometry.stroke",
                "stylers": [
                    { "visibility": "off" }
                ]
            },
            {
                "featureType": "administrative.province",
                "elementType": "labels.text",
                "stylers": [
                    { "visibility": "off" }
                ]
            },
            {
                "featureType": "administrative.locality",
                "elementType": "labels.text",
                "stylers": [
                    { "visibility": "on" }
                ]
            },
            {
                "featureType": "administrative",
                "elementType": "geometry",
                "stylers": [
                    { "visibility": "off" }
                ]
            }
        ];

        var zoom = currentLocation || currentCity ? 9 : 6;
        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: zoom,
            center: new google.maps.LatLng(31.7917, -7.0926),
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            styles: mapStyles
        });

        var infowindow = new google.maps.InfoWindow();
        var infowindowContent = document.getElementById('infowindow-content');
        infowindow.setContent(infowindowContent);

        locations.forEach(function(location, i) {
            var marker = new google.maps.Marker({
                position: new google.maps.LatLng(location[1], location[2]),
                icon: 'assets/maps_icon.png',
                map: map
            });

            google.maps.event.addListener(marker, 'click', (function(marker, i) {
                return function() {
                    infowindow.setContent(location[0]);
                    infowindow.open(map, marker);
                }
            })(marker, i));
        });

        // Setup autocomplete
        var input = document.getElementById('pac-input');
        var autocomplete = new google.maps.places.Autocomplete(input);
        autocomplete.bindTo('bounds', map);
        autocomplete.setFields(['address_components', 'geometry', 'icon', 'name']);
        autocomplete.setComponentRestrictions({ 'country': 'MA' });
        autocomplete.setTypes(['geocode']);

        autocomplete.addListener('place_changed', function() {
            infowindow.close();
            var place = autocomplete.getPlace();

            if (!place.geometry) {
                window.alert("Adresse invalide");
                return;
            }

            if (place.geometry.viewport) {
                map.fitBounds(place.geometry.viewport);
            } else {
                map.setCenter(place.geometry.location);
            }

            var currentLocation = place.geometry.location.toString().replace("(", "").replace(')', '').split(",");
            var searchLocality = place.address_components.find(function(component) {
                return component.types.includes('locality');
            });
            var currentCity = searchLocality ? searchLocality.long_name : null;
            var searchZip = place.address_components.find(function(component) {
                return component.types.includes('postal_code');
            });
            var currentZip = searchZip ? searchZip.short_name : null;

            $.getJSON("/CalculDistanceGPS", { currentLocation: currentLocation, currentCity: currentCity, currentZip: currentZip }, function(result) {
                $('.centers-section').html("");
                $('.centers-section').html(result.data);
                $(".title-section").removeClass("d-none");
                $(".title-section").html(result.title);

                locations = [];

                result.centers.forEach(function(center, i) {
                    var logo_show = baseUrl + "/assets/varta_logo.png";
                    var class_logo = "";
                    var html_logo = '<center><img src="' + logo_show + '" alt="" class="imgLogo" width="120"></center>';

                    if (center.url_photo != "") {
                        logo_show = baseUrl + '/' + center.url_photo;
                        class_logo = 'style="background: url(' + logo_show + '); width: 100%; height: 100px; margin-top: 20px; background-size: cover;"';
                        html_logo = '<div ' + class_logo + '></div>';
                    }

                    var googleMapsUrl = getGoogleMapsUrl(center.latitude, center.longitude);

/*
                    locations[i] = [
                        html_logo +
                        '<div class="popup-maps">' +
                        '<p style="margin-bottom: 5px; margin-top: 5px;">' +
                        '<a href="#" target="_blank" style="font-size: 14px">' +
                        '<b style="font-weight: bold; font-size: 15px; color: #11487f;">' + center.name + '</b></a></p>' +
                        '<a href="' + googleMapsUrl + '" target="_blank" style="font-size: 14px">' +
                        '<div style="color: #000; font-size: 14px; font-weight: 500; margin-bottom: 5px; display: flex;">' +
                        '<div>' + icon_address + '</div>' +
                        '<div>' + center.address + '</div></div></a>' +
                        '<a href="tel:' + center.phone + '" class="tel" style="color: #fec714; font-size: 14px; font-weight: 500; display: flex; align-items: center; text-shadow: 2px 2px 2px rgba(0, 0, 0, 0.1)">' +
                        '<div>' + icon_phone + '</div>' + formatPhoneNumber(center.phone) + '</a>' +
                        '</div>',
                        center.latitude,
                        center.longitude
                    ];
*/

                    locations[i] = [
                        html_logo +
                        '<div class="popup-maps">' +
                        '<p style="margin-bottom: 5px; margin-top: 5px;">' +
                        '<a href="#" target="_blank" style="font-size: 14px">' +
                        '<b style="font-weight: bold; font-size: 15px; color: #11487f;">' + center.name + '</b></a></p>' +
                        '<a href="' + googleMapsUrl + '" target="_blank" style="font-size: 14px">' +
                        '<div style="color: #000; font-size: 14px; font-weight: 500; margin-bottom: 5px; display: flex;">' +
                        '<div>' + icon_address + '</div>' +
                        '<div>' + center.address + '</div></div></a>' +
                        '' +
                        '' +
                        '</div>',
                        center.latitude,
                        center.longitude
                    ];
                    
                });

                infowindow = new google.maps.InfoWindow();

                locations.forEach(function(location, i) {
                    var marker = new google.maps.Marker({
                        position: new google.maps.LatLng(location[1], location[2]),
                        icon: 'assets/maps_icon.png',
                        map: map
                    });

                    google.maps.event.addListener(marker, 'click', (function(marker, i) {
                        return function() {
                            infowindow.setContent(location[0]);
                            infowindow.open(map, marker);
                        }
                    })(marker, i));
                });
            });
        });

        // Change placeholder for search box
        $('.pac-target-input').attr("placeholder", "Rechercher une adresse");
    });
}
