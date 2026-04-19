<script setup>
import { VISIT_TYPE_BADGE } from '@/config/visitTypes.js'
defineProps({ type: String })
</script>

<template>
    <span class="text-xs font-semibold px-2 py-0.5 rounded"
        :style="{
            background: VISIT_TYPE_BADGE[type]?.bg ?? '#f1f5f9',
            color:      VISIT_TYPE_BADGE[type]?.color ?? '#475569'
        }">
        {{ VISIT_TYPE_BADGE[type]?.label ?? type }}
    </span>
</template>
