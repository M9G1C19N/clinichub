<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import { FlaskConical, CheckCircle2, Clock, AlertTriangle, PackageOpen } from 'lucide-vue-next'

const props = defineProps({
    waiting:   Array,
    unclaimed: Array,
    counts:    Object,
})

// ── Clock ──────────────────────────────────────────────
const now = ref(new Date())
let clockTimer = null
let refreshTimer = null

onMounted(() => {
    clockTimer   = setInterval(() => { now.value = new Date() }, 1000)
    refreshTimer = setInterval(() => {
        router.reload({ only: ['waiting', 'unclaimed', 'counts'] })
    }, 10000)
})
onUnmounted(() => {
    clearInterval(clockTimer)
    clearInterval(refreshTimer)
})

const timeStr = computed(() =>
    now.value.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
)
const dateStr = computed(() =>
    now.value.toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
)

// Animate: highlight newest entries
const highlightedId = ref(null)
</script>

<template>
    <div class="min-h-screen flex flex-col" style="background:#0B1829; font-family:'Segoe UI',Arial,sans-serif;">

        <!-- ══ HEADER ══════════════════════════════════════ -->
        <header class="flex items-center justify-between px-8 py-4 border-b"
            style="background:#0F2044; border-color:#1e3a6e;">
            <!-- Logo + Clinic -->
            <div class="flex items-center gap-4">
                <img :src="CLINIC_LOGO" alt="Clinic Logo" class="h-12 w-12 object-contain rounded-xl"
                    style="background:white; padding:4px;"/>
                <div>
                    <p class="text-white font-black text-xl leading-tight tracking-tight">{{ CLINIC_INFO.shortName }}</p>
                    <p class="text-sm font-medium" style="color:#60a5fa;">Laboratory Results Pickup</p>
                </div>
            </div>

            <!-- Live indicator + counter -->
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <p class="text-4xl font-black tabular-nums" style="color:#34d399;">{{ counts.waiting }}</p>
                    <p class="text-xs font-semibold uppercase tracking-widest" style="color:#6ee7b7;">Ready</p>
                </div>
                <div class="w-px h-10" style="background:#1e3a6e;"/>
                <div class="text-center">
                    <p class="text-4xl font-black tabular-nums" style="color:#fbbf24;">{{ counts.unclaimed }}</p>
                    <p class="text-xs font-semibold uppercase tracking-widest" style="color:#fde68a;">Unclaimed</p>
                </div>
                <div class="w-px h-10" style="background:#1e3a6e;"/>
                <div class="text-right">
                    <p class="text-2xl font-black tabular-nums" style="color:white;">{{ timeStr }}</p>
                    <p class="text-xs" style="color:#94a3b8;">{{ dateStr }}</p>
                </div>
            </div>
        </header>

        <!-- ══ BODY ════════════════════════════════════════ -->
        <div class="flex flex-1 gap-0 overflow-hidden">

            <!-- ── LEFT PANEL: Ready for Pickup ──────────── -->
            <div class="flex flex-col flex-1 border-r" style="border-color:#1e3a6e;">
                <!-- Panel Header -->
                <div class="flex items-center gap-3 px-6 py-3 border-b" style="background:#0a2d1a; border-color:#166534;">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                            style="background:#34d399;"/>
                        <span class="relative inline-flex rounded-full h-3 w-3" style="background:#10b981;"/>
                    </span>
                    <CheckCircle2 class="w-5 h-5" style="color:#34d399;"/>
                    <span class="text-base font-black uppercase tracking-widest" style="color:#34d399;">
                        Ready for Pickup
                    </span>
                    <span class="ml-auto px-3 py-1 rounded-full text-sm font-black"
                        style="background:#166534; color:#34d399;">
                        {{ counts.waiting }} patient{{ counts.waiting !== 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Column Headers -->
                <div class="grid px-6 py-2 text-xs font-bold uppercase tracking-widest"
                    style="grid-template-columns:1fr 160px 100px; color:#4ade80; background:#0a1f12; border-bottom:1px solid #14532d;">
                    <span>Patient Name</span>
                    <span>Request No.</span>
                    <span class="text-right">Time</span>
                </div>

                <!-- Rows -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="!waiting.length"
                        class="flex flex-col items-center justify-center h-64 opacity-40">
                        <PackageOpen class="w-16 h-16 mb-4" style="color:#34d399;"/>
                        <p class="text-lg font-semibold" style="color:#34d399;">No results ready at this time</p>
                    </div>

                    <div v-for="(item, index) in waiting" :key="item.id"
                        class="grid px-6 items-center transition-all"
                        style="grid-template-columns:1fr 160px 100px; min-height:64px; border-bottom:1px solid #0a2211;"
                        :style="{
                            background: index % 2 === 0 ? '#0d2416' : '#0a1f12',
                        }">

                        <!-- Patient -->
                        <div class="flex items-center gap-3">
                            <!-- Pulsing badge for first item -->
                            <div v-if="index === 0"
                                class="relative flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm"
                                style="background:#166534; color:#34d399;">
                                <span class="animate-ping absolute inset-0 rounded-xl opacity-30"
                                    style="background:#34d399;"/>
                                {{ item.patient_name.charAt(0) }}
                            </div>
                            <div v-else
                                class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm"
                                style="background:#0a2d1a; color:#34d399;">
                                {{ item.patient_name.charAt(0) }}
                            </div>
                            <div>
                                <p class="font-bold text-lg leading-tight" style="color:white;">
                                    {{ item.patient_name }}
                                </p>
                                <p class="text-xs font-mono" style="color:#6ee7b7;">{{ item.patient_code }}</p>
                            </div>
                        </div>

                        <!-- Request No. -->
                        <span class="text-sm font-mono font-bold px-2 py-1 rounded inline-block"
                            style="background:#063a1c; color:#34d399; border:1px solid #166534;">
                            {{ item.request_number }}
                        </span>

                        <!-- Time -->
                        <span class="text-right text-sm font-medium" style="color:#86efac;">
                            {{ item.released_at }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT PANEL: Unclaimed ─────────────────── -->
            <div class="flex flex-col" style="width:420px; min-width:420px;">
                <!-- Panel Header -->
                <div class="flex items-center gap-3 px-5 py-3 border-b"
                    style="background:#2d1a00; border-color:#92400e;">
                    <AlertTriangle class="w-5 h-5" style="color:#fbbf24;"/>
                    <span class="text-base font-black uppercase tracking-widest" style="color:#fbbf24;">
                        Unclaimed
                    </span>
                    <span class="ml-auto px-3 py-1 rounded-full text-sm font-black"
                        style="background:#451a03; color:#fbbf24;">
                        {{ counts.unclaimed }}
                    </span>
                </div>

                <!-- Column Headers -->
                <div class="grid px-5 py-2 text-xs font-bold uppercase tracking-widest"
                    style="grid-template-columns:1fr 90px; color:#fcd34d; background:#1c1000; border-bottom:1px solid #451a03;">
                    <span>Patient</span>
                    <span class="text-right">Date</span>
                </div>

                <!-- Rows -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="!unclaimed.length"
                        class="flex flex-col items-center justify-center h-48 opacity-40">
                        <CheckCircle2 class="w-12 h-12 mb-3" style="color:#fbbf24;"/>
                        <p class="text-sm font-semibold" style="color:#fbbf24;">No unclaimed results</p>
                    </div>

                    <div v-for="(item, index) in unclaimed" :key="item.id"
                        class="grid px-5 items-center"
                        style="grid-template-columns:1fr 90px; min-height:56px; border-bottom:1px solid #1c1000;"
                        :style="{ background: index % 2 === 0 ? '#1a1000' : '#150d00' }">

                        <div>
                            <p class="font-bold leading-tight" style="color:#fde68a; font-size:0.9rem;">
                                {{ item.patient_name }}
                            </p>
                            <p class="text-xs font-mono" style="color:#b45309;">
                                {{ item.patient_code }} · {{ item.request_number }}
                            </p>
                        </div>
                        <span class="text-right text-xs font-medium" style="color:#fcd34d;">
                            {{ item.released_date }}
                        </span>
                    </div>
                </div>

                <!-- Bottom notice -->
                <div class="px-5 py-3 text-xs text-center border-t" style="background:#1a0e00; border-color:#451a03; color:#92400e;">
                    Please proceed to the laboratory window to claim your results.
                </div>
            </div>
        </div>

        <!-- ══ FOOTER ═════════════════════════════════════ -->
        <footer class="flex items-center justify-between px-8 py-2 border-t"
            style="background:#0F2044; border-color:#1e3a6e;">
            <div class="flex items-center gap-2">
                <FlaskConical class="w-4 h-4" style="color:#60a5fa;"/>
                <span class="text-xs font-medium" style="color:#60a5fa;">Laboratory Results Board</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"/>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"/>
                </span>
                <span class="text-xs" style="color:#64748b;">Auto-refreshes every 10 seconds</span>
            </div>
            <p class="text-xs" style="color:#334155;">{{ CLINIC_INFO.address }}</p>
        </footer>

    </div>
</template>
