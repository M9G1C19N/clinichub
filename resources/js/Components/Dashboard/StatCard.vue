<script setup>
import { computed } from 'vue'

const props = defineProps({
    label:   { type: String, required: true },
    value:   { type: [String, Number], required: true },
    icon:    { type: [Object, Function], default: null },
    color:   { type: String, default: '#1B4F9B' },
    sub:     { type: String, default: null },
    trend:   { type: Number, default: null },
    link:    { type: String, default: null },
    loading: { type: Boolean, default: false },
    danger:  { type: Boolean, default: false },
    warning: { type: Boolean, default: false },
    success: { type: Boolean, default: false },
})

const bgColor = computed(() => {
    if (props.danger)  return '#FEE2E2'
    if (props.warning) return '#FEF3C7'
    if (props.success) return '#D1FAE5'
    return props.color + '18'
})

const iconColor = computed(() => {
    if (props.danger)  return '#DC2626'
    if (props.warning) return '#D97706'
    if (props.success) return '#059669'
    return props.color
})
</script>

<template>
    <component
        :is="link ? 'a' : 'div'"
        :href="link"
        class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-start gap-4 hover:shadow-md transition-all duration-200 group"
        :class="{ 'cursor-pointer': link, 'border-red-100': danger, 'border-amber-100': warning, 'border-emerald-100': success }"
    >
        <!-- Icon box -->
        <div
            class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110"
            :style="{ background: bgColor }"
        >
            <component v-if="icon" :is="icon" class="w-5 h-5" :style="{ color: iconColor }" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 truncate">{{ label }}</p>
            <div v-if="loading" class="h-7 w-16 bg-slate-100 rounded animate-pulse mt-1" />
            <p v-else class="text-2xl font-black mt-0.5 text-[#0F2044] tabular-nums">{{ value }}</p>
            <p v-if="sub" class="text-xs text-slate-400 mt-0.5 truncate">{{ sub }}</p>
        </div>

        <!-- Trend badge -->
        <div
            v-if="trend !== null && trend !== undefined"
            class="text-xs font-bold px-2 py-1 rounded-full flex-shrink-0 mt-0.5"
            :class="trend >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'"
        >
            {{ trend >= 0 ? '↑' : '↓' }} {{ Math.abs(trend) }}%
        </div>
    </component>
</template>
