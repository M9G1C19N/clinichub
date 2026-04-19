<script setup>
import { usePage } from '@inertiajs/vue3'
import { watch, ref } from 'vue'

const page = usePage()
const visible = ref(false)
const message = ref('')
const type = ref('success')

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        message.value = flash.success
        type.value = 'success'
        visible.value = true
        setTimeout(() => visible.value = false, 4000)
    } else if (flash?.error) {
        message.value = flash.error
        type.value = 'error'
        visible.value = true
        setTimeout(() => visible.value = false, 5000)
    }
}, { deep: true })
</script>

<template>
    <transition name="toast">
        <div v-if="visible"
            :class="['fixed bottom-6 right-6 z-[100] flex items-center gap-3 px-4 py-3.5 rounded-2xl shadow-lg max-w-sm bg-white border',
                type === 'success' ? 'border-emerald-200' : 'border-rose-200']">
            <svg v-if="type === 'success'" class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <svg v-else class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm font-medium text-slate-700 flex-1">{{ message }}</p>
            <button @click="visible = false" class="text-slate-400 hover:text-slate-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </transition>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px); }
</style>
