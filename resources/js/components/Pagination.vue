<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

const props = defineProps<{ links: PaginationLink[] }>();

function decodeLabel(label: string) {
  if (label == null) return "";
  const doc = new DOMParser().parseFromString(String(label), "text/html");
  return doc.documentElement.textContent ?? "";
}

const normalizedLinks = computed(() =>
  props.links.map((link) => ({
    ...link,
    text: decodeLabel(link.label).trim(),
  }))
);

const pageLinks = computed(() =>
  normalizedLinks.value.filter((link) => /^\d+$/.test(link.text))
);

const previousLink = computed(() =>
  normalizedLinks.value.find((link) => link.text.toLowerCase().includes('previous'))
);

const nextLink = computed(() =>
  normalizedLinks.value.find((link) => link.text.toLowerCase().includes('next'))
);

const mobilePageLinks = computed(() => {
  if (pageLinks.value.length <= 3) {
    return pageLinks.value;
  }

  const currentIndex = pageLinks.value.findIndex((link) => link.active);
  const safeIndex = currentIndex === -1 ? 0 : currentIndex;
  const startIndex = Math.min(
    Math.max(safeIndex - 1, 0),
    Math.max(pageLinks.value.length - 3, 0)
  );

  return pageLinks.value.slice(startIndex, startIndex + 3);
});
</script>

<template>
  <div v-if="links.length > 3" class="mt-12 mb-6">
    <div class="sm:hidden">
      <div class="flex items-center justify-center gap-2">
        <span
          v-if="previousLink?.url === null"
          class="min-w-20 rounded-xl border border-border-color bg-primary-background px-3 py-2.5 text-center text-sm font-bold opacity-50"
        >
          Prev
        </span>

        <Link
          v-else-if="previousLink?.url"
          :href="previousLink.url"
          class="min-w-20 rounded-xl border border-border-color bg-primary-background px-3 py-2.5 text-center text-sm font-bold transition-all shadow-sm hover:border-primary/50"
        >
          Prev
        </Link>

        <template v-for="link in mobilePageLinks" :key="link.label">
          <span
            v-if="link.url === null"
            class="min-w-11 rounded-xl border border-border-color bg-primary-background px-3 py-2.5 text-center text-sm font-bold opacity-50"
          >
            {{ link.text }}
          </span>

          <Link
            v-else
            :href="link.url"
            class="min-w-11 rounded-xl border px-3 py-2.5 text-center text-sm font-bold transition-all shadow-sm"
            :class="link.active
              ? 'border-primary bg-primary text-button-content'
              : 'border-border-color bg-primary-background hover:border-primary/50'"
          >
            {{ link.text }}
          </Link>
        </template>

        <span
          v-if="nextLink?.url === null"
          class="min-w-20 rounded-xl border border-border-color bg-primary-background px-3 py-2.5 text-center text-sm font-bold opacity-50"
        >
          Next
        </span>

        <Link
          v-else-if="nextLink?.url"
          :href="nextLink.url"
          class="min-w-20 rounded-xl border border-border-color bg-primary-background px-3 py-2.5 text-center text-sm font-bold transition-all shadow-sm hover:border-primary/50"
        >
          Next
        </Link>
      </div>
    </div>

    <div class="hidden flex-wrap justify-center gap-2 sm:flex">
      <template v-for="(link, k) in links" :key="k">
        <span
          v-if="link.url === null"
          class="rounded-xl border border-border-color bg-primary-background px-5 py-2.5 text-sm font-bold opacity-50 cursor-not-allowed"
        >
          {{ decodeLabel(link.label) }}
        </span>

        <Link
          v-else
          :href="link.url"
          class="rounded-xl border px-5 py-2.5 text-sm font-bold transition-all shadow-sm"
          :class="link.active
            ? 'border-primary bg-primary text-button-content'
            : 'border-border-color bg-primary-background hover:border-primary/50'"
        >
          {{ decodeLabel(link.label) }}
        </Link>
      </template>
    </div>
  </div>
</template>
