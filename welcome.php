<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>  
  <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Advanced Photorealistic 3D Map with Placemarks</title>
  <script src="https://unpkg.com/maplibre-gl@3.1.0/dist/maplibre-gl.js"></script>
  <link href="https://unpkg.com/maplibre-gl@3.1.0/dist/maplibre-gl.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div id="user-info">
        <span id="username-display"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <form method="post" action="logout.php" style="display:inline;">
            <button id="logout-btn" type="submit">Logout</button>
        </form>
    </div>

    <div id="search-container">
        <input id="search-input" type="text" placeholder="Search for a location..." aria-label="Search location" />
        <button id="search-button">Search</button>
    </div>
    <div id="map" aria-label="Interactive map"></div>
    <script>
    // MapTiler key (replace with your own if needed)
    const MAPTILER_KEY = '1TBBpJMr16MtMfya24P0';

    // Helper: Convert hex color to color name (basic mapping)
    function hexToColorName(hex) {
        const map = {
            "#ff0000": "Red",
            "#ff5722": "Orange",
            "#2196f3": "Blue"
        };
        if (!hex) return "Unknown";
        hex = hex.toLowerCase();
        return map[hex] || hex;
    }

    const locationCache = {};

    // Helper: Reverse geocode coordinates to location name (using Nominatim)
    async function getLocationName(lat, lng) {
        const key = `${lat},${lng}`;
        if (locationCache[key]) return locationCache[key];
        try {
            const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;
            const res = await fetch(url, {headers: {'Accept': 'application/json'}});
            if (!res.ok) return '';
            const data = await res.json();
            locationCache[key] = data.display_name || '';
            return locationCache[key];
        } catch {
            return '';
        }
    }

    // Initialize the MapTiler 3D satellite map
    const map = new maplibregl.Map({
        container: 'map',
        style: `https://api.maptiler.com/maps/hybrid/style.json?key=${MAPTILER_KEY}`,
        center: [123.7994, 10.7333], // Tuburan, Cebu as center
        zoom: 5.5,
        pitch: 45, // 3D angle
        bearing: -10,
        antialias: true
    });

    map.addControl(new maplibregl.NavigationControl(), 'top-right');

    // Add 3D terrain after map loads
    map.on('load', () => {
        map.addSource('terrain', {
            type: 'raster-dem',
            url: `https://api.maptiler.com/tiles/terrain-rgb/tiles.json?key=${MAPTILER_KEY}`,
            tileSize: 256
        });
        map.setTerrain({ source: 'terrain', exaggeration: 2.0 });
    });

    async function handleSearch() {
        const location = document.getElementById('search-input').value.trim();
        if (!location) {
            alert('Please enter a location to search.');
            return;
        }

        const searchButton = document.getElementById('search-button');
        searchButton.disabled = true;
        searchButton.textContent = "Searching...";

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(location)}&format=json&limit=1`
            );
            const data = await response.json();

            if (data.length > 0) {
                const { lon, lat, display_name } = data[0];

                // Remove previous search markers
                if (window._searchMarker) {
                    window._searchMarker.remove();
                    window._searchMarker = null;
                }

                // Add new search marker
                const markerElement = document.createElement('div');
                markerElement.className = 'pulse-marker search-marker';

                window._searchMarker = new maplibregl.Marker({
                    element: markerElement,
                    anchor: 'bottom'
                })
                .setLngLat([parseFloat(lon), parseFloat(lat)])
                .setPopup(new maplibregl.Popup().setHTML(`<h3>${display_name}</h3>`))
                .addTo(map);

                // Fly to location
                map.flyTo({
                    center: [parseFloat(lon), parseFloat(lat)],
                    zoom: 16,
                    pitch: 60,
                    bearing: -40,
                    speed: 1.2,
                    curve: 1.4
                });
            } else {
                alert('Location not found. Please try a different search term.');
            }
        } catch (error) {
            console.error('Search error:', error);
            alert('An error occurred during search. Please try again.');
        } finally {
            searchButton.disabled = false;
            searchButton.textContent = "Search";
        }
    }

    // Add event listener for search button
    document.getElementById('search-button').addEventListener('click', handleSearch);
    document.getElementById('search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleSearch();
        }
    });

    let mapMarkers = [];
    let allPlacemarks = [];

    async function fetchAllPlacemarks() {
        try {
            const response = await fetch('placemarks_api.php', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error('Failed to load placemarks');
            return await response.json();
        } catch (e) {
            alert('Could not load placemarks: ' + e.message);
            return [];
        }
    }

    function addPlacemarksToMap(placemarks) {
        // Remove old markers
        mapMarkers.forEach(marker => marker.remove());
        mapMarkers = [];

        placemarks.forEach(pm => {
            const el = document.createElement('div');
            el.style.width = '22px';
            el.style.height = '22px';
            el.style.background = pm.color || '#0078d7';
            el.style.borderRadius = '50%';
            el.style.border = '2px solid #fff';
            el.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
            el.title = `User: ${pm.username}\nCreated: ${pm.created_at}`;

            // Only show delete button if placemark belongs to current user
            const currentUser = document.getElementById('username-display')?.textContent || '';
            let deleteBtn = '';
            if (pm.username === currentUser) {
                deleteBtn = `<button class="delete-placemark-btn" data-id="${pm.id}">Delete</button>`;
            }

            const popup = new maplibregl.Popup({ offset: 18 })
                .setHTML(
                    `<strong>User:</strong> ${pm.username}<br>
                     <strong>Created:</strong> ${pm.created_at}<br>
                     <strong>Color:</strong> <span style="color:${pm.color};font-weight:bold;">${hexToColorName(pm.color)}</span><br>
                     <strong>Coordinates:</strong> ${parseFloat(pm.lat).toFixed(5)}, ${parseFloat(pm.lng).toFixed(5)}<br>
                     ${deleteBtn}`
                );

            const marker = new maplibregl.Marker(el)
                .setLngLat([parseFloat(pm.lng), parseFloat(pm.lat)])
                .setPopup(popup)
                .addTo(map);

            // Listen for popup open to attach delete handler
            marker.getElement().addEventListener('click', () => {
                setTimeout(() => {
                    const btn = document.querySelector('.delete-placemark-btn[data-id="' + pm.id + '"]');
                    if (btn) {
                        btn.onclick = async function() {
                            if (confirm('Delete this placemark?')) {
                                await deletePlacemark(pm.id);
                            }
                        }
                    }
                }, 200);
            });

            mapMarkers.push(marker);
        });
    }

    // On map load, show all placemarks
    map.on('load', async () => {
        allPlacemarks = await fetchAllPlacemarks();
        addPlacemarksToMap(allPlacemarks);
    });

    // --- Placemark Color Picker UI ---
    let colorPickerDiv = null;
    let pendingPlacemarkCoords = null;
    const ALLOWED_COLORS = ['#ff0000', '#ff5722', '#2196f3'];

    function showColorPicker(coords) {
        // Remove existing picker if any
        if (colorPickerDiv) colorPickerDiv.remove();

        colorPickerDiv = document.createElement('div');
        colorPickerDiv.style.position = 'absolute';
        colorPickerDiv.style.left = '50%';
        colorPickerDiv.style.top = '50%';
        colorPickerDiv.style.transform = 'translate(-50%, -50%)';
        colorPickerDiv.style.background = '#fff';
        colorPickerDiv.style.padding = '18px 24px';
        colorPickerDiv.style.borderRadius = '10px';
        colorPickerDiv.style.boxShadow = '0 2px 16px rgba(0,0,0,0.18)';
        colorPickerDiv.style.zIndex = 1000;
        colorPickerDiv.innerHTML = `
            <div style="font-weight:600;margin-bottom:10px;">Choose Placemark Color</div>
            <div style="display:flex;gap:12px;margin-bottom:12px;">
                ${ALLOWED_COLORS.map(color => `
                    <div class="color-option" 
                        style="width:32px;height:32px;border-radius:50%;background:${color};border:2px solid #ccc;cursor:pointer;"
                        data-color="${color}">
                    </div>
                `).join('')}
            </div>
            <button id="cancel-color-picker" style="margin-top:4px;">Cancel</button>
        `;
        document.body.appendChild(colorPickerDiv);

        // Color selection
        colorPickerDiv.querySelectorAll('.color-option').forEach(el => {
            el.addEventListener('click', async function() {
                const color = this.getAttribute('data-color');
                colorPickerDiv.remove();
                colorPickerDiv = null;
                await addPlacemarkAndSave(coords, color);
            });
        });
        // Cancel button
        colorPickerDiv.querySelector('#cancel-color-picker').onclick = () => {
            colorPickerDiv.remove();
            colorPickerDiv = null;
            pendingPlacemarkCoords = null;
        };
    }

    // --- Single-click to add placemark ---
    map.on('click', (e) => {
        // Check if the click is on an existing placemark marker
        const features = map.queryRenderedFeatures(e.point);
        let clickedMarker = false;

        // Check if the click target is a marker element
        if (e.originalEvent.target.classList.contains('maplibregl-marker') ||
            e.originalEvent.target.closest('.maplibregl-marker')) {
            clickedMarker = true;
        }

        // If not clicking a marker, show color picker
        if (!clickedMarker) {
            pendingPlacemarkCoords = e.lngLat;
            showColorPicker(e.lngLat);
        }
    });

    // --- Add placemark and save to DB ---
    async function addPlacemarkAndSave(coords, color) {
        // Prepare placemark data
        const placemarkData = [{
            id: 'pm-' + Date.now() + Math.random().toString(36).substr(2, 5),
            lng: coords.lng,
            lat: coords.lat,
            color: color,
            username: document.getElementById('username-display')?.textContent || 'anonymous',
            createdAt: new Date().toISOString()
        }];
        try {
            const response = await fetch('placemarks_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(placemarkData)
            });
            if (!response.ok) throw new Error('Failed to save placemark');
            // Reload all placemarks from DB to ensure consistency
            allPlacemarks = await fetchAllPlacemarks();
            addPlacemarksToMap(allPlacemarks);
        } catch (e) {
            alert('Could not save placemark: ' + e.message);
        }
    }

    async function deletePlacemark(id) {
        try {
            const response = await fetch('placemarks_api.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ id })
            });
            const result = await response.json();
            if (result.success) {
                allPlacemarks = await fetchAllPlacemarks();
                addPlacemarksToMap(allPlacemarks);
            } else {
                alert(result.error || 'Could not delete placemark.');
            }
        } catch (e) {
            alert('Could not delete placemark: ' + e.message);
        }
    }
    </script>
</body>
</html>