import { cva } from "class-variance-authority"

export { default as NavigationMenu } from "./NavigationMenu.vue"
export { default as NavigationMenuContent } from "./NavigationMenuContent.vue"
export { default as NavigationMenuIndicator } from "./NavigationMenuIndicator.vue"
export { default as NavigationMenuItem } from "./NavigationMenuItem.vue"
export { default as NavigationMenuLink } from "./NavigationMenuLink.vue"
export { default as NavigationMenuList } from "./NavigationMenuList.vue"
export { default as NavigationMenuTrigger } from "./NavigationMenuTrigger.vue"
export { default as NavigationMenuViewport } from "./NavigationMenuViewport.vue"

export const navigationMenuTriggerStyle = cva(
  "group inline-flex h-9 w-max items-center justify-center rounded-md bg-primary-background px-4 py-2 text-sm font-medium hover:bg-muted-background hover:text-primary-content focus:bg-muted-background focus:text-primary-content disabled:pointer-events-none disabled:opacity-50 data-[state=open]:hover:bg-muted-background data-[state=open]:text-primary-content data-[state=open]:focus:bg-muted-background data-[state=open]:bg-accent/50 focus-visible:ring-primary-content/50 outline-none transition-[color,box-shadow] focus-visible:ring-[3px] focus-visible:outline-1",
)
