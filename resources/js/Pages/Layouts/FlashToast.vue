<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed, watch, ref } from 'vue'
import { CheckCircle, XCircle, X } from 'lucide-vue-next'

const page = usePage()
const visible = ref(false)
const message = ref('')
const type = ref('success')

watch(
    () => page.props.flash,
    (flash) => {
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
    },
    { deep: true }
)
</script>

<template>
    <transition name="toast">
        <div
            v-if="visible"
            :class="[
                'fixed bottom-6 right-6 z-[100] flex items-center gap-3 px-4 py-3.5 rounded-2xl shadow-card max-w-sm',
                type === 'success' ? 'bg-white border border-emerald-200' : 'bg-white border border-rose-200'
            ]"
        >
            <CheckCircle v-if="type === 'success'" class="w-5 h-5 text-emerald-500 flex-shrink-0" />
            <XCircle v-else class="w-5 h-5 text-rose-500 flex-shrink-0" />
            <p class="text-sm font-medium text-slate-700 flex-1">{{ message }}</p>
            <button @click="visible = false" class="text-slate-400 hover:text-slate-600 ml-2">
                <X class="w-4 h-4" />
            </button>
        </div>
    </transition>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(12px);
}
</style>
