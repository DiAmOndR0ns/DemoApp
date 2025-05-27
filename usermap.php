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
    <!-- UI controls FIRST -->
    <!-- Place this at the top of your <body> in usermap.php, replacing your current navigation/buttons/search markup -->
    <a href="userlandpage.php" title="Back to Dashboard" class="map-btn map-back-btn" style="position:absolute;top:24px;left:24px;">
        <i class="fa-solid fa-arrow-left"></i>
    </a>
    <div id="user-info" class="map-btn map-user-info" style="position:absolute;top:24px;right:24px;">
        <span id="username-display"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <div id="search-container" class="map-btn map-search-container" style="position:absolute;top:90px;left:50%;transform:translateX(-50%);">
        <input id="search-input" type="text" placeholder="Search for a location..." aria-label="Search location" />
        <button id="search-button" class="map-btn map-search-btn" title="Search">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div>
    <form method="post" action="logout.php" class="map-btn map-logout-btn" style="position:absolute;top:24px;right:140px;">
        <button id="logout-btn" type="submit" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </form>
    <!-- Map LAST -->
    <div id="map" aria-label="Interactive map"></div>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(120deg, #f9fbfd 0%, #e3f0ff 100%);
        }
        #map {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: 1;
        }
        .map-btn {
            box-shadow: 0 2px 8px rgba(0,120,215,0.10);
            border-radius: 50px;
            background: #fff;
            border: none;
            outline: none;
            transition: background 0.2s, color 0.2s;
            font-size: 1.1rem;
            z-index: 1002;
        }
        .map-back-btn {
            position: absolute;
            top: 24px;
            left: 24px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0078d7;
            font-size: 1.7rem;
            text-decoration: none;
        }
        .map-back-btn:hover {
            background: #e3f0ff;
            color: #ff5722;
        }
        .map-user-info {
            position: absolute;
            top: 24px;
            right: 24px;
            background: #fff;
            padding: 8px 18px 8px 18px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            color: #0078d7;
            font-size: 1.08rem;
            box-shadow: 0 2px 8px rgba(0,120,215,0.10);
        }
        .map-search-container {
            position: absolute;
            top: 90px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border-radius: 30px;
            box-shadow: 0 2px 8px rgba(0,120,215,0.10);
            display: flex;
            align-items: center;
            padding: 6px 12px;
            z-index: 1002;
        }
        #search-input {
            border: none;
            outline: none;
            padding: 8px 12px;
            font-size: 1rem;
            border-radius: 20px;
            background: #f5faff;
            margin-right: 8px;
            width: 180px;
            transition: background 0.2s;
        }
        #search-input:focus {
            background: #e3f0ff;
        }
        .map-search-btn {
            background: #0078d7;
            color: #fff;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            font-size: 1.2rem;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
        }
        .map-search-btn:hover {
            background: #2196f3;
            color: #fff;
        }
        .map-logout-btn {
            position: absolute;
            top: 24px;
            right: 140px;
            background: #fff;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(255,87,34,0.10);
            z-index: 1002;
            border: none;
            padding: 0;
        }
        .map-logout-btn button {
            background: none;
            border: none;
            color: #ff5722;
            font-size: 1.7rem;
            width: 100%;
            height: 100%;
            cursor: pointer;
            border-radius: 50%;
            transition: background 0.2s, color 0.2s;
        }
        .map-logout-btn button:hover {
            background: #ffe3e3;
            color: #d32f2f;
        }
        @media (max-width: 600px) {
            .map-back-btn {
                top: 10px;
                left: 10px;
            }
            .map-user-info {
                top: 10px;
                right: 10px;
                padding: 6px 10px;
            }
            .map-search-container {
                bottom: 70px;
                left: 10px;
                width: 90vw;
            }
            .map-logout-btn {
                bottom: 10px;
                right: 10px;
                width: 44px;
                height: 44px;
            }
            #search-input {
                width: 100px;
            }
        }
    </style>
        <!-- Add this inside your <body>, preferably at the top for visibility -->
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

        // Fix: Make sure the search button text/icon is restored after search, and marker always shows
        async function handleSearch() {
            const location = document.getElementById('search-input').value.trim();
            if (!location) {
                alert('Please enter a location to search.');
                return;
            }

            const searchButton = document.getElementById('search-button');
            searchButton.disabled = true;
            // Show only the spinner or searching text
            const originalHTML = searchButton.innerHTML;
            searchButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

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
                    markerElement.style.width = '28px';
                    markerElement.style.height = '28px';
                    markerElement.style.background = '#2196f3';
                    markerElement.style.borderRadius = '50%';
                    markerElement.style.border = '3px solid #fff';
                    markerElement.style.boxShadow = '0 2px 8px rgba(33,150,243,0.18)';

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
                searchButton.innerHTML = originalHTML;
            }
        }

        // Add event listener for search button
        document.getElementById('search-button').addEventListener('click', handleSearch);
        document.getElementById('search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
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