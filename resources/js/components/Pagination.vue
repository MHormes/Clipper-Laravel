<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
defineProps<{ links: Array<any> }>();
function decodeLabel(label: string) {
  if (label == null) return "";
  const doc = new DOMParser().parseFromString(String(label), "text/html");
  return doc.documentElement.textContent ?? "";
}

</script>

<template>
  <div v-if="links.length > 3" class="mt-12 mb-6 flex justify-center gap-2">
    <template v-for="(link, k) in links" :key="k">
      <span
        v-if="link.url === null"
        class="px-5 py-2.5 rounded-xl text-sm border font-bold opacity-50 cursor-not-allowed bg-primary-background border-border-color"
      >
        {{ decodeLabel(link.label) }}
      </span>

      <Link
        v-else
        :href="link.url"
        class="px-5 py-2.5 rounded-xl text-sm border font-bold transition-all shadow-sm"
        :class="link.active
          ? 'bg-primary text-button-content border-primary'
          : 'bg-primary-background border-border-color hover:border-primary/50'"
      >
        {{ decodeLabel(link.label) }}
      </Link>
    </template>
  </div>
</template>
