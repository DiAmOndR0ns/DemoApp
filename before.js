// Configuration
// Configuration
const MAPTILER_KEY = '1TBBpJMr16MtMfya24P0';
const API_ENDPOINT = 'http://localhost/DemoApp/welcome.php'; // Path to your PHP API
let is3DEnabled = true;
let currentMarkers = [];
let placemarkIdCounter = 0;
let currentPlacemarkColor = '#FF5722'; // Default color (orange)
let sunPosition = 90; // Default sun position
let sunIntensity = 1.0; // Default sun intensity

// Initialize map with fresh imagery cache busting
const map = new maplibregl.Map({
    container: 'map',
    style: `https://api.maptiler.com/maps/hybrid/style.json?key=${MAPTILER_KEY}&fresh=${Date.now()}`,
    center: [123.7994, 10.7333], // Tuburan, Cebu
    zoom: 16,
    pitch: 60,
    bearing: -40,
    antialias: true
});

// Add geolocate control
map.addControl(new maplibregl.GeolocateControl({
    positionOptions: {
        enableHighAccuracy: true
    },
    trackUserLocation: true,
    showUserLocation: true,
    showAccuracyCircle: false
}));

// Add navigation controls
map.addControl(new maplibregl.NavigationControl(), 'top-right');

// Add color picker UI to the map
function addColorPickerUI() {
    const colorPickerContainer = document.createElement('div');
    colorPickerContainer.className = 'map-control color-picker-control';
    colorPickerContainer.innerHTML = `
        <div class="color-picker-header">
            <span>Placemark Color</span>
        </div>
        <input type="color" id="placemark-color" value="${currentPlacemarkColor}" title="Choose placemark color">
        <div class="color-presets">
            <div class="color-option" style="background-color: #FF5722;" data-color="#FF5722"></div>
            <div class="color-option" style="background-color: #2196F3;" data-color="#2196F3"></div>
            <div class="color-option" style="background-color: #4CAF50;" data-color="#4CAF50"></div>
            <div class="color-option" style="background-color: #FFC107;" data-color="#FFC107"></div>
            <div class="color-option" style="background-color: #9C27B0;" data-color="#9C27B0"></div>
        </div>
        <button id="save-placemarks-btn" class="save-button">Save to Server</button>
    `;
    
    document.getElementById('map').appendChild(colorPickerContainer);
    
    document.getElementById('placemark-color').addEventListener('input', (e) => {
        currentPlacemarkColor = e.target.value;
    });
    
    document.querySelectorAll('.color-option').forEach(option => {
        option.addEventListener('click', (e) => {
            currentPlacemarkColor = e.target.dataset.color;
            document.getElementById('placemark-color').value = currentPlacemarkColor;
        });
    });

    // Add save button event listener
    document.getElementById('save-placemarks-btn').addEventListener('click', async () => {
        const button = document.getElementById('save-placemarks-btn');
        button.textContent = 'Saving...';
        button.disabled = true;
        
        const success = await savePlacemarksToServer();
        
        button.textContent = success ? 'Saved!' : 'Error Saving';
        setTimeout(() => {
            button.textContent = 'Save to Server';
            button.disabled = false;
        }, 2000);
    });
}

// Add click event listener for placing markers
map.on('click', (e) => {
    addCircularPlacemark(e.lngLat, currentPlacemarkColor);
});

// Function to add a circular placemark with custom color
function addCircularPlacemark(coords, color = currentPlacemarkColor, id = null, createdAt = null) {
    if (!coords || isNaN(coords.lng) || isNaN(coords.lat)) {
        console.error('Invalid coordinates:', coords);
        return null;
    }

    // Create marker element
    const markerElement = document.createElement('div');
    markerElement.className = 'circular-placemark';

    // Apply styles for the circular marker
    Object.assign(markerElement.style, {
        width: '24px',
        height: '24px',
        borderRadius: '50%',
        backgroundColor: color,
        border: '2px solid white',
        boxShadow: '0 0 8px rgba(0,0,0,0.3)',
        cursor: 'pointer',
        position: 'relative'
    });

    // Add inner white dot for better visibility
    const innerDot = document.createElement('div');
    Object.assign(innerDot.style, {
        position: 'absolute',
        top: '5px',
        left: '5px',
        width: '10px',
        height: '10px',
        borderRadius: '50%',
        backgroundColor: 'white',
        opacity: '0.8'
    });
    markerElement.appendChild(innerDot);

    // Optional: Add pulse animation
    const pulseElement = document.createElement('div');
    pulseElement.className = 'pulse-effect';
    Object.assign(pulseElement.style, {
        position: 'absolute',
        top: '-6px',
        left: '-6px',
        width: '36px',
        height: '36px',
        borderRadius: '50%',
        backgroundColor: color,
        opacity: '0.4',
        transform: 'scale(0)',
        animation: 'pulse 2s infinite'
    });
    markerElement.appendChild(pulseElement);

    // Create the marker
    const marker = new maplibregl.Marker({
        element: markerElement,
        anchor: 'center',
        draggable: true
    })
    .setLngLat(coords)
    .setPopup(new maplibregl.Popup({ offset: 25 })
        .setHTML(`
            <p>Coordinates: ${coords.lng.toFixed(6)}, ${coords.lat.toFixed(6)}</p>
            <div class="popup-actions">
                <button class="change-color-btn" data-marker-id="${placemarkIdCounter}">Change Color</button>
                <button class="delete-marker-btn" data-marker-id="${placemarkIdCounter}">Delete</button>
            </div>`))
    .addTo(map);

    // Store marker reference with ID
    marker._placemarkId = id !== null ? id : placemarkIdCounter++;
    if (id !== null && id >= placemarkIdCounter) {
        placemarkIdCounter = id + 1;
    }
    marker._createdAt = createdAt || new Date().toISOString();

    currentMarkers.push(marker);
    marker.togglePopup();

    // Automatically save after creation
    debouncedSavePlacemarks();

    return marker;
}

// Debounced saving function
let saveTimeout;
function debouncedSavePlacemarks() {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        savePlacemarksToServer();
    }, 1000);
}

// Modify the savePlacemarksToServer function
async function savePlacemarksToServer() {
    const placemarksData = currentMarkers.map(marker => {
        const coords = marker.getLngLat();
        
        return {
            id: marker._placemarkId || generateId(),
            lng: coords.lng,
            lat: coords.lat,
            color: marker.getElement()?.style?.backgroundColor || '',
            username: document.getElementById('username-display').textContent, // Get username from the display
            createdAt: marker._createdAt || new Date().toISOString()
        };
    });

    try {
        const response = await fetch(API_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include', // Important for sending session cookies
            body: JSON.stringify(placemarksData)
        });

        if (!response.ok) {
            let errorText = await response.text();
            if (errorText.startsWith('<!') || errorText.includes('<html>')) {
                errorText = `Server returned HTML error (status ${response.status})`;
            }
            throw new Error(errorText || `HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Save successful:', result.message);
        return true;
    } catch (error) {
        console.error('Error saving to server:', error.message);
        showErrorToUser('Failed to save placemarks. Please try again later.');
        return false;
    }
}

// Helper function to show errors to user
function showErrorToUser(message) {
    const errorElement = document.getElementById('error-message') || createErrorElement();
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    
    setTimeout(() => {
        errorElement.style.display = 'none';
    }, 5000);
}

function createErrorElement() {
    const el = document.createElement('div');
    el.id = 'error-message';
    el.style.position = 'fixed';
    el.style.bottom = '20px';
    el.style.right = '20px';
    el.style.padding = '15px';
    el.style.backgroundColor = '#ff4444';
    el.style.color = 'white';
    el.style.borderRadius = '5px';
    el.style.zIndex = '1000';
    el.style.display = 'none';
    document.body.appendChild(el);
    return el;
}
// Modify the loadPlacemarksFromServer function to include credentials
async function loadPlacemarksFromServer() {
    const loadingIndicator = showLoadingIndicator("Loading placemarks...");
    
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);

        const response = await fetch(API_ENDPOINT, {
            credentials: 'include', // Important for sending session cookies
            signal: controller.signal
        });
        clearTimeout(timeoutId);

        if (!response.ok) {
            let errorData;
            try {
                errorData = await response.json();
            } catch (e) {
                errorData = { error: `HTTP ${response.status}: ${response.statusText}` };
            }
            throw new Error(errorData.error || 'Server returned an error');
        }

        const placemarksData = await response.json();
        
        if (!Array.isArray(placemarksData)) {
            throw new Error('Expected an array of placemarks from server');
        }

        clearExistingMarkers();
        const processedMarkers = await processPlacemarks(placemarksData);
        
        console.log(`Loaded ${processedMarkers.length} placemarks`);
        return true;
    } catch (error) {
        console.error('Placemark loading failed:', error);
        showNotification(
            error.message.includes('aborted') 
                ? 'Request timed out. Please try again.' 
                : `Failed to load placemarks: ${error.message}`,
            'error'
        );
        return false;
    } finally {
        loadingIndicator.remove();
    }
}

// Helper function to show loading state
function showLoadingIndicator(message) {
    const loader = document.createElement('div');
    loader.className = 'loading-indicator';
    loader.innerHTML = `
        <div class="loading-spinner"></div>
        <div class="loading-text">${message}</div>
    `;
    document.body.appendChild(loader);
    return loader;
}

// Safely clear existing markers
function clearExistingMarkers() {
    currentMarkers.forEach(marker => {
        try {
            if (!marker._isUserLocation) {
                const popup = marker.getPopup();
                if (popup) popup.remove();
                marker.remove();
            }
        } catch (e) {
            console.warn('Error removing marker:', e);
        }
    });
    currentMarkers = currentMarkers.filter(m => m._isUserLocation);
}

// Process and validate placemarks
async function processPlacemarks(placemarksData) {
    const successfulMarkers = [];

    for (const data of placemarksData) {
        try {
            // Validate required fields
            if (typeof data !== 'object' || data === null) {
                throw new Error('Invalid placemark data format');
            }

            const lng = parseFloat(data.lng);
            const lat = parseFloat(data.lat);

            if (isNaN(lng) || lng < -180 || lng > 180) {
                throw new Error(`Invalid longitude: ${data.lng}`);
            }

            if (isNaN(lat) || lat < -90 || lat > 90) {
                throw new Error(`Invalid latitude: ${data.lat}`);
            }

            // Add marker with validated data
            const marker = addCircularPlacemark(
                { lng, lat },
                isValidColor(data.color) ? data.color : '#FF0000',
                data.id?.toString() || generateId(),
                data.created_at || data.createdAt || new Date().toISOString()
            );

            if (marker) {
                successfulMarkers.push(marker);
            }
        } catch (e) {
            console.error('Skipping invalid placemark:', data, e);
        }
    }

    return successfulMarkers;
}

// Validate color format
function isValidColor(color) {
    return /^#([0-9A-F]{3}){1,2}$/i.test(color);
}

// Generate more reliable IDs
function generateId() {
    return 'pm-' + Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
}
// Modify the deletePlacemarkFromServer function to include credentials
async function deletePlacemarkFromServer(id) {
    if (!id) {
        console.error('Delete failed: Missing ID parameter');
        throw new Error('Missing ID parameter');
    }

    try {
        const response = await fetch(`${API_ENDPOINT}?id=${encodeURIComponent(id)}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include' // Important for sending session cookies
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to delete placemark');
        }

        const result = await response.json();
        console.log('Delete successful:', result.message);
        return true;
    } catch (error) {
        console.error('Error deleting placemark:', error.message);
        showErrorToUser('Failed to delete placemark. Please try again.');
        return false;
    }
}

// Update the marker deletion event handler
document.addEventListener('click', async (e) => {
    if (e.target.classList.contains('delete-marker-btn')) {
        const markerId = e.target.dataset.markerId;
        if (!markerId) {
            console.error('No marker ID found in delete button');
            return;
        }

        const markerIndex = currentMarkers.findIndex(m => m._placemarkId === markerId);
        if (markerIndex === -1) {
            console.warn(`Marker with ID ${markerId} not found.`);
            return;
        }

        const marker = currentMarkers[markerIndex];
        const confirmation = confirm(`Are you sure you want to delete "${marker.getPopup()?.options?.html?.match(/<h3>(.*?)<\/h3>/)?.[1] || 'this placemark'}"?`);
        
        if (confirmation) {
            const success = await deletePlacemarkFromServer(markerId);
            if (success) {
                marker.remove();
                currentMarkers.splice(markerIndex, 1);
            }
        }
    }
});

// Handle marker interactions
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('change-color-btn')) {
        const markerId = parseInt(e.target.dataset.markerId);
        const marker = currentMarkers.find(m => m._placemarkId === markerId);

        if (marker) {
            const newColor = prompt('Enter a new color (hex code):', currentPlacemarkColor);
            if (newColor) {
                updateMarkerColor(marker, newColor);
                debouncedSavePlacemarks();
            }
        }
    }

    if (e.target.classList.contains('delete-marker-btn')) {
        const markerId = parseInt(e.target.dataset.markerId);
        const markerIndex = currentMarkers.findIndex(m => m._placemarkId === markerId);

        if (markerIndex !== -1) {
            const marker = currentMarkers[markerIndex]; // Ensure marker exists
            deletePlacemarkFromServer(markerId).then(success => {
                if (success) {
                    if (marker) {
                        marker.remove(); // Safely remove the marker
                    }
                    currentMarkers.splice(markerIndex, 1); // Remove from array
                }
            });
        } else {
            console.warn(`Marker with ID ${markerId} not found.`);
        }
    }
});

// Update marker color
function updateMarkerColor(marker, newColor) {
    // Convert color names to hex values
    const colorNameToHex = {
        'red': '#FF0000',
        'blue': '#0000FF',
        'green': '#00FF00',
        'yellow': '#FFFF00',
        'purple': '#800080',
        'orange': '#FFA500',
        'black': '#000000',
        'white': '#FFFFFF'
    };

    // If a basic color name was provided, convert it to hex
    const hexColor = colorNameToHex[newColor.toLowerCase()] || newColor;

    // Validate hex color format
    if (!/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(hexColor)) {
        showErrorToUser('Please enter a valid hex color code (e.g., #FF0000)');
        return false;
    }

    const markerElement = marker.getElement();
    markerElement.style.backgroundColor = hexColor;
    
    // Update pulse effect color
    const pulseElement = markerElement.querySelector('.pulse-effect');
    if (pulseElement) {
        pulseElement.style.backgroundColor = hexColor;
    }
    
    // Update current color
    currentPlacemarkColor = hexColor;
    const colorPicker = document.getElementById('placemark-color');
    if (colorPicker) {
        colorPicker.value = hexColor;
    }

    return true;
}

const style = document.createElement('style');
style.textContent = `
    .pulse-marker {
        width: 20px;
        height: 20px;
            if (newColor) {
                updateMarkerColor(marker, newColor);
            }
        }
    }
    
    if (e.target.classList.contains('delete-marker-btn')) {
        const markerId = parseInt(e.target.dataset.markerId);
        const markerIndex = currentMarkers.findIndex(m => m._placemarkId === markerId);
        
        if (markerIndex !== -1) {
            currentMarkers[markerIndex].remove();
            currentMarkers.splice(markerIndex, 1);
        }
    }
});

// Function to update marker color
function updateMarkerColor(marker, newColor) {
    const markerElement = marker.getElement();
    markerElement.style.backgroundColor = newColor;
    
    // Update pulse effect color
    const pulseElement = markerElement.querySelector('.pulse-effect');
    if (pulseElement) {
        pulseElement.style.backgroundColor = newColor;
    }
    
    // Update current color if this is the last placed marker
    currentPlacemarkColor = newColor;
    document.getElementById('placemark-color').value = newColor;
}

// Initialize core map features
function initializeMapFeatures() {
    // Add terrain with fresh cache busting
    map.addSource('terrain', {
        type: 'raster-dem',
        url: `https://api.maptiler.com/tiles/terrain-rgb/tiles.json?key=${MAPTILER_KEY}&fresh=${Date.now()}`,
        tileSize: 256
    });

    // Realistic terrain exaggeration
    map.setTerrain({ 
        source: 'terrain', 
        exaggeration: is3DEnabled ? 2.0 : 1.0 
    });
}

  // Performance monitoring
  function logPerformance() {
      const mem = window.performance && window.performance.memory;
      console.log(`Memory usage: ${mem ? (mem.usedJSHeapSize / 1048576).toFixed(2) + 'MB' : 'N/A'}`);
  }
  
  // Enhanced search with geocoding
  document.getElementById('search-button').addEventListener('click', () => {
      const location = document.getElementById('search-input').value.trim();
      if (!location) {
          alert('Please enter a location to search.');
          return;
      }
      
      document.getElementById('search-button').disabled = true;
      document.getElementById('search-button').textContent = "Searching...";
      
      fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(location)}&format=json&limit=1`)
          .then(response => response.json())
          .then(data => {
              if (data.length > 0) {
                  const { lon, lat, display_name } = data[0]; // Extract lon and lat
                  
                  // Clear previous search markers
                  currentMarkers = currentMarkers.filter(m => {
                      const shouldKeep = !m.getElement().classList.contains('search-marker');
                      if (!shouldKeep) m.remove();
                      return shouldKeep;
                  });
                  
                  // Create pulsing marker for search result
                  const markerElement = document.createElement('div');
                  markerElement.className = 'pulse-marker search-marker';
                  markerElement.style.backgroundColor = '#ff6d00';
                  
                  const marker = new maplibregl.Marker({
                      element: markerElement,
                      anchor: 'bottom'
                  })
                  .setLngLat([parseFloat(lon), parseFloat(lat)])
                  .setPopup(new maplibregl.Popup().setHTML(`<h3>${display_name}</h3>`))
                  .addTo(map);
                  
                  currentMarkers.push(marker);
                  
                  // Fly to location with cinematic animation
                  map.flyTo({
                      center: [parseFloat(lon), parseFloat(lat)],
                      zoom: 16,
                      pitch: is3DEnabled ? 60 : 0,
                      bearing: is3DEnabled ? -40 : 0,
                      speed: 1.2,
                      curve: 1.4,
                      essential: true
                  });
              } else {
                  alert('Location not found. Please try a different search term.');
              }
          })
          .catch(error => {
              console.error('Search error:', error);
              alert('An error occurred during search. Please try again.');
          })
          .finally(() => {
              document.getElementById('search-button').disabled = false;
              document.getElementById('search-button').textContent = "Search";
          });
  });
  
  // Handle Enter key in search
  document.getElementById('search-input').addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
          document.getElementById('search-button').click();
      }
  });