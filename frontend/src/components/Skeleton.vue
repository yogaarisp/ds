<template>
  <div 
    :class="[
      'animate-pulse bg-slate-200',
      variant === 'circle' ? 'rounded-full' : 'rounded-lg',
      customClass
    ]"
    :style="{ width, height }"
  ></div>
</template>

<script setup>
defineProps({
  width: {
    type: String,
    default: '100%'
  },
  height: {
    type: String,
    default: '1rem'
  },
  variant: {
    type: String,
    default: 'rect' // circle | rect
  },
  customClass: {
    type: String,
    default: ''
  }
})
</script>
