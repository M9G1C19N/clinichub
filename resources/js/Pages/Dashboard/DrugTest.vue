<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ShieldCheck, AlertTriangle, CheckCircle2, Activity,
    Clock, RefreshCw, Wifi, ChevronRight, Beaker, TrendingUp,
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

const totalResults  = computed(() => Object.values(props.resultDistribution ?? {}).reduce((a, b) => a + b, 0))
const tempPct       = computed(() => props.totalSpecimens > 0 ? Math.round((props.tempInRangeCount / props.totalSpecimens) * 100) : 0)
const activeQ       = computed(() => (props.queue ?? []).filter(q => ['waiting','calling','serving'].includes(q.status)))
const doneQ         = computed(() => (props.queue ?? []).filter(q => q.status === 'completed').length)
const releasePct    = computed(() => {
    const total = (props.stats?.collected ?? 0)
    return total > 0 ? Math.round(((props.stats?.released_today ?? 0) / total) * 100) : 0
})

const refreshing = ref(false)
const lastRefresh = ref(new Date())
let timer = null
onMounted(() => { timer = setInterval(refresh, 10000) })
onUnmounted(() => clearInterval(timer))
async function refresh() {
    refreshing.value = true
    await router.reload({ only: ['stats', 'queue', 'resultDistribution'] })
    lastRefresh.value = new Date()
    refreshing.value = false
}
</script>

<template>
<AppLayout title="Drug Test Dashboard">
<div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0F2044]">Drug Test Station</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ user.name }} &mdash; {{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full font-semibold">
                <Wifi class="w-3 h-3"/> Live — 10s
            </span>
            <button @click="refresh" class="p-2 text-slate-400 hover:text-[#1B4F9B] hover:bg-slate-50 rounded-lg transition-colors">
                <RefreshCw class="w-4 h-4" :class="refreshing && 'animate-spin'"/>
            </button>
            <Link :href="route('drug-test.index')" class="flex items-center gap-2 text-sm font-semibold border border-[#1B4F9B] text-[#1B4F9B] px-3 py-2 rounded-xl hover:bg-[#1B4F9B] hover:text-white transition-colors">
                <ShieldCheck class="w-4 h-4"/> Full View
            </Link>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">In Queue</span>
                <Activity class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.in_queue }}</div>
            <div class="text-xs opacity-70 mt-1">{{ doneQ }} completed today</div>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#0369A1,#0ea5e9)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Specimens Collected</span>
                <Beaker class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.collected }}</div>
            <div class="text-xs opacity-70 mt-1">processing or released</div>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#059669,#10B981)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Released Today</span>
                <CheckCircle2 class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.released_today }}</div>
            <div class="text-xs opacity-70 mt-1">results released</div>
        </div>

        <div class="rounded-2xl p-5" :class="(stats.positive_today ?? 0) > 0 ? 'text-white' : 'bg-white border border-slate-100'"
            :style="(stats.positive_today ?? 0) > 0 ? 'background:linear-gradient(135deg,#DC2626,#ef4444)' : ''">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider" :class="(stats.positive_today ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">Positive Results</span>
                <AlertTriangle class="w-5 h-5" :class="(stats.positive_today ?? 0) > 0 ? 'opacity-60' : 'text-red-500'"/>
            </div>
            <div class="text-3xl font-black" :class="(stats.positive_today ?? 0) > 0 ? '' : 'text-red-600'">{{ stats.positive_today }}</div>
            <div class="text-xs mt-1" :class="(stats.positive_today ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">
                {{ (stats.positive_today ?? 0) > 0 ? 'requires follow-up' : 'no positives today' }}
            </div>
        </div>
    </div>

    <!-- Release Progress -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <TrendingUp class="w-4 h-4 text-[#1B4F9B]"/>
                <span class="text-sm font-bold text-slate-700">Specimen Release Progress</span>
            </div>
            <span class="text-sm font-black text-[#1B4F9B]">{{ stats.released_today }} / {{ stats.collected }} specimens</span>
        </div>
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                :class="releasePct >= 80 ? 'bg-emerald-500' : releasePct >= 50 ? 'bg-blue-500' : 'bg-amber-500'"
                :style="{ width: releasePct + '%' }"/>
        </div>
        <div class="flex justify-between text-xs text-slate-400 mt-1">
            <span>{{ releasePct }}% released</span>
            <span>{{ (stats.collected ?? 0) - (stats.released_today ?? 0) }} still processing</span>
        </div>
    </div>

    <!-- Chain of Custody Alert -->
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
        <AlertTriangle class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"/>
        <div>
            <p class="text-sm font-bold text-amber-800">Chain of Custody Protocol</p>
            <p class="text-xs text-amber-700 mt-0.5">Drug test is ALWAYS processed first. Urine specimen is time-sensitive — collect within 15 minutes of patient arrival.</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">
        <!-- Queue -->
        <div class="col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <ShieldCheck class="w-4 h-4 text-[#1B4F9B]"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Today's Drug Test Queue</h3>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold">{{ activeQ.length }} active</span>
                </div>
                <span class="text-xs text-slate-400">{{ queue?.length ?? 0 }} patient(s)</span>
            </div>
            <div class="overflow-y-auto" style="max-height:420px;">
                <div v-if="!queue?.length" class="py-16 text-center">
                    <CheckCircle2 class="w-10 h-10 text-slate-200 mx-auto mb-2"/>
                    <p class="text-slate-400 text-sm">No patients in drug test queue today</p>
                </div>
                <div v-else class="divide-y divide-slate-50">
                    <div v-for="entry in queue" :key="entry.id"
                        class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/60 transition-colors">
                        <div class="flex-shrink-0 text-center w-14">
                            <p class="text-xl font-black font-mono text-[#0F2044]">{{ entry.queue_number }}</p>
                            <p :class="['text-xs font-bold',
                                entry.status==='serving'  ? 'text-emerald-600' :
                                entry.status==='calling'  ? 'text-blue-600 animate-pulse' : 'text-slate-400']">
                                {{ entry.status }}
                            </p>
                        </div>
                        <div class="w-px h-10 bg-slate-100 flex-shrink-0"/>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full flex-shrink-0" :class="priorityDot[entry.priority] ?? 'bg-slate-300'"/>
                                <p class="font-semibold text-[#0F2044] truncate">{{ entry.patient_name }}</p>
                                <span v-if="entry.result" class="text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                                    :style="{ background: resultConfig[entry.result]?.bg, color: resultConfig[entry.result]?.color }">
                                    {{ resultConfig[entry.result]?.label ?? entry.result }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 ml-4">
                                <p class="text-xs text-slate-400 font-mono">{{ entry.patient_code }}</p>
                                <span class="text-slate-300">&bull;</span>
                                <p class="text-xs text-slate-400">{{ entry.age_sex }}</p>
                                <span class="text-slate-300">&bull;</span>
                                <p class="text-xs text-slate-500 font-medium truncate max-w-24">{{ entry.employer }}</p>
                            </div>
                        </div>
                        <!-- Drugs panel -->
                        <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2 py-1 rounded-lg flex-shrink-0">{{ entry.drugs_label }}</span>
                        <!-- Status + action -->
                        <div class="flex-shrink-0 flex items-center gap-2">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                :class="entry.drug_status === 'released'   ? 'bg-emerald-100 text-emerald-700' :
                                        entry.drug_status === 'processing' ? 'bg-blue-100 text-blue-700' :
                                        'bg-amber-100 text-amber-700'">
                                {{ entry.drug_status }}
                            </span>
                            <Link v-if="entry.visit_id" :href="route('drug-test.enter', entry.visit_id)"
                                class="text-xs font-semibold px-3 py-1.5 rounded-xl transition-colors"
                                :class="entry.drug_status === 'released'
                                    ? 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                    : 'text-white hover:opacity-90'"
                                :style="entry.drug_status !== 'released' ? 'background:#1B4F9B' : ''">
                                {{ entry.drug_status === 'released' ? 'View' : entry.drug_status === 'processing' ? 'Continue' : 'Collect' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right panel -->
        <div class="space-y-4">
            <!-- Result Distribution -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Result Distribution Today</h3>
                <div v-if="totalResults > 0" class="space-y-2.5">
                    <div v-for="(count, result) in resultDistribution" :key="result" class="flex items-center gap-3">
                        <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: resultConfig[result]?.color ?? '#94A3B8' }"/>
                        <span class="flex-1 text-xs text-slate-600">{{ resultConfig[result]?.label ?? result }}</span>
                        <span class="font-black text-slate-800 text-xs">{{ count }}</span>
                        <span class="text-xs text-slate-400 w-8 text-right">{{ Math.round((count / totalResults) * 100) }}%</span>
                    </div>
                    <div class="pt-2 border-t border-slate-100 flex justify-between">
                        <span class="text-xs font-semibold text-slate-400">TOTAL</span>
                        <span class="font-black text-[#1B4F9B] text-sm">{{ totalResults }}</span>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-400 text-center py-4">No results entered yet today</div>
            </div>

            <!-- Specimen Quality -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Specimen Quality</h3>
                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs text-slate-600 font-semibold">Temp In Range (32–38°C)</span>
                            <span class="text-sm font-black text-[#0F2044]">{{ tempPct }}%</span>
                        </div>
                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                :class="tempPct >= 80 ? 'bg-emerald-500' : tempPct >= 60 ? 'bg-amber-500' : 'bg-red-500'"
                                :style="{ width: `${tempPct}%` }"/>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">{{ tempInRangeCount }} of {{ totalSpecimens }} specimens in range</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</AppLayout>
</template>
