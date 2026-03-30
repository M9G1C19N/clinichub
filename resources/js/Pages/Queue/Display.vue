<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    FlaskConical, ScanLine, TestTube,
    Stethoscope, Activity,
} from 'lucide-vue-next'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
const props = defineProps({
    board:          Object,
    todayTotal:     Number,
    todayCompleted: Number,
})

let refreshTimer = null
const now = ref(new Date())
let clockTimer = null

onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({ only: ['board', 'todayTotal', 'todayCompleted'] })
    }, 10000)
    clockTimer = setInterval(() => { now.value = new Date() }, 1000)
})

onUnmounted(() => {
    clearInterval(refreshTimer)
    clearInterval(clockTimer)
})

const timeString = () => now.value.toLocaleTimeString('en-PH', {
    hour: '2-digit', minute: '2-digit', second: '2-digit'
})
const dateString = () => now.value.toLocaleDateString('en-PH', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
})

const roomConfig = {
    laboratory:     { label: 'Laboratory',         color: '#3B82F6', icon: FlaskConical },
    xray_utz:       { label: 'X-Ray & Ultrasound', color: '#8B5CF6', icon: ScanLine },
    drug_test:      { label: 'Drug Test',           color: '#F43F5E', icon: TestTube },
    interview_room: { label: 'Interview Room',      color: '#10B981', icon: Stethoscope },
}
</script>

<template>
    <div class="min-h-screen flex flex-col" style="background:#0F2044;">

        <!-- Header -->
        <div class="flex items-center justify-between px-8 py-5 border-b border-white/10">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                    style="background:#1B4F9B">
                    <img :src="CLINIC_LOGO" class="w-12 h-12 object-contain bg-white rounded-xl p-1"/>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white">St. Peter Diagnostics & Laboratory</h1>
                    <p class="text-white/50 text-sm">Queue Display Board</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-3xl font-black text-white font-mono">{{ timeString() }}</p>
                <p class="text-white/50 text-sm">{{ dateString() }}</p>
            </div>
        </div>

        <!-- Room Grid -->
        <div class="flex-1 grid grid-cols-2 gap-6 p-8">
            <div v-for="(data, room) in board" :key="room"
                class="rounded-2xl overflow-hidden flex flex-col"
                style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1)">

                <!-- Room Header -->
                <div class="px-6 py-4 flex items-center gap-3"
                    :style="{ background: roomConfig[room]?.color + '20', borderBottom: '1px solid rgba(255,255,255,0.1)' }">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                        :style="{ background: roomConfig[room]?.color + '30' }">
                        <component :is="roomConfig[room]?.icon"
                            class="w-5 h-5"
                            :style="{ color: roomConfig[room]?.color }" />
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-white">{{ roomConfig[room]?.label }}</h2>
                        <p class="text-white/50 text-xs">{{ data.wait_count }} waiting</p>
                    </div>
                </div>

                <!-- Now Serving -->
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center">
                    <p class="text-white/40 text-sm font-semibold uppercase tracking-widest mb-4">
                        Now Serving
                    </p>
                    <div v-if="data.serving">
                        <p class="font-black leading-none mb-3"
                            :style="{ fontSize: '5rem', color: roomConfig[room]?.color }">
                            {{ data.serving.queue_number }}
                        </p>
                        <p class="text-white text-xl font-bold">{{ data.serving.patient_name }}</p>
                    </div>
                    <div v-else class="opacity-30">
                        <p class="text-white/30 text-6xl font-black">—</p>
                        <p class="text-white/30 text-sm mt-2">No patient being served</p>
                    </div>
                </div>

                <!-- Now Calling -->
                <div v-if="data.calling"
                    class="px-6 py-4 text-center"
                    style="background: rgba(255,255,255,0.05); border-top: 1px solid rgba(255,255,255,0.1)">
                    <p class="text-white/40 text-xs uppercase tracking-widest mb-1">Now Calling</p>
                    <p class="text-white font-bold text-lg">
                        {{ data.calling.queue_number }} — {{ data.calling.patient_name }}
                    </p>
                </div>

                <!-- Waiting count -->
                <div class="px-6 py-3 flex items-center justify-between"
                    style="border-top: 1px solid rgba(255,255,255,0.05)">
                    <span class="text-white/30 text-xs">Patients Waiting</span>
                    <span class="text-white font-bold">{{ data.wait_count }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-8 py-4 border-t border-white/10 flex items-center justify-between">
            <p class="text-white/40 text-sm">
                Total Today: <span class="text-white font-bold">{{ todayTotal }}</span>
            </p>
            <p class="text-white/40 text-sm">
                Completed: <span class="text-emerald-400 font-bold">{{ todayCompleted }}</span>
            </p>
            <p class="text-white/30 text-xs">Auto-refreshes every 10 seconds</p>
        </div>
    </div>
</template>
