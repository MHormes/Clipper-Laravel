<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { ArrowLeft, MapPin } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

interface MarkerItem {
    id: string;
    lat: number;
    lon: number;
    image_data: string;
    series: {
        id: string;
        name: string;
        slug?: string;
    };
    series_number: number;
    location_bought: string;
}

const props = defineProps<{
    markers: MarkerItem[];
    tileConfig: {
        url: string;
        attribution: string;
    };
}>();

const mapEl = ref<HTMLDivElement | null>(null);
let map: L.Map | null = null;
let clusterLayer: L.MarkerClusterGroup | null = null;
const clipperMarkers: L.Marker[] = [];

delete (L.Icon.Default.prototype as unknown as { _getIconUrl?: unknown })._getIconUrl;

L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const escapeHtml = (value: string) =>
    value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

const buildPopupHtml = (marker: MarkerItem) => {
    const seriesUrl = route('series.show', {
        series: marker.series.id,
        slug: marker.series.slug,
    });

    return `
        <div class="map-popup-image-only">
            <img src="${escapeHtml(marker.image_data)}" alt="${escapeHtml(marker.series.name)} #${marker.series_number}" />
            <a href="${seriesUrl}" class="map-popup-link">Show Series</a>
        </div>
    `;
};

const buildMarkerHtml = (marker: MarkerItem) => `
    <div class="clipper-pin" title="${escapeHtml(marker.series.name)} #${marker.series_number}">
        <div class="clipper-pin-body">
            <img src="${escapeHtml(marker.image_data)}" alt="${escapeHtml(marker.series.name)} #${marker.series_number}" />
        </div>
        <div class="clipper-pin-tip"></div>
    </div>
`;

const getPinDimensions = (zoom: number) => {
    if (zoom <= 5) return { width: 18, height: 64, tip: 8 };
    if (zoom <= 7) return { width: 22, height: 78, tip: 9 };
    if (zoom <= 9) return { width: 26, height: 92, tip: 10 };
    if (zoom <= 11) return { width: 30, height: 106, tip: 11 };
    return { width: 34, height: 122, tip: 12 };
};

const buildClipperIcon = (marker: MarkerItem, zoom: number) => {
    const dims = getPinDimensions(zoom);
    const iconHeight = dims.height + Math.round(dims.tip / 2);

    return L.divIcon({
        html: `
            <div class="clipper-pin" style="--pin-w:${dims.width}px;--pin-h:${dims.height}px;--pin-tip:${dims.tip}px;">
                ${buildMarkerHtml(marker)}
            </div>
        `,
        className: 'clipper-pin-wrapper',
        iconSize: [dims.width, iconHeight],
        iconAnchor: [Math.round(dims.width / 2), iconHeight],
        popupAnchor: [0, -dims.height],
    });
};

const createClusterIcon = (cluster: L.MarkerCluster) => {
    const count = cluster.getChildCount();
    const size = count < 10 ? 34 : count < 50 ? 40 : 46;

    return L.divIcon({
        html: `<div class="cluster-badge" style="--cluster-size:${size}px;"><span>${count}</span></div>`,
        className: 'cluster-icon-wrapper',
        iconSize: [size, size],
    });
};

onMounted(async () => {
    await nextTick();

    if (!mapEl.value) return;

    map = L.map(mapEl.value, {
        zoomControl: true,
        minZoom: 2,
    });

    L.tileLayer(props.tileConfig.url, {
        attribution: props.tileConfig.attribution,
        maxZoom: 19,
    }).addTo(map);

    if (!props.markers.length) {
        map.setView([52.3676, 4.9041], 5);
        return;
    }

    clusterLayer = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        iconCreateFunction: createClusterIcon,
    });

    const bounds: L.LatLngTuple[] = [];
    const initialZoom = map.getZoom();

    for (const marker of props.markers) {
        const latLng: L.LatLngTuple = [marker.lat, marker.lon];
        bounds.push(latLng);

        const markerLayer = L.marker(latLng)
            .setIcon(buildClipperIcon(marker, initialZoom))
            .bindPopup(buildPopupHtml(marker), { maxWidth: 160, closeButton: false });

        clipperMarkers.push(markerLayer);
        clusterLayer.addLayer(markerLayer);
    }

    map.addLayer(clusterLayer);
    map.fitBounds(bounds, { padding: [40, 40] });

    map.on('zoomend', () => {
        if (!map) return;

        const currentZoom = map.getZoom();
        clipperMarkers.forEach((markerLayer, index) => {
            markerLayer.setIcon(buildClipperIcon(props.markers[index], currentZoom));
        });
    });
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
    }
    clusterLayer = null;
    clipperMarkers.length = 0;
});
</script>

<template>
    <Head title="Map View" />

    <AppLayout>
        <div class="w-full max-w-7xl mx-auto p-6">
            <div class="mb-8 flex items-center justify-between gap-4">
                <Link
                    :href="route('collection.index')"
                    class="inline-flex items-center gap-2 text-sm font-bold text-muted-content hover:text-primary transition-colors"
                >
                    <ArrowLeft class="w-4 h-4" />
                    BACK TO COLLECTION
                </Link>

                <div class="text-[10px] font-black uppercase tracking-widest text-muted-content">
                    {{ markers.length }} pinned clippers
                </div>
            </div>

            <div class="rounded-3xl border border-border-color bg-component-background p-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3 px-2">
                    <div>
                        <h1 class="text-2xl font-black uppercase tracking-tight">Collection Map</h1>
                        <p class="text-sm text-muted-content">Your collected clippers with saved coordinates.</p>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl border border-border-color bg-primary-background px-3 py-2">
                        <MapPin class="size-4 text-primary" />
                        <span class="text-xs font-black uppercase tracking-wider text-primary-content">Live Pins</span>
                    </div>
                </div>

                <div v-if="markers.length === 0" class="min-h-[560px] flex flex-col items-center justify-center rounded-2xl border border-dashed border-border-color bg-primary-background text-center px-6">
                    <MapPin class="size-14 text-muted-content mb-4" />
                    <h2 class="text-2xl font-black uppercase tracking-tight">No Coordinates Yet</h2>
                    <p class="mt-2 text-sm text-muted-content max-w-md">
                        Add a location to your collected clippers from the details modal, then they will appear as map pins here.
                    </p>
                </div>

                <div v-else ref="mapEl" class="h-[70vh] min-h-[560px] rounded-2xl border border-border-color overflow-hidden"></div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.leaflet-control-attribution) {
    background: color-mix(in srgb, var(--color-component-background) 92%, transparent);
    color: var(--color-muted-content);
    border-radius: 6px;
}

:deep(.leaflet-popup-content-wrapper) {
    background: var(--color-component-background);
    color: var(--color-primary-content);
    border: 1px solid var(--color-border-color);
    border-radius: 12px;
    box-shadow: 0 12px 24px color-mix(in srgb, var(--color-primary-content) 15%, transparent);
}

:deep(.leaflet-popup-tip) {
    background: var(--color-component-background);
    border: 1px solid var(--color-border-color);
}

:deep(.leaflet-popup-content) {
    margin: 8px;
}

:deep(.leaflet-container a.leaflet-popup-close-button) {
    color: var(--color-muted-content);
}

:deep(.leaflet-container a.leaflet-popup-close-button:hover) {
    color: var(--color-primary-content);
}

:deep(.map-popup-image-only) {
    width: 70px;
}

:deep(.map-popup-image-only img) {
    width: 100%;
    aspect-ratio: 1 / 4;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid var(--color-border-color);
}

:deep(.map-popup-link) {
    margin-top: 8px;
    display: inline-flex;
    width: 100%;
    justify-content: center;
    align-items: center;
    border-radius: 8px;
    padding: 8px 10px;
    background: var(--color-primary);
    color: var(--color-button-content);
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border: 1px solid color-mix(in srgb, var(--color-primary) 60%, var(--color-border-color) 40%);
}

:deep(.clipper-pin-wrapper) {
    background: transparent;
    border: 0;
}

:deep(.clipper-pin) {
    position: relative;
    width: var(--pin-w);
    height: calc(var(--pin-h) + (var(--pin-tip) / 2));
}

:deep(.clipper-pin-body) {
    width: 100%;
    height: var(--pin-h);
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid var(--color-primary);
    box-shadow: 0 4px 10px color-mix(in srgb, var(--color-primary) 30%, transparent);
    background: var(--color-component-background);
}

:deep(.clipper-pin-body img) {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

:deep(.clipper-pin-tip) {
    position: absolute;
    left: 50%;
    bottom: calc(var(--pin-tip) * -0.5);
    width: var(--pin-tip);
    height: var(--pin-tip);
    transform: translateX(-50%) rotate(45deg);
    background: var(--color-primary);
    border-radius: 1px;
    box-shadow: 0 2px 6px color-mix(in srgb, var(--color-primary) 35%, transparent);
}

:deep(.cluster-icon-wrapper) {
    background: transparent;
    border: 0;
}

:deep(.cluster-badge) {
    width: var(--cluster-size);
    height: var(--cluster-size);
    border-radius: 9999px;
    background: color-mix(in srgb, var(--color-primary) 85%, black 15%);
    border: 2px solid color-mix(in srgb, var(--color-primary) 60%, white 40%);
    box-shadow: 0 4px 12px color-mix(in srgb, var(--color-primary) 35%, transparent);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-button-content);
    font-weight: 900;
    font-size: 12px;
}

@media (max-width: 640px) {
    :deep(.map-popup-image-only) {
        width: 58px;
    }

    :deep(.leaflet-popup-content) {
        margin: 6px;
    }

    :deep(.map-popup-link) {
        margin-top: 6px;
        padding: 6px 8px;
        font-size: 10px;
    }
}
</style>
