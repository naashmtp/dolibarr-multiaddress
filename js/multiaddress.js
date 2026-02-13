/**
 * MultiAddress Module - JavaScript
 * Copyright (C) 2025
 */

var MultiAddress = {
    /**
     * Initialize module
     */
    init: function() {
        console.log('MultiAddress module initialized');

        // Initialize geocoding button if exists
        var geocodeBtn = document.getElementById('btn-geocode');
        if (geocodeBtn) {
            geocodeBtn.addEventListener('click', this.geocodeAddress.bind(this));
        }

        // Initialize map if container exists
        var mapContainer = document.getElementById('multiaddress-map');
        if (mapContainer) {
            this.initMap();
        }

        // Auto-geocode on address change (optional)
        this.setupAutoGeocoding();
    },

    /**
     * Geocode address using Nominatim API
     */
    geocodeAddress: function(e) {
        if (e) e.preventDefault();

        var address = document.querySelector('textarea[name="address_addr"]').value;
        var zip = document.querySelector('input[name="zipcode"]').value;
        var town = document.querySelector('input[name="town"]').value;

        if (!address && !town) {
            alert('Veuillez saisir au moins une adresse ou une ville');
            return;
        }

        var fullAddress = [address, zip, town].filter(Boolean).join(', ');

        // Show loading
        var btn = document.getElementById('btn-geocode');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="multiaddress-loading"></span> Géolocalisation...';
        }

        // Call Nominatim API
        var url = 'https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(fullAddress) + '&limit=1';

        fetch(url, {
            headers: {
                'User-Agent': 'DolibarrMultiAddress/1.0'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '🌍 Géolocaliser';
            }

            if (data && data.length > 0) {
                var lat = data[0].lat;
                var lon = data[0].lon;

                document.querySelector('input[name="latitude"]').value = lat;
                document.querySelector('input[name="longitude"]').value = lon;

                alert('✓ Coordonnées trouvées : ' + lat + ', ' + lon);

                // Update map if exists
                if (this.map) {
                    this.updateMapMarker(lat, lon);
                }
            } else {
                alert('❌ Adresse introuvable. Vérifiez les informations saisies.');
            }
        })
        .catch(error => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '🌍 Géolocaliser';
            }
            console.error('Geocoding error:', error);
            alert('Erreur lors de la géolocalisation');
        });
    },

    /**
     * Initialize Leaflet map (if Leaflet is available)
     */
    initMap: function() {
        // Check if Leaflet is loaded
        if (typeof L === 'undefined') {
            console.warn('Leaflet not loaded, map disabled');
            return;
        }

        var lat = parseFloat(document.querySelector('input[name="latitude"]').value) || 46.603354;
        var lon = parseFloat(document.querySelector('input[name="longitude"]').value) || 1.888334;

        this.map = L.map('multiaddress-map').setView([lat, lon], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);

        this.marker = L.marker([lat, lon], {draggable: true}).addTo(this.map);

        // Update coordinates on marker drag
        this.marker.on('dragend', function(e) {
            var position = e.target.getLatLng();
            document.querySelector('input[name="latitude"]').value = position.lat.toFixed(8);
            document.querySelector('input[name="longitude"]').value = position.lng.toFixed(8);
        });
    },

    /**
     * Update map marker position
     */
    updateMapMarker: function(lat, lon) {
        if (this.map && this.marker) {
            var newLatLng = new L.LatLng(lat, lon);
            this.marker.setLatLng(newLatLng);
            this.map.panTo(newLatLng);
        }
    },

    /**
     * Setup auto-geocoding on address field change (debounced)
     */
    setupAutoGeocoding: function() {
        var townField = document.querySelector('input[name="town"]');
        var zipField = document.querySelector('input[name="zipcode"]');

        if (townField && zipField) {
            var timeout;
            var autoGeocode = function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    // Auto-geocode only if both fields are filled and no coordinates yet
                    var lat = document.querySelector('input[name="latitude"]').value;
                    var lon = document.querySelector('input[name="longitude"]').value;

                    if ((!lat || !lon) && townField.value && zipField.value) {
                        console.log('Auto-geocoding triggered');
                        // Optionally call geocodeAddress() automatically
                    }
                }, 1000);
            };

            townField.addEventListener('blur', autoGeocode);
            zipField.addEventListener('blur', autoGeocode);
        }
    },

    /**
     * Confirm delete action
     */
    confirmDelete: function(addressId, addressLabel) {
        return confirm('Êtes-vous sûr de vouloir supprimer l\'adresse "' + addressLabel + '" ?');
    }
};

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        MultiAddress.init();
    });
} else {
    MultiAddress.init();
}
