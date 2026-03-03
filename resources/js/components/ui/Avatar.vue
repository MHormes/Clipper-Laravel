<script lang="ts">
import { h } from "vue"
import { AvatarImage as RekaAvatarImage, AvatarFallback as RekaAvatarFallback, type AvatarImageProps, type AvatarFallbackProps } from "reka-ui"
import { reactiveOmit } from "@vueuse/core"
import { cn } from "@/lib/utils"

export const AvatarImage = {
  name: "AvatarImage",
  setup(props: AvatarImageProps, { slots }: any) {
    return () => h(RekaAvatarImage, {
      'data-slot': 'avatar-image',
      ...props,
      class: 'aspect-square size-full'
    }, slots)
  }
}

export const AvatarFallback = {
  name: "AvatarFallback",
  setup(props: AvatarFallbackProps & { class?: HTMLAttributes["class"] }, { slots }: any) {
    const delegatedProps = reactiveOmit(props, "class")
    return () => h(RekaAvatarFallback, {
      'data-slot': 'avatar-fallback',
      ...delegatedProps,
      class: cn('bg-muted-background flex size-full items-center justify-center rounded-full', props.class)
    }, slots)
  }
}
</script>

<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { AvatarRoot } from "reka-ui"
import { cn as utilCn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <AvatarRoot
    data-slot="avatar"
    :class="utilCn('relative flex size-8 shrink-0 overflow-hidden rounded-full', props.class)"
  >
    <slot />
  </AvatarRoot>
</template>
