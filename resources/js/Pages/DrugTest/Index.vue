<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    TestTube, CheckCircle2, Clock, XCircle,
    Activity, AlertTriangle, Search,
    Calendar, FileText, ChevronRight, Loader2, X, SlidersHorizontal,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE as visitTypeBadge } from '@/config/visitTypes.js'
const props = defineProps({
    queue:   Array,
    pending: Object,
    history: Object,
    filters: Object,
    summary: Object,
})

const activeTab    = ref('today')
const search       = ref(props.filters?.search ?? '')
const date         = ref(props.filters?.date ?? '')
const statusFilter = ref(props.filters?.status ?? 'all')

// Today's queue client-side filters
const queueSearch        = ref('')
const queueStatusFilter  = ref('all')
const resultStatusFilter = ref('all')

const filteredQueue = computed(() => {
    let list = props.queue ?? []
    const s = queueSearch.value.toLowerCase().trim()
    if (s) {
        list = list.filter(item =>
            item.patient.full_name?.toLowerCase().includes(s) ||
            item.patient.patient_code?.toLowerCase().includes(s)
        )
    }
    if (queueStatusFilter.value !== 'all') {
        list = list.filter(item => item.status === queueStatusFilter.value)
    }
    if (resultStatusFilter.value !== 'all') {
        list = list.filter(item => (item.visit?.drug_status ?? 'none') === resultStatusFilter.value)
    }
    return list
})

let refreshTimer = null
onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({ only:['queue','pending','history','summary'], preserveScroll:true })
    }, 10000)
})
onUnmounted(() => clearInterval(refreshTimer))

function applyFilters() {
    router.get(route('drug-test.index'), { search: search.value, date: date.value, status: statusFilter.value },
        { preserveState: true, replace: true })
}
function clearFilters() {
    search.value = ''
    date.value = ''
    statusFilter.value = 'all'
    router.get(route('drug-test.index'), {}, { preserveState: true, replace: true })
}

const queueStatusConfig = {
    waiting:   { label:'Waiting',   dot:'#f59e0b', color:'#92400e' },
    calling:   { label:'Calling',   dot:'#3b82f6', color:'#1e40af' },
    serving:   { label:'Serving',   dot:'#10b981', color:'#065f46' },
    completed: { label:'Completed', dot:'#94a3b8', color:'#475569' },
}

const drugStatusConfig = {
    none:       { dot:'#94a3b8', label:'No Result',   color:'#64748b' },
    pending:    { dot:'#f59e0b', label:'Pending',     color:'#b45309' },
    collecting: { dot:'#0d9488', label:'Collected',   color:'#0f766e' },
    processing: { dot:'#0ea5e9', label:'In Progress', color:'#0369a1' },
    released:   { dot:'#10b981', label:'Released',    color:'#047857' },
}

const resultConfig = {
    negative:    { label:'NEGATIVE',   bg:'#f0fdf4', color:'#15803d', border:'#86efac' },
    positive:    { label:'POSITIVE',   bg:'#fef2f2', color:'#dc2626', border:'#fca5a5' },
    cancelled:   { label:'CANCELLED',  bg:'#f8fafc', color:'#64748b', border:'#cbd5e1' },
    refusal:     { label:'REFUSAL',    bg:'#fff7ed', color:'#c2410c', border:'#fed7aa' },
    diluted:     { label:'DILUTED',    bg:'#fefce8', color:'#a16207', border:'#fde047' },
    substituted: { label:'SUBSTITUTED',bg:'#fdf4ff', color:'#7e22ce', border:'#d8b4fe' },
    adulterated: { label:'ADULTERATED',bg:'#fff1f2', color:'#be123c', border:'#fda4af' },
}

// ── Specimen Collection Modal ──────────────────────────────
const collectModal = ref(false)
const collectVisitId = ref(null)
const collectPatientName = ref('')

const collectForm = useForm({
    test_purpose:        'pre_employment',
    drugs_to_test:       ['thc','met'],
    specimen_type:       'urine',
    specimen_time:       new Date().toTimeString().slice(0,5),
    specimen_date:       new Date().toISOString().slice(0,10),
    temp_in_range:       true,
    specimen_volume:     '',
    specimen_appearance: 'clear',
    specimen_sampling:   'single',
    specimen_collection: 'unobserved',
    collector_name:      '',
    collector_license:   '',
    company:             '',
})

function openCollectModal(item) {
    collectVisitId.value = item.visit.id
    collectPatientName.value = item.patient.full_name
    // Pre-fill company from visit
    collectForm.company = item.visit?.employer_company ?? ''
    collectForm.specimen_time = new Date().toTimeString().slice(0,5)
    collectForm.specimen_date = new Date().toISOString().slice(0,10)
    collectModal.value = true
}

function submitCollect() {
    collectForm.post(route('drug-test.collect', collectVisitId.value), {
        preserveScroll: true,
        onSuccess: () => {
            collectModal.value = false
            collectForm.reset()
        },
    })
}

const drugOptions = [
    { value: 'thc',               label: 'THC' },
    { value: 'met',               label: 'MET' },
    { value: 'thc_met',           label: 'THC & MET' },
    { value: 'thc_coc_pcp_opi_amp', label: 'THC, COC, PCP, OPI, AMP (5-Panel)' },
]

</script>

<template>
    <AppLayout title="Drug Test">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <TestTube class="w-5 h-5 text-rose-600"/>
                        Drug Test
                    </h1>
                    <p class="text-slate-400 text-xs mt-0.5">DOH-compliant specimen collection and result management</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-rose-600 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-lg">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"/>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-rose-500"/>
                        </span>
                        Live · 10s
                    </div>
                    <a :href="route('queue.room', 'drug_test')" target="_blank">
                        <Button variant="outline" size="sm" class="gap-1.5 text-xs h-7">
                            <Activity class="w-3.5 h-3.5"/> Room Screen
                        </Button>
                    </a>
                </div>
            </div>
        </template>

        <!-- Tab Nav -->
        <div class="flex items-center gap-1 mb-5 bg-slate-100 p-1 rounded-xl w-fit">
            <button v-for="tab in [
                { key:'today',   label:'Today\'s Queue',  count:summary.today          },
                { key:'pending', label:'Pending Release',  count:summary.pending        },
                { key:'history', label:'History',          count:history.total          },
            ]" :key="tab.key"
                @click="activeTab = tab.key"
                :class="['flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all',
                    activeTab === tab.key ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700']">
                <TestTube v-if="tab.key === 'today'" class="w-3.5 h-3.5"/>
                <Clock    v-if="tab.key === 'pending'" class="w-3.5 h-3.5"/>
                <FileText v-if="tab.key === 'history'" class="w-3.5 h-3.5"/>
                {{ tab.label }}
                <span :class="['px-1.5 py-0.5 rounded-md text-xs font-black min-w-[20px] text-center',
                    activeTab === tab.key ? 'bg-rose-600 text-white' : 'bg-slate-200 text-slate-600']">
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <!-- TODAY -->
        <div v-if="activeTab === 'today'">
            <!-- Filter bar -->
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <span class="text-xs text-slate-400">
                        <strong class="text-slate-700 font-bold">{{ filteredQueue.length }}</strong> / {{ queue.length }} shown
                    </span>
                </div>
                <div class="p-3 space-y-2.5">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                        <Input v-model="queueSearch" placeholder="Search patient name or code..."
                            class="pl-9 h-8 text-xs"/>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs text-slate-400 font-medium w-14 flex-shrink-0">Queue</span>
                        <button v-for="s in ['all','waiting','calling','serving','completed']" :key="s"
                            @click="queueStatusFilter = s"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-all"
                            :style="queueStatusFilter === s
                                ? 'background-color:#e11d48;color:white;border-color:#e11d48;box-shadow:0 1px 4px rgba(225,29,72,0.3);'
                                : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                            {{ s === 'all' ? 'All' : s.charAt(0).toUpperCase() + s.slice(1) }}
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs text-slate-400 font-medium w-14 flex-shrink-0">Status</span>
                        <button v-for="s in ['all','none','collecting','processing','released']" :key="s"
                            @click="resultStatusFilter = s"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-all"
                            :style="resultStatusFilter === s
                                ? 'background-color:#e11d48;color:white;border-color:#e11d48;box-shadow:0 1px 4px rgba(225,29,72,0.3);'
                                : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                            {{ s === 'all' ? 'All' : s === 'none' ? 'No Result' : s === 'collecting' ? 'Collected' : s === 'processing' ? 'In Progress' : 'Released' }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!queue.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <TestTube class="w-6 h-6 text-rose-400"/>
                </div>
                <p class="text-sm font-semibold text-slate-500">No patients in drug test queue today</p>
            </div>

            <div v-else-if="!filteredQueue.length"
                class="bg-white rounded-xl border border-slate-200 py-12 text-center">
                <p class="text-sm font-semibold text-slate-400">No patients match current filters</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">Queue</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Queue Status</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Test Status</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Result</th>
                            <th class="px-4 py-2.5 w-32"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="item in filteredQueue" :key="item.id"
                            class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-4 py-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black font-mono text-sm border-2"
                                    :style="{
                                        background: item.status === 'serving' ? '#fff1f2' : item.status === 'calling' ? '#eff6ff' : '#f8fafc',
                                        color: item.status === 'serving' ? '#be123c' : item.status === 'calling' ? '#1e40af' : '#475569',
                                        borderColor: item.status === 'serving' ? '#fda4af' : item.status === 'calling' ? '#93c5fd' : '#e2e8f0',
                                    }">
                                    {{ item.queue_number }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                        style="background:#e11d48">
                                        {{ item.patient.full_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">{{ item.patient.full_name }}</p>
                                        <p class="text-xs text-slate-400 font-mono">{{ item.patient.patient_code }} · {{ item.patient.age_sex }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1.5 ml-9">
                                    <span v-if="item.visit"
                                        class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{ background:visitTypeBadge[item.visit.visit_type]?.bg, color:visitTypeBadge[item.visit.visit_type]?.color }">
                                        {{ visitTypeBadge[item.visit.visit_type]?.label }}
                                    </span>
                                    <span v-if="item.visit?.employer_company" class="text-xs text-slate-400">
                                        · {{ item.visit.employer_company }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5"
                                    :style="{ color:queueStatusConfig[item.status]?.color }">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :style="{ background:queueStatusConfig[item.status]?.dot }"/>
                                    <span class="text-xs font-semibold">{{ queueStatusConfig[item.status]?.label }}</span>
                                </div>
                                <div v-if="item.status === 'completed' && !item.visit?.is_released"
                                    class="flex items-center gap-1 mt-1">
                                    <AlertTriangle class="w-3 h-3 text-amber-500"/>
                                    <span class="text-xs text-amber-600">Awaiting result</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :style="{ background:drugStatusConfig[item.visit?.drug_status ?? 'none']?.dot }"/>
                                    <span class="text-xs font-semibold"
                                        :style="{ color:drugStatusConfig[item.visit?.drug_status ?? 'none']?.color }">
                                        {{ drugStatusConfig[item.visit?.drug_status ?? 'none']?.label }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="item.visit?.result"
                                    class="text-xs font-bold px-2 py-0.5 rounded border"
                                    :style="{
                                        background: resultConfig[item.visit.result]?.bg,
                                        color: resultConfig[item.visit.result]?.color,
                                        borderColor: resultConfig[item.visit.result]?.border,
                                    }">
                                    {{ resultConfig[item.visit.result]?.label }}
                                </span>
                                <span v-else class="text-xs text-slate-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <!-- Collect Specimen: only when active in queue and not yet collected -->
                                    <button
                                        v-if="item.visit?.id && ['calling','serving'].includes(item.status) && !item.visit?.is_collected"
                                        class="inline-flex items-center gap-1.5 text-xs h-7 px-2.5 rounded-md font-semibold text-white"
                                        style="background-color:#0d9488;"
                                        @click="openCollectModal(item)">
                                        <TestTube class="w-3 h-3"/>
                                        Collect Specimen
                                    </button>
                                    <!-- Enter/Update/View -->
                                    <Link v-if="item.visit?.id" :href="route('drug-test.enter', item.visit.id)">
                                        <Button size="sm"
                                            class="text-xs h-7 gap-1.5 text-white opacity-80 group-hover:opacity-100"
                                            :style="{ backgroundColor:
                                                item.visit?.is_released  ? '#10b981' :
                                                item.visit?.is_collected ? '#e11d48' :
                                                item.visit?.has_result   ? '#f59e0b' : '#94a3b8' }">
                                            {{ item.visit?.is_released  ? 'View' :
                                               item.visit?.is_collected ? 'Enter Result' :
                                               item.visit?.has_result   ? 'Update' : 'Enter' }}
                                            <ChevronRight class="w-3 h-3"/>
                                        </Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PENDING -->
        <div v-if="activeTab === 'pending'">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="search || date || statusFilter !== 'all'"
                        @click="clearFilters"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700 transition-colors">
                        <X class="w-3 h-3"/> Clear filters
                    </button>
                </div>
                <div class="p-3 space-y-2.5">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 max-w-sm">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="search" placeholder="Search patient..." class="pl-9 h-8 text-xs" @keyup.enter="applyFilters"/>
                        </div>
                        <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                            <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="date" type="date"
                                class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32"
                                @change="applyFilters"/>
                        </div>
                        <Button size="sm" class="h-8 text-xs gap-1.5 text-white" style="background:#e11d48" @click="applyFilters">
                            <Search class="w-3.5 h-3.5"/> Search
                        </Button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs text-slate-400 font-medium w-14 flex-shrink-0">Status</span>
                        <button v-for="s in [
                            { value: 'all',        label: 'All'        },
                            { value: 'pending',    label: 'Pending'    },
                            { value: 'collecting', label: 'Collected'  },
                            { value: 'processing', label: 'In Progress'},
                        ]" :key="s.value"
                            @click="statusFilter = s.value; applyFilters()"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-all"
                            :style="statusFilter === s.value
                                ? 'background:#e11d48;color:white;border-color:#e11d48;box-shadow:0 1px 4px rgba(225,29,72,0.3);'
                                : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                            {{ s.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!pending.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <CheckCircle2 class="w-10 h-10 text-emerald-400 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No pending drug tests</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Code No.</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Drugs / Purpose</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Status</th>
                            <th class="px-4 py-2.5 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in pending.data" :key="r.id"
                            class="hover:bg-rose-50/20 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{ background:visitTypeBadge[r.visit_type]?.bg, color:visitTypeBadge[r.visit_type]?.color }">
                                        {{ visitTypeBadge[r.visit_type]?.label }}
                                    </span>
                                    <span v-if="r.employer" class="text-xs text-slate-400">{{ r.employer }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono font-bold px-2 py-1 rounded"
                                    style="background:#fff1f2; color:#be123c;">
                                    {{ r.code_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-semibold text-slate-700">{{ r.drugs_label }}</p>
                                <p class="text-xs text-slate-400">{{ r.purpose }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ r.visit_date }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-0.5">
                                    <div class="flex items-center gap-1.5">
                                        <Loader2 v-if="r.status === 'processing'" class="w-3 h-3 text-sky-500 animate-spin"/>
                                        <CheckCircle2 v-else-if="r.status === 'collecting'" class="w-3 h-3 text-teal-500"/>
                                        <Clock v-else class="w-3 h-3 text-amber-500"/>
                                        <span class="text-xs font-semibold"
                                            :class="{
                                                'text-sky-600':   r.status === 'processing',
                                                'text-teal-600':  r.status === 'collecting',
                                                'text-amber-600': r.status === 'pending',
                                            }">
                                            {{ r.status === 'processing' ? 'In Progress' :
                                               r.status === 'collecting' ? 'Collected' : 'Pending' }}
                                        </span>
                                    </div>
                                    <span v-if="r.collected_at && r.status === 'collecting'"
                                        class="text-xs text-slate-400 ml-4.5">
                                        at {{ r.collected_at }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('drug-test.enter', r.visit_id)">
                                    <Button size="sm" class="text-xs h-7 gap-1 text-white opacity-60 group-hover:opacity-100 transition-opacity"
                                        style="background:#e11d48">
                                        {{ r.status === 'processing' ? 'Continue' :
                                           r.status === 'collecting' ? 'Enter Result' : 'Enter' }}
                                        <ChevronRight class="w-3 h-3"/>
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">{{ pending.from }}–{{ pending.to }} of {{ pending.total }}</p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in pending.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background:#e11d48' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- HISTORY -->
        <div v-if="activeTab === 'history'">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="search || date" @click="clearFilters"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700 transition-colors">
                        <X class="w-3 h-3"/> Clear filters
                    </button>
                </div>
                <div class="p-3">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 max-w-sm">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="search" placeholder="Search patient..." class="pl-9 h-8 text-xs" @keyup.enter="applyFilters"/>
                        </div>
                        <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                            <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="date" type="date" class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32" @change="applyFilters"/>
                        </div>
                        <Button size="sm" class="h-8 text-xs gap-1.5 text-white" style="background:#e11d48" @click="applyFilters">
                            <Search class="w-3.5 h-3.5"/> Search
                        </Button>
                    </div>
                </div>
            </div>

            <div v-if="!history.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <FileText class="w-10 h-10 text-slate-300 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No results found</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Code No.</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Result</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Drugs</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-44">Released</th>
                            <th class="px-4 py-2.5 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in history.data" :key="r.id"
                            class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <span v-if="r.employer" class="text-xs text-purple-600">{{ r.employer }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono font-bold px-2 py-1 rounded"
                                    style="background:#f0fdf4; color:#047857; border:1px solid #bbf7d0;">
                                    {{ r.code_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="r.result"
                                    class="text-xs font-bold px-2 py-0.5 rounded border"
                                    :style="{
                                        background:resultConfig[r.result]?.bg,
                                        color:resultConfig[r.result]?.color,
                                        borderColor:resultConfig[r.result]?.border
                                    }">
                                    {{ resultConfig[r.result]?.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ r.drugs_label }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 text-emerald-600">
                                    <CheckCircle2 class="w-3.5 h-3.5"/>
                                    <span class="text-xs font-medium">{{ r.released_at }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Link :href="route('drug-test.enter', r.visit_id)">
                                        <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                            <TestTube class="w-3 h-3"/> View
                                        </Button>
                                    </Link>
                                    <a :href="route('drug-test.print', r.visit_id)" target="_blank">
                                        <Button variant="outline" size="sm" class="text-xs h-7">Print</Button>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">{{ history.from }}–{{ history.to }} of {{ history.total }}</p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in history.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background:#e11d48' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════ -->
        <!-- SPECIMEN COLLECTION MODAL                       -->
        <!-- ══════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="collectModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100"
                        style="background:linear-gradient(135deg,#fff1f2,#ffe4e6)">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <TestTube class="w-4 h-4 text-rose-600"/>
                                Collect Specimen
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">{{ collectPatientName }}</p>
                        </div>
                        <button @click="collectModal = false"
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                            <X class="w-4 h-4"/>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitCollect" class="p-5 space-y-4">

                        <!-- Test Purpose -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Test Purpose <span class="text-rose-500">*</span></label>
                            <select v-model="collectForm.test_purpose"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                <option value="pre_employment">Pre-employment</option>
                                <option value="return_to_duty">Return to Duty</option>
                                <option value="random">Random</option>
                                <option value="reasonable_suspicion">Reasonable Suspicion/Cause</option>
                                <option value="follow_up">Follow-up</option>
                                <option value="post_accident">Post-accident</option>
                                <option value="mandatory">Mandatory</option>
                            </select>
                            <p v-if="collectForm.errors.test_purpose" class="text-xs text-rose-500 mt-0.5">{{ collectForm.errors.test_purpose }}</p>
                        </div>

                        <!-- Drugs to Test -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Drugs to Test <span class="text-rose-500">*</span></label>
                            <div class="flex flex-wrap gap-2">
                                <label v-for="opt in drugOptions" :key="opt.value"
                                    class="flex items-center gap-1.5 text-xs cursor-pointer px-2.5 py-1.5 rounded-lg border transition-colors"
                                    :class="collectForm.drugs_to_test.includes(opt.value)
                                        ? 'border-rose-400 bg-rose-50 text-rose-700 font-semibold'
                                        : 'border-slate-200 text-slate-600 hover:border-rose-200'">
                                    <input type="checkbox" :value="opt.value"
                                        v-model="collectForm.drugs_to_test"
                                        class="sr-only"/>
                                    {{ opt.label }}
                                </label>
                            </div>
                            <p v-if="collectForm.errors.drugs_to_test" class="text-xs text-rose-500 mt-0.5">{{ collectForm.errors.drugs_to_test }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Specimen Type -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Specimen Type <span class="text-rose-500">*</span></label>
                                <select v-model="collectForm.specimen_type"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                    <option value="urine">Urine</option>
                                    <option value="blood">Blood</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Specimen Date -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Collection Date</label>
                                <input type="date" v-model="collectForm.specimen_date"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"/>
                            </div>

                            <!-- Specimen Time -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Collection Time</label>
                                <input type="time" v-model="collectForm.specimen_time"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"/>
                            </div>

                            <!-- Temp in Range -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Temperature in Range</label>
                                <select v-model="collectForm.temp_in_range"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                                    <option :value="true">Yes</option>
                                    <option :value="false">No</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Collector Name -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Collector Name</label>
                                <input v-model="collectForm.collector_name" type="text"
                                    placeholder="Full name"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"/>
                            </div>
                            <!-- Collector License -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">License No.</label>
                                <input v-model="collectForm.collector_license" type="text"
                                    placeholder="PRC / License No."
                                    class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"/>
                            </div>
                        </div>

                        <!-- Company -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Company / Employer</label>
                            <input v-model="collectForm.company" type="text"
                                placeholder="Optional"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-300"/>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button type="button"
                                class="text-xs h-8 px-4 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold"
                                @click="collectModal = false">
                                Cancel
                            </button>
                            <button type="submit"
                                :disabled="collectForm.processing || collectForm.drugs_to_test.length === 0"
                                class="text-xs h-8 px-5 rounded-lg text-white font-semibold flex items-center gap-1.5 disabled:opacity-60"
                                style="background:#0d9488">
                                <Loader2 v-if="collectForm.processing" class="w-3 h-3 animate-spin"/>
                                <TestTube v-else class="w-3 h-3"/>
                                {{ collectForm.processing ? 'Saving...' : 'Confirm Collection' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>
