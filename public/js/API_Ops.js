/**
 * API_Ops.js
 * Sends AJAX request to Laravel's /api/weather route (WeatherController).
 * Gets user location first, falls back to default city.
 */

async function loadWeather(lat = null, lon = null) {
    const weatherBox = document.getElementById('weatherBox');
    if (!weatherBox) return;

    weatherBox.innerHTML = `
        <div class="flex items-center gap-2 text-xs text-slate-400 px-3 py-2">
            <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            Loading weather...
        </div>`;

    try {
        // Use Laravel route from window.routes
        let url = (window.routes && window.routes.weather) || '/api/weather';
        if (lat !== null && lon !== null) {
            url += `?lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lon)}`;
        }

        const response = await fetch(url);
        if (!response.ok) throw new Error(`Server error: ${response.status}`);
        const data = await response.json();

        if (data.error) {
            weatherBox.innerHTML = `
                <div class="mx-3 my-2 p-2 bg-red-50 border border-red-200 rounded-lg text-red-600 text-xs">
                    ⚠ ${data.error}
                </div>`;
            return;
        }

        weatherBox.innerHTML = `
            <div class="mx-3 my-2 rounded-xl bg-gradient-to-br from-blue-50 to-slate-50 border border-slate-200 p-3 text-xs leading-5 text-slate-700">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-semibold text-slate-800">🌤 ${data.name}</span>
                    <span class="text-slate-400">${data.description}</span>
                </div>
                <div class="flex gap-3 text-slate-500 flex-wrap">
                    <span>🌡 ${data.temp}°C</span>
                    <span>💧 ${data.humidity}%</span>
                    <span>💨 ${data.wind_speed} km/h</span>
                </div>
            </div>`;
    } catch (err) {
        console.error('Weather error:', err);
        weatherBox.innerHTML = `
            <div class="mx-3 my-2 p-2 bg-red-50 border border-red-200 rounded-lg text-red-600 text-xs">
                ⚠ Unable to load weather data.
            </div>`;
    }
}

function initWeather() {
    if (!navigator.geolocation) { loadWeather(); return; }

    navigator.geolocation.getCurrentPosition(
        pos => loadWeather(pos.coords.latitude, pos.coords.longitude),
        ()  => loadWeather(),
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

document.addEventListener('DOMContentLoaded', initWeather);