<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ member: boolean }>()
const greeting = computed(() => props.member ? 'Bonjour membre' : 'Bonjour visiteur')
</script>

<template>
  <p>{{ greeting }}</p>
</template>
