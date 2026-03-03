<script lang="ts">
import { h, type HTMLAttributes as VueHTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { Primitive, type PrimitiveProps } from "reka-ui"
import { ChevronRight } from "lucide-vue-next"

export const BreadcrumbItem = {
  name: "BreadcrumbItem",
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => h('li', { 'data-slot': 'breadcrumb-item', class: cn('inline-flex items-center gap-1.5', props.class) }, slots)
  }
}

export const BreadcrumbLink = {
  name: "BreadcrumbLink",
  setup(props: PrimitiveProps & { class?: VueHTMLAttributes }, { slots }: any) {
    return () => h(Primitive, {
      'data-slot': 'breadcrumb-link',
      as: props.as || 'a',
      asChild: props.asChild,
      class: cn('hover:text-primary-content transition-colors', props.class)
    }, slots)
  }
}

export const BreadcrumbList = {
  name: "BreadcrumbList",
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => h('ol', { 'data-slot': 'breadcrumb-list', class: cn('text-muted-content flex flex-wrap items-center gap-1.5 text-sm break-words sm:gap-2.5', props.class) }, slots)
  }
}

export const BreadcrumbPage = {
  name: "BreadcrumbPage",
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => h('span', {
      'data-slot': 'breadcrumb-page',
      role: 'link', 'aria-disabled': 'true', 'aria-current': 'page',
      class: cn('text-primary-content font-normal', props.class)
    }, slots)
  }
}

export const BreadcrumbSeparator = {
  name: "BreadcrumbSeparator",
  setup(props: { class?: VueHTMLAttributes }, { slots }: any) {
    return () => h('li', {
      'data-slot': 'breadcrumb-separator',
      role: 'presentation', 'aria-hidden': 'true',
      class: cn('[&>svg]:size-3.5', props.class)
    }, slots.default ? slots.default() : [h(ChevronRight)])
  }
}
</script>

<script lang="ts" setup>
import type { HTMLAttributes } from "vue"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <nav
    aria-label="breadcrumb"
    data-slot="breadcrumb"
    :class="props.class"
  >
    <slot />
  </nav>
</template>
