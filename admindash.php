<?php
// admindash.php
session_start();

// Only allow access if logged in as admin
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/maplibre-gl@3.1.0/dist/maplibre-gl.css" rel="stylesheet" />
    <style>
        body {
            background: #f4f6f8;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .dashboard {
            max-width: 600px;
            margin: 60px auto 30px auto;
            background: #fff;
            padding: 32px 28px 24px 28px;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            position: relative;
        }
        .logout {
            position: absolute;
            top: 24px;
            right: 32px;
        }
        .logout a {
            color: #fff;
            background: #d8000c;
            padding: 8px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .logout a:hover {
            background: #a30000;
        }
        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }
        p {
            text-align: center;
            color: #555;
        }
        hr {
            margin: 24px 0;
            border: none;
            border-top: 1px solid #eee;
        }
        h3 {
            color: #0078d7;
            margin-bottom: 12px;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin-bottom: 14px;
        }
        ul li a {
            color: #0078d7;
            text-decoration: none;
            font-size: 16px;
            padding: 8px 0;
            display: inline-block;
            border-bottom: 1px solid transparent;
            transition: color 0.2s, border-bottom 0.2s;
        }
        ul li a:hover {
            color: #005fa3;
            border-bottom: 1px solid #0078d7;
        }
        .footer {
            text-align: center;
            margin-top: 24px;
            color: #aaa;
            font-size: 13px;
        }
        /* Map styles */
        #admin-map-container {
            width: 100%;
            max-width: 900px;
            height: 500px;
            margin: 40px auto 0 auto;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            overflow: hidden;
            background: #e0eafc;
        }
        #admin-map {
            width: 100%;
            height: 100%;
        }
        .map-legend {
            position: absolute;
            bottom: 30px;
            left: 30px;
            background: rgba(255,255,255,0.95);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 12px 18px;
            font-size: 15px;
            color: #333;
            z-index: 10;
        }
        /* Table styles */
        .placemark-table-container {
            max-width: 900px;
            margin: 40px auto 0 auto;
        }
        #placemark-table {
            width: 100%;
            border-collapse: collapse;
            background: #f9fbfd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,120,215,0.05);
        }
        #placemark-table th, #placemark-table td {
            padding: 10px;
            text-align: left;
            border-top: 1px solid #e0e0e0;
        }
        #placemark-table th {
            background: #0078d7;
            color: #fff;
            font-weight: 600;
        }
        #placemark-table tr:hover {
            background: #f1f1f1;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>You are logged in as <strong>Admin</strong>.</p>
    <hr>
    <h3>Admin Actions</h3>
    <ul>
        <li><a href="Usertable.php">View Users Table</a></li>
        <li><a href="add_user.php">Add New User</a></li>
        <li><a href="pendinguser.php">View Pending Registrations</a></li>
        <!-- Add more admin actions here -->
    </ul>
    <div class="footer">
        &copy; <?php echo date('Y'); ?> DemoApp Admin
    </div>
</div>

<div id="admin-map-container">
    <div id="admin-map"></div>
    <div class="map-legend">
        <strong>Legend:</strong> Placemark Severity<br>
        <span style="display:inline-block;width:16px;height:16px;background:#ff0000;border-radius:50%;margin-right:6px;vertical-align:middle;border:1px solid #ccc;"></span> Critical<br>
        <span style="display:inline-block;width:16px;height:16px;background:#ff5722;border-radius:50%;margin-right:6px;vertical-align:middle;border:1px solid #ccc;"></span> Substantial<br>
        <span style="display:inline-block;width:16px;height:16px;background:#2196f3;border-radius:50%;margin-right:6px;vertical-align:middle;border:1px solid #ccc;"></span> Low
    </div>
</div>

<div class="placemark-table-container" style="max-width:900px;margin:40px auto 0 auto;">
    <h3 style="color:#0078d7;text-align:center;">All Placemarks</h3>
    <!-- Add this search bar above the table -->
    <div style="max-width:900px;margin:20px auto 0 auto;text-align:right;">
        <input id="placemark-search" type="text" placeholder="Search by username, color, or location..." style="padding:7px 12px;border-radius:6px;border:1px solid #bbb;width:270px;">
    </div>
    <div style="max-height:400px;overflow-y:auto;border-radius:8px;box-shadow:0 2px 8px rgba(0,120,215,0.05);">
        <table id="placemark-table" style="width:100%;border-collapse:collapse;background:#f9fbfd;">
            <thead>
                <tr>
                    <th style="background:#0078d7;color:#fff;font-weight:600;padding:10px;">Username</th>
                    <th style="background:#0078d7;color:#fff;font-weight:600;padding:10px;">Color Name</th>
                    <th style="background:#0078d7;color:#fff;font-weight:600;padding:10px;">Location Name</th>
                    <th style="background:#0078d7;color:#fff;font-weight:600;padding:10px;">Coordinates</th>
                    <th style="background:#0078d7;color:#fff;font-weight:600;padding:10px;">Date Added</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be filled by JS -->
            </tbody>
        </table>
    </div>
</div>
<script src="https://unpkg.com/maplibre-gl@3.1.0/dist/maplibre-gl.js"></script>
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
        container: 'admin-map',
        style: `https://api.maptiler.com/maps/hybrid/style.json?key=${MAPTILER_KEY}`,
        center: [120.9842, 14.5995], // Manila as default
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

    let mapMarkers = [];
    let allPlacemarks = [];

    async function fetchAllPlacemarks() {
        try {
            const response = await fetch('admin_placemarks_api.php', {
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
            el.title = `User: ${pm.username}\nCreated: ${pm.createdAt}`;

            const marker = new maplibregl.Marker(el)
                .setLngLat([parseFloat(pm.lng), parseFloat(pm.lat)])
                .setPopup(new maplibregl.Popup({ offset: 18 })
                    .setHTML(
                        `<strong>User:</strong> ${pm.username}<br>
                         <strong>Created:</strong> ${pm.createdAt}<br>
                         <strong>Color:</strong> <span style="color:${pm.color};font-weight:bold;">${hexToColorName(pm.color)}</span><br>
                         <strong>Coordinates:</strong> ${parseFloat(pm.lat).toFixed(5)}, ${parseFloat(pm.lng).toFixed(5)}`
                    )
                )
                .addTo(map);

            mapMarkers.push(marker);
        });
    }

    // Fill the placemark table with filtered placemarks
    async function fillPlacemarkTable(placemarks) {
        const tbody = document.querySelector('#placemark-table tbody');
        tbody.innerHTML = '';
        const limited = placemarks.slice(0, 10);
        for (const pm of limited) {
            const tr = document.createElement('tr');

            // Username
            const tdUser = document.createElement('td');
            tdUser.textContent = pm.username;
            tr.appendChild(tdUser);

            // Color
            const tdColor = document.createElement('td');
            tdColor.textContent = hexToColorName(pm.color);
            tr.appendChild(tdColor);

            // Location Name (async, with cache)
            const tdLoc = document.createElement('td');
            tdLoc.innerHTML = '<span style="color:#aaa;">Loading...</span>';
            tr.appendChild(tdLoc);

            getLocationName(pm.lat, pm.lng).then(locationName => {
                tdLoc.textContent = locationName || '(Unknown)';
                if (!locationName) tdLoc.style.color = '#aaa';
            });

            // Coordinates
            const tdCoords = document.createElement('td');
            tdCoords.textContent = `${parseFloat(pm.lat).toFixed(5)}, ${parseFloat(pm.lng).toFixed(5)}`;
            tr.appendChild(tdCoords);

            // Date Added
            const tdDate = document.createElement('td');
            tdDate.textContent = pm.createdAt || pm.created_at || '';
            tr.appendChild(tdDate);

            tbody.appendChild(tr);
        }
    }

    // Update the search event to filter both table and map
    document.getElementById('placemark-search').addEventListener('input', async function () {
        const query = this.value.trim().toLowerCase();
        let filtered = allPlacemarks;

        if (query) {
            const colorNames = ["red", "orange", "blue"];
            if (colorNames.includes(query)) {
                filtered = allPlacemarks.filter(pm => hexToColorName(pm.color).toLowerCase() === query);
            } else {
                filtered = allPlacemarks.filter(pm => (pm.username || '').toLowerCase().includes(query));
            }
        } else {
            filtered = [...allPlacemarks].sort((a, b) => {
                const dateA = new Date(a.createdAt || a.created_at || 0);
                const dateB = new Date(b.createdAt || b.created_at || 0);
                return dateB - dateA;
            });
        }

        addPlacemarksToMap(filtered); // Update map markers
        await fillPlacemarkTable(filtered); // Update table
    });

    // On map load, show all placemarks
    map.on('load', async () => {
        allPlacemarks = await fetchAllPlacemarks();
        addPlacemarksToMap(allPlacemarks);
        fillPlacemarkTable(allPlacemarks);
    });
</script>
</body>
</html>