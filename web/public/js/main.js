const map = L.map('map').setView([0.518389,25.205708], 14);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

const locationDotIcon = L.divIcon({
  className: 'location-dot',
  html: '<i class="fa-solid fa-location-dot"></i>',
  iconSize: [25, 41],
  iconAnchor: [12, 41]
});

L.control.scale({ metric: true, imperial: false }).addTo(map);

let defaultMarker = L.marker([0.518389, 25.205708], { icon: locationDotIcon }).addTo(map);

const gridLayer = L.layerGroup();
const GRID_Z = 14;

function tile2lon(x, z) { return x / Math.pow(2, z) * 360 - 180; }
function tile2lat(y, z) {
  var n = Math.PI - 2 * Math.PI * y / Math.pow(2, z);
  return 180 / Math.PI * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n)));
}

function drawGrid() {
  if (map.getZoom() < 12) {
    gridLayer.clearLayers();
    return;
  }
  var bounds = map.getBounds();

  var xMin = Math.floor((bounds.getWest() + 180) / 360 * Math.pow(2, GRID_Z));
  var xMax = Math.floor((bounds.getEast() + 180) / 360 * Math.pow(2, GRID_Z));
  var yMin = Math.floor((1 - Math.log(Math.tan(bounds.getNorth()*Math.PI/180) + 1/Math.cos(bounds.getNorth()*Math.PI/180)) / Math.PI) / 2 * Math.pow(2, GRID_Z));
  var yMax = Math.floor((1 - Math.log(Math.tan(bounds.getSouth()*Math.PI/180) + 1/Math.cos(bounds.getSouth()*Math.PI/180)) / Math.PI) / 2 * Math.pow(2, GRID_Z));

  for (var x = xMin; x <= xMax + 1; x++) {
    L.polyline([[tile2lat(yMin, GRID_Z), tile2lon(x, GRID_Z)], [tile2lat(yMax+1, GRID_Z), tile2lon(x, GRID_Z)]], {color:'red', weight:1}).addTo(gridLayer);
  }
  for (var y = yMin; y <= yMax + 1; y++) {
    L.polyline([[tile2lat(y, GRID_Z), tile2lon(xMin, GRID_Z)], [tile2lat(y, GRID_Z), tile2lon(xMax+1, GRID_Z)]], {color:'red', weight:1}).addTo(gridLayer);
  }
}

map.on('moveend', drawGrid);
drawGrid();

const markers = {};
const tracks = {};
const trackPoints = {};
const trackVisible = {};
const lastSeen = {};
const lastSpeed = {};
const lastTime = {};
let lastLatLng = null;
let follow = true;
let activeTrackerId = null;

const CrosshairControl = L.Control.extend({
  options: { position: 'topleft' },
  onAdd: function () {
    const container = L.DomUtil.create('div', 'leaflet-bar crosshair-btn active');
    container.innerHTML = '<i class="fa-solid fa-crosshairs"></i>';
    L.DomEvent.disableClickPropagation(container);
    L.DomEvent.on(container, 'click', () => {
      follow = !follow;
      container.classList.toggle('active', follow);
      if (follow && lastLatLng) map.panTo(lastLatLng);
    });
    this._container = container;
    return container;
  }
});
const crosshairControl = new CrosshairControl();
map.addControl(crosshairControl);

const MenuControl = L.Control.extend({
  options: { position: 'topright' },
  onAdd: function () {
    const container = L.DomUtil.create('div', 'leaflet-bar menu-control');
    const menuTemplate = document.getElementById('menuControl');
    container.appendChild(menuTemplate.content.cloneNode(true));
    L.DomEvent.disableClickPropagation(container);
    const toggle = container.querySelector('.menu-toggle');
    L.DomEvent.on(toggle, 'click', () => {
      container.classList.toggle('expanded');
    });
    return container;
  }
});

const menuControl = new MenuControl();
map.addControl(menuControl);
map.removeLayer(gridLayer);

map.on('dragstart', () => {
  follow = false;
  crosshairControl._container.classList.remove('active');
});

function timeAgo(epochSeconds) {
  let seconds = Math.floor(Date.now() / 1000) - epochSeconds;

  const days = Math.floor(seconds / 86400);
  seconds -= days * 86400;
  const hours = Math.floor(seconds / 3600);
  seconds -= hours * 3600;
  const minutes = Math.floor(seconds / 60);
  seconds -= minutes * 60;

  if (days > 0) return `${days}d ${hours}h ${minutes}m ${seconds}s ago`;
  if (hours > 0) return `${hours}h ${minutes}m ${seconds}s ago`;
  if (minutes > 0) return `${minutes}m ${seconds}s ago`;
  return `${seconds}s ago`;
}

function openInfoPanel(trackerId) {
  activeTrackerId = trackerId;
  document.getElementById('infoTitle').textContent = `Tracker ${trackerId}`;
  document.getElementById('trackToggleBtn').textContent = trackVisible[trackerId] ? 'Hide' : 'Show';
  updateInfoPanel(trackerId);
  document.getElementById('infoPanel').classList.add('visible');
}

function updateInfoPanel(trackerId) {
  document.getElementById('infoLastSeen').textContent = timeAgo(lastSeen[trackerId]);
  document.getElementById('infoSpeed').textContent = lastSpeed[trackerId] !== null && lastSpeed[trackerId] !== undefined ? `${lastSpeed[trackerId].toFixed(1)} km/h` : 'N/A';
}

setInterval(() => {
  if (activeTrackerId !== null) {
    document.getElementById('infoLastSeen').textContent = timeAgo(lastSeen[activeTrackerId]);
  }
}, 1000);

document.getElementById('infoClose').addEventListener('click', () => {
  document.getElementById('infoPanel').classList.remove('visible');
});

document.getElementById('trackToggleBtn').addEventListener('click', () => {
  if (activeTrackerId === null) return;
  trackVisible[activeTrackerId] = !trackVisible[activeTrackerId];
  if (trackVisible[activeTrackerId]) {
    tracks[activeTrackerId].addTo(map);
  } else {
    map.removeLayer(tracks[activeTrackerId]);
  }
  document.getElementById('trackToggleBtn').textContent = trackVisible[activeTrackerId] ? 'Hide' : 'Show';
});

let isEditingName = false;

document.getElementById('saveBtn').addEventListener('click', () => {
  if (activeTrackerId === null || !trackPoints[activeTrackerId]) return;

  if (isEditingName) return;

  const inputContainer = document.getElementById('nameInputContainer');
  const buttonsContainer = document.getElementById('buttonsContainer');
  
  buttonsContainer.classList.add('hidden');
  
  setTimeout(() => {
    inputContainer.classList.add('visible');
  }, 300);
  
  isEditingName = true;
});

document.getElementById('saveNameBtn').addEventListener('click', () => {
  if (activeTrackerId === null || !trackPoints[activeTrackerId]) return;

  const input = document.getElementById('activityNameInput');
  let name = input.value.trim();

  if (name === '') {
    const firstPointTime = trackPoints[activeTrackerId][0][2];
    const date = new Date(firstPointTime * 1000);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    name = `Activity on ${year}/${month}/${day}`;
  } else {
    name = name.substring(0, 50);
  }

  const points = trackPoints[activeTrackerId];

  const firstPointTime = points.length > 0 ? points[0][2] : null;
  const lastPointTime = points.length > 0 ? points[points.length - 1][2] : null;

  const duration = lastPointTime - firstPointTime;

  const saveData = {
    tracker_id: activeTrackerId,
    name: name,
    start_time: firstPointTime,
    end_time: lastPointTime,
    duration: duration,
    waypoints: points.map((point, idx) => ({
      lat: point[0],
      lon: point[1],
      time_recorded: point[2],
      speed: lastSpeed[activeTrackerId]
    }))
  };

  fetch('api/save_activity.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(saveData)
  })
  .then(res => res.json())
  .then(data => {
    const nameInputContainer = document.getElementById('nameInputContainer');
    const buttonsContainer = document.getElementById('buttonsContainer');
    const statusContainer = document.getElementById('statusContainer');
    
    nameInputContainer.classList.remove('visible');
    buttonsContainer.classList.add('hidden');
    
    statusContainer.innerHTML = data.success 
      ? '<span class="status-success"><i class="fa-solid fa-check"></i> Track saved successfully</span>'
      : '<span class="status-error"><i class="fa-solid fa-xmark"></i> Failed to save track: ' + (data.error || 'Unknown error') + '</span>';
    
    statusContainer.classList.add('visible');
  })
  .catch(err => {
    const nameInputContainer = document.getElementById('nameInputContainer');
    const buttonsContainer = document.getElementById('buttonsContainer');
    const statusContainer = document.getElementById('statusContainer');
    
    nameInputContainer.classList.remove('visible');
    buttonsContainer.classList.add('hidden');
    
    statusContainer.innerHTML = '<span class="status-error"><i class="fa-solid fa-xmark"></i> Error saving track: ' + err.message + '</span>';
    
    statusContainer.classList.add('visible');
  });
});

function resetInfoPanel() {
  const inputContainer = document.getElementById('nameInputContainer');
  const buttonsContainer = document.getElementById('buttonsContainer');
  const statusContainer = document.getElementById('statusContainer');
  
  inputContainer.classList.remove('visible');
  statusContainer.classList.remove('visible');
  
  setTimeout(() => {
    buttonsContainer.classList.remove('hidden');
  }, 300);
  
  document.getElementById('activityNameInput').value = '';
  isEditingName = false;
}

document.getElementById('clearTrackBtn').addEventListener('click', () => {
  if (activeTrackerId === null) return;

  if (!confirm('Are you sure?')) return;

  delete trackPoints[activeTrackerId];
  delete tracks[activeTrackerId];
  delete trackVisible[activeTrackerId];

  if (markers[activeTrackerId]) {
    map.removeLayer(markers[activeTrackerId]);
    delete markers[activeTrackerId];
  }

  fetch(`api/delete_track.php?id=${activeTrackerId}`, {
    method: 'DELETE'
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Track cleared successfully!');
      } else {
        alert('Failed to clear track: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(err => {
      alert('Error clearing track: ' + err.message);
    });
});

document.getElementById('activitiesBtn').addEventListener('click', () => {
  window.location.href = 'activities.php';
});

document.getElementById('explorerTilesCheckbox').addEventListener('change', (e) => {
  if (e.target.checked) {
    if (map.getZoom() >= 12) {
      gridLayer.addTo(map);
    } else {
      e.target.checked = false;
    }
  } else {
    map.removeLayer(gridLayer);
  }
});

function addPoint(trackerId, lat, lon, timeRecorded, speedCalculated, pan) {
  if (defaultMarker) {
    map.removeLayer(defaultMarker);
    defaultMarker = null;
  }

  const latlng = [lat, lon];
  lastLatLng = latlng;
  lastSeen[trackerId] = timeRecorded;
  lastSpeed[trackerId] = speedCalculated;

  if (!trackPoints[trackerId]) {
    trackPoints[trackerId] = [];
  }
  trackPoints[trackerId].push([lat, lon, timeRecorded]);

  if (tracks[trackerId]) {
    tracks[trackerId].setLatLngs(trackPoints[trackerId]);
  } else {
    trackVisible[trackerId] = false;
    tracks[trackerId] = L.polyline(trackPoints[trackerId], {
      color: '#FF0077',
      opacity: 0.75
    });
  }

  if (markers[trackerId]) {
    markers[trackerId].setLatLng(latlng);
  } else {
    markers[trackerId] = L.marker(latlng, { icon: locationDotIcon }).addTo(map);
    markers[trackerId].on('click', () => openInfoPanel(trackerId));
  }

  if (activeTrackerId === trackerId) {
    updateInfoPanel(trackerId);
  }

  if (pan && follow) map.panTo(latlng);
}

const ws = new WebSocket('ws://localhost:8081');

ws.onmessage = (event) => {
  const pos = JSON.parse(event.data);

  const zoom = map.getZoom();
  addPoint(pos.tracker_id, pos.lat, pos.lon, pos.time_recorded, pos.speed_calculated, true);
  const hash = `#${zoom}/${pos.lat.toFixed(6)}/${pos.lon.toFixed(6)}`;
  history.replaceState(null, '', `/${hash}`);
};

fetch('api/track.php')
  .then(res => res.json())
  .then(data => {
    for (const trackerId in data) {
      data[trackerId].forEach(p =>
        addPoint(trackerId, p.lat, p.lon, p.time_recorded, p.speed_calculated, false)
      );
    }
    if (lastLatLng) map.panTo(lastLatLng);
  });
