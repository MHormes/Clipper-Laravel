/**
 * Service for interacting with OpenStreetMap Nominatim API
 */
export const geocodingService = {
    async search(query) {
        if (!query || query.length < 3) return [];

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`,
                {
                    headers: {
                        'Accept-Language': 'en',
                        // Replace with your app name - helps OSM sysadmins
                        'User-Agent': 'Clipper-MS/1.0'
                    }
                }
            );

            if (!response.ok) throw new Error('Network response was not ok');

            return await response.json();
        } catch (error) {
            console.error("Geocoding Service Error:", error);
            return [];
        }
    },
    async reverse(lat, lon) {
        if (!lat || !lon) return null;

        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`,
                {
                    headers: {
                        'Accept-Language': 'en',
                        'User-Agent': 'Clipper-MS/1.0'
                    }
                }
            );

            if (!response.ok) throw new Error('Network response was not ok');

            return await response.json();
        } catch (error) {
            console.error("Reverse Geocoding Service Error:", error);
            return null;
        }
    }
};