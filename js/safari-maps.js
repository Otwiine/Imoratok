/* ==========================================================================
   Imoratok Safaris - Interactive Safari Area & Route Maps (Leaflet + OSM)
   ========================================================================== */

(function () {
  const SAFARI_MAPS = {
    'bwindi-3day': {
      connectLine: true,
      stops: [
        { name: 'Entebbe / Kampala', label: 'Start / End', coords: [0.0512, 32.4637], note: 'Arrival & departure gateway; scenic equator stopover en route.' },
        { name: 'Bwindi Impenetrable NP', label: 'Days 1–3', coords: [-1.0500, 29.6667], note: 'UNESCO rainforest home to wild mountain gorillas.' }
      ]
    },
    'primates-8day': {
      connectLine: true,
      stops: [
        { name: 'Entebbe', label: 'Day 1 & 8', coords: [0.0512, 32.4637], note: 'International arrival and departure.' },
        { name: 'Kibale Forest NP', label: 'Days 2–3', coords: [0.5000, 30.3667], note: 'Chimpanzee tracking & Bigodi Wetland Sanctuary.' },
        { name: 'Queen Elizabeth NP', label: 'Days 4–5', coords: [-0.1936, 29.9936], note: 'Kasenyi plains game drive & Kazinga Channel boat cruise.' },
        { name: 'Bwindi Impenetrable NP', label: 'Day 6', coords: [-1.0500, 29.6667], note: 'Mountain gorilla tracking in ancient rainforest.' },
        { name: 'Lake Bunyonyi', label: 'Day 7', coords: [-1.2939, 29.9144], note: 'Canoeing & relaxation among terraced Kigezi hills.' }
      ]
    },
    'birding-6day': {
      connectLine: true,
      stops: [
        { name: 'Entebbe / Kampala', label: 'Arrival & Day 6', coords: [0.3136, 32.5811], note: 'Gateway with Mpigi drum makers & equator stops.' },
        { name: 'Lake Mburo NP', label: 'Day 1', coords: [-0.6289, 30.9469], note: 'Boat birding for African Finfoot & Papyrus Gonolek.' },
        { name: 'Mgahinga Gorilla NP', label: 'Days 2–3', coords: [-1.3767, 29.6433], note: 'Virunga volcanoes & Albertine Rift endemics.' },
        { name: 'Bwindi (Ruhija & Buhoma)', label: 'Days 4–5', coords: [-1.0333, 29.7667], note: 'Premier montane forest birding trails.' }
      ]
    },
    'murchison-5day': {
      connectLine: true,
      stops: [
        { name: 'Entebbe / Kampala', label: 'Start / End', coords: [0.0512, 32.4637], note: 'Departure & return transfer.' },
        { name: 'Ziwa Rhino Sanctuary', label: 'Day 1', coords: [1.4833, 32.0833], note: 'On-foot southern white rhino tracking.' },
        { name: 'Murchison Falls NP', label: 'Days 2–3 & 5', coords: [2.2750, 31.6833], note: 'Northern savannah game drives, Nile boat cruise & Top of Falls.' },
        { name: 'Budongo Forest', label: 'Day 4', coords: [1.7250, 31.5417], note: 'Wild chimpanzee tracking in primary mahogany forest.' }
      ]
    },
    'qenp-3day': {
      connectLine: true,
      stops: [
        { name: 'Kampala / Entebbe', label: 'Day 1 & 3', coords: [0.3136, 32.5811], note: 'Departure & return via Equator crossing.' },
        { name: 'Queen Elizabeth NP', label: 'Days 1–2', coords: [-0.1936, 29.9936], note: 'Kasenyi plains game drives & Kazinga Channel boat cruise.' }
      ]
    },
    'mburo-2day': {
      connectLine: true,
      stops: [
        { name: 'Kampala / Entebbe', label: 'Start / End', coords: [0.3136, 32.5811], note: '4–5 hour scenic transfer via the Equator.' },
        { name: 'Lake Mburo NP', label: 'Days 1–2', coords: [-0.6289, 30.9469], note: 'Lake boat ride, night game drive & morning walking safari.' }
      ]
    },
    'cultural-19day': {
      connectLine: true,
      stops: [
        { name: 'Entebbe', label: 'Day 1 & 19', coords: [0.0512, 32.4637], note: 'Arrival & departure gateway.' },
        { name: 'Sipi Falls (Mt. Elgon)', label: 'Days 2–3', coords: [1.3375, 34.3792], note: 'Waterfalls & Bugisu Arabica coffee farmers.' },
        { name: 'Moroto & Karamoja', label: 'Days 4–5', coords: [2.5345, 34.6666], note: 'Karamojong Manyattas, Tepeth & Nakapelemoru village.' },
        { name: 'Kidepo Valley NP', label: 'Days 6–8', coords: [3.9000, 33.8667], note: 'Remote Narus Valley wildlife & Ik community.' },
        { name: 'Murchison Falls NP', label: 'Days 9–11', coords: [2.2750, 31.6833], note: 'Nile boat cruise, savannah drives & Budongo chimps.' },
        { name: 'Kibale Forest NP', label: 'Days 12–13', coords: [0.5000, 30.3667], note: 'Chimpanzee tracking & Bigodi community walk.' },
        { name: 'Queen Elizabeth NP', label: 'Days 14–15', coords: [-0.1936, 29.9936], note: 'Kazinga Channel & Ishasha tree-climbing lions.' },
        { name: 'Bwindi Impenetrable NP', label: 'Days 16–17', coords: [-1.0500, 29.6667], note: 'Gorilla tracking & Batwa heritage experience.' },
        { name: 'Lake Mburo NP', label: 'Day 18', coords: [-0.6289, 30.9469], note: 'Walking safari & Ankole long-horned cattle.' }
      ]
    },
    'adventure-uganda': {
      connectLine: false,
      stops: [
        { name: 'Jinja (Source of the Nile)', label: 'White-Water Rafting', coords: [0.4390, 33.2032], note: 'Grade 5 Victoria Nile rapids, kayaking & bungee jumping.' },
        { name: 'Sipi Falls (Mt. Elgon)', label: 'Hiking & Abseiling', coords: [1.3375, 34.3792], note: 'Cliffside waterfalls & mountain trails in eastern Uganda.' },
        { name: 'Rwenzori Mountains NP', label: 'Alpine Mountaineering', coords: [0.3867, 29.8717], note: 'Equatorial glaciers & Margherita Peak (5,109m).' },
        { name: 'Murchison Falls (River Nile)', label: 'Sport Angling', coords: [2.2750, 31.6833], note: 'Catch-and-release Nile Perch fishing below the falls.' }
      ]
    }
  };

  function createPinIcon(index) {
    return L.divIcon({
      className: 'safari-map-pin-wrap',
      html: `<span class="safari-map-pin">${index}</span>`,
      iconSize: [28, 28],
      iconAnchor: [14, 14],
      popupAnchor: [0, -14]
    });
  }

  function initSafariMaps() {
    if (typeof L === 'undefined') return;

    document.querySelectorAll('.safari-map-container[data-safari-map]').forEach((el) => {
      const key = el.getAttribute('data-safari-map');
      const config = SAFARI_MAPS[key];
      if (!config || !config.stops.length) return;

      const terrainLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        maxZoom: 17,
        attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors, SRTM | Map style: &copy; <a href="https://opentopomap.org" target="_blank" rel="noopener">OpenTopoMap</a>'
      });

      const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 18,
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics'
      });

      const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
      });

      const map = L.map(el, {
        scrollWheelZoom: false,
        attributionControl: true,
        layers: [streetLayer]
      });

      L.control.layers(
        {
          'Street': streetLayer,
          'Terrain': terrainLayer,
          'Satellite': satelliteLayer
        },
        null,
        { position: 'topright' }
      ).addTo(map);

      const latLngs = [];

      config.stops.forEach((stop, idx) => {
        latLngs.push(stop.coords);
        const marker = L.marker(stop.coords, { icon: createPinIcon(idx + 1) }).addTo(map);
        marker.bindPopup(
          `<div class="safari-map-popup">
            <span class="safari-map-popup-tag">${stop.label}</span>
            <strong>${stop.name}</strong>
            <p>${stop.note}</p>
          </div>`
        );
      });

      if (config.connectLine && latLngs.length > 1) {
        L.polyline(latLngs, {
          color: '#1b382b',
          weight: 3,
          opacity: 0.85,
          dashArray: '8, 6'
        }).addTo(map);
      }

      const bounds = L.latLngBounds(latLngs);
      map.fitBounds(bounds, { padding: [42, 42], maxZoom: 9 });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSafariMaps);
  } else {
    initSafariMaps();
  }
})();

