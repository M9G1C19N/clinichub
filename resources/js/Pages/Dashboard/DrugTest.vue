<script setup>
import { computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import QueueBadge from '@/Components/Dashboard/QueueBadge.vue'
import VisitTypeBadge from '@/Components/Dashboard/VisitTypeBadge.vue'
import {
    ShieldCheck, AlertTriangle, CheckCircle2, Activity,
    Clock, RefreshCw, Wifi, ChevronRight, Beaker
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    role: String,
    stats: Object,
    queue: Array,
    resultDistribution: Object,
    tempInRangeCount: Number,
    totalSpecimens: Number,
})

const priorityDot = {
    urgent: 'bg-red-500', senior: 'bg-amber-500',
    pwd: 'bg-purple-500', pregnant: 'bg-pink-500', regular: 'bg-slate-300',
}

const resultConfig = {
    negative:    { label: 'Negative',    color: '#059669', bg: '#D1FAE5' },
    positive:    { label: 'Positive',    color: '#DC2626', bg: '#FEE2E2' },
    cancelled:   { label: 'Cancelled',   color: '#94A3B8', bg: '#F1F5F9' },
    refusal:     { label: 'Refusal',     color: '#D97706', bg: '#FEF3C7' },
    diluted:     { label: 'Diluted',     color: '#7C3AED', bg: '#EDE9FE' },
    adulterated: { label: 'Adulterated', color: '#C2410C', bg: '#FFF7ED' },
    substituted: { label: 'Substituted', color: '#B45309', bg: '#FFFBEB' },
}

const totalResults = computed(() => Object.values(props.resultDistribution ?? {}).reduce((a, b) => a + b, 0))

const tempPct = computed(() => props.totalSpecimens > 0 ? Math.round((props.tempInRangeCount / props.totalSpecimens) * 100) : 0)

let timer = null
onMounted(() => {
    timer = setInterval(() => {
        router.reload({ only: ['stats', 'queue', 'resultDistribution'] })
    }, 10000)
})
onUnmounted(() => clearInterval(timer))
</script>

<template>
    <AppLayout title="Drug Test Dashboard">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-[#0F2044]">Drug Test Station</h1>
                <p class="text-sm text-slate-400 mt-0.5">Specimen collection and result release</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full">
                    <Wifi class="w-3 h-3" /> <span class="font-semibold">Live — 10s</span>
                </div>
                <button @click="() => router.reload()" class="p-2 text-slate-400 hover:text-[#1B4F9B] hover:bg-slate-50 rounded-lg">
                    <RefreshCw class="w-4 h-4" />
                </button>
                <Link :href="route('drug-test.index')" class="flex items-center gap-2 text-sm text-[#1B4F9B] font-semibold border border-[#1B4F9B] px-3 py-2 rounded-xl hover:bg-[#1B4F9B] hover:text-white transition-colors">
                    Full View <ChevronRight class="w-4 h-4" />
                </Link>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard label="In Queue" :value="stats.in_queue" :icon="Activity" color="#1B4F9B" />
            <StatCard label="Specimens Collected" :value="stats.collected" :icon="Clock" color="#0369A1" sub="Processing or released" />
            <StatCard label="Released Today" :value="stats.released_today" :icon="CheckCircle2" color="#059669" :success="stats.released_today > 0" />
            <StatCard
                label="Positive Results"
                :value="stats.positive_today"
                :icon="AlertTriangle"
                color="#DC2626"
                :danger="stats.positive_today > 0"
                :sub="stats.positive_today > 0 ? 'Requires follow-up' : 'No positives today'"
            />
        </div>

        <div class="grid grid-cols-3 gap-5">
            <!-- Queue -->
            <div class="col-span-3 lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="p-5 border-b border-slate-50 flex items-center gap-2">
                        <ShieldCheck class="w-4 h-4 text-[#1B4F9B]" />
                        <h3 class="font-bold text-[#0F2044] flex-1">Today's Drug Test Queue</h3>
                        <span class="text-xs text-slate-400">{{ queue?.length ?? 0 }} patient(s)</span>
                    </div>

                    <div class="divide-y divide-slate-50">
                        <div
                            v-for="entry in queue" :key="entry.id"
                            class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition-colors"
                        >
                            <!-- Queue number -->
                            <div class="flex-shrink-0 text-center w-16">
                                <p class="text-lg font-black text-[#0F2044]">{{ entry.queue_number }}</p>
                                <QueueBadge :status="entry.status" size="xs" />
                            </div>

                            <!-- Patient -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityDot[entry.priority] ?? 'bg-slate-300'" />
                                    <p class="font-semibold text-[#0F2044]">{{ entry.patient_name }}</p>
                                    <!-- Result badge if available -->
                                    <span v-if="entry.result" class="text-xs font-bold px-2 py-0.5 rounded-full"
                                        :style="{ background: resultConfig[entry.result]?.bg, color: resultConfig[entry.result]?.color }">
                                        {{ resultConfig[entry.result]?.label ?? entry.result }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 ml-4">
                                    <p class="text-xs text-slate-400 font-mono">{{ entry.patient_code }}</p>
                                    <span class="text-slate-300">·</span>
                                    <p class="text-xs text-slate-400">{{ entry.age_sex }}</p>
                                    <span class="text-slate-300">·</span>
                                    <p class="text-xs text-slate-500 font-medium">{{ entry.employer }}</p>
                                </div>
                            </div>

                            <!-- Drugs -->
                            <div class="flex-shrink-0">
                                <p class="text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded-lg">{{ entry.drugs_label }}</p>
                            </div>

                            <!-- Visit type -->
                            <div class="flex-shrink-0">
                                <VisitTypeBadge :type="entry.visit_type ?? 'pre_employment'" size="xs" />
                            </div>

                            <!-- Status + action -->
                            <div class="flex-shrink-0 flex items-center gap-2">
                                <QueueBadge :status="entry.drug_status" size="xs" />
                                <Link
                                    v-if="entry.visit_id"
                                    :href="route('drug-test.enter', entry.visit_id)"
                                    class="text-xs font-semibold px-3 py-2 rounded-xl transition-colors"
                                    :class="entry.drug_status === 'released'
                                        ? 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                        : 'bg-[#1B4F9B] text-white hover:bg-[#0F2044]'"
                                >
                                    {{ entry.drug_status === 'released' ? 'View' : entry.drug_status === 'processing' ? 'Continue' : 'Collect' }}
                                </Link>
                            </div>
                        </div>

                        <div v-if="!queue?.length" class="py-16 text-center">
                            <CheckCircle2 class="w-12 h-12 text-slate-200 mx-auto mb-3" />
                            <p class="text-slate-400">No patients in drug test queue today</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right panel -->
            <div class="col-span-3 lg:col-span-1 space-y-4">
                <!-- Result distribution donut chart (simple visual) -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Result Distribution Today</h3>
                    <div v-if="totalResults > 0" class="space-y-3">
                        <div v-for="(count, result) in resultDistribution" :key="result" class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: resultConfig[result]?.color ?? '#94A3B8' }" />
                            <span class="flex-1 text-sm text-slate-600">{{ resultConfig[result]?.label ?? result }}</span>
                            <span class="font-black text-[#0F2044]">{{ count }}</span>
                            <span class="text-xs text-slate-400">{{ Math.round((count / totalResults) * 100) }}%</span>
                        </div>
                        <div class="mt-2 pt-2 border-t border-slate-100 flex justify-between">
                            <span class="text-xs font-semibold text-slate-400">TOTAL</span>
                            <span class="font-black text-[#1B4F9B]">{{ totalResults }}</span>
                        </div>
                    </div>
                    <div v-else class="text-sm text-slate-400 text-center py-6">
                        No results entered yet today
                    </div>
                </div>

                <!-- Specimen quality -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Specimen Quality</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-slate-600">Temp In Range (32–38°C)</span>
                                <span class="text-sm font-black text-[#0F2044]">{{ tempPct }}%</span>
                            </div>
                            <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="tempPct >= 80 ? 'bg-emerald-500' : tempPct >= 60 ? 'bg-amber-500' : 'bg-red-500'"
                                    :style="{ width: `${tempPct}%` }"
                                />
                            </div>
                            <p class="text-xs text-slate-400 mt-1">{{ tempInRangeCount }} of {{ totalSpecimens }} specimens</p>
                        </div>
                    </div>
                </div>

                <!-- Important notice -->
                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-5">
                    <div class="flex items-start gap-3">
                        <AlertTriangle class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-bold text-amber-800">Chain of Custody</p>
                            <p class="text-xs text-amber-700 mt-1">Drug test ALWAYS processed first. Urine specimen is time-sensitive — collect within 15 minutes of patient arrival.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
