<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    Ear, CheckCircle2, Clock, Activity, AlertTriangle,
    Search, Calendar, FileText, ChevronRight, Loader2,
    SlidersHorizontal, X, PackageCheck, PackageOpen, Minus,
    SquareCheckBig,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE as visitTypeBadge } from '@/config/visitTypes.js'

const props = defineProps({
    todayQueue:     Array,
    pending:        Object,
    history:        Object,
    readyForPickup: Object,
    filters:        Object,
    summary:        Object,
})

const activeTab = ref('today')

const search       = ref(props.filters?.search ?? '')
const date         = ref(props.filters?.date ?? '')
const pickupSearch = ref(props.filters?.pickup_search ?? '')
const pickupClaim  = ref(props.filters?.pickup_claim ?? 'all')
const pickupDate   = ref(props.filters?.pickup_date ?? '')

const queueSearch  = ref('')

const filteredQueue = computed(() => {
    let list = props.todayQueue ?? []
    const s = queueSearch.value.toLowerCase().trim()
    if (s) {
        list = list.filter(item =>
            item.patient.full_name?.toLowerCase().includes(s) ||
            item.patient.patient_code?.toLowerCase().includes(s)
        )
    }
    return list
})

// Status configs
const audStatusConfig = {
    none:       { dot: '#94a3b8', label: 'Not Started',  bg: '#f1f5f9', color: '#64748b' },
    collecting: { dot: '#0d9488', label: 'Exam Started', bg: '#f0fdfa', color: '#0f766e' },
    draft:      { dot: '#3b82f6', label: 'Draft',        bg: '#eff6ff', color: '#1d4ed8' },
    released:   { dot: '#10b981', label: 'Released',     bg: '#f0fdf4', color: '#047857' },
}
const queueStatusConfig = {
    waiting:   { label: 'Waiting',   bg: '#fffbeb', color: '#92400e', dot: '#f59e0b' },
    calling:   { label: 'Calling',   bg: '#eff6ff', color: '#1e40af', dot: '#3b82f6' },
    serving:   { label: 'Serving',   bg: '#ecfdf5', color: '#065f46', dot: '#10b981' },
    completed: { label: 'Completed', bg: '#f8fafc', color: '#475569', dot: '#94a3b8' },
}

const startingId  = ref(null)
const claimingId  = ref(null)
const unclaimingId = ref(null)

function startExam(visitId) {
    startingId.value = visitId
    router.post(route('audiometry.collect', visitId), {}, {
        preserveScroll: true,
        onFinish: () => { startingId.value = null },
    })
}

function markClaimed(id) {
    claimingId.value = id
    router.post(route('audiometry.claim', id), {}, {
        preserveScroll: true,
        onFinish: () => { claimingId.value = null },
    })
}

function markUnclaimed(id) {
    if (!confirm('Mark this patient as unclaimed?')) return
    unclaimingId.value = id
    router.post(route('audiometry.unclaim', id), {}, {
        preserveScroll: true,
        onFinish: () => { unclaimingId.value = null },
    })
}

function applyFilters() {
    router.get(route('audiometry.index'), {
        search:        search.value,
        date:          date.value,
        pickup_search: pickupSearch.value,
        pickup_claim:  pickupClaim.value,
        pickup_date:   pickupDate.value,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    date.value   = ''
    applyFilters()
}

function applyPickupFilters() {
    router.get(route('audiometry.index'), {
        search: search.value,
        date:   date.value,
        pickup_search: pickupSearch.value,
        pickup_claim:  pickupClaim.value,
        pickup_date:   pickupDate.value,
    }, { preserveState: true, replace: true })
}

function clearPickupFilters() {
    pickupSearch.value = ''
    pickupClaim.value  = 'all'
    pickupDate.value   = ''
    applyPickupFilters()
}

// Auto-refresh every 15s
let refreshTimer = null
onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({
            only: ['todayQueue', 'pending', 'history', 'readyForPickup', 'summary'],
            preserveScroll: true,
        })
    }, 15000)
})
onUnmounted(() => clearInterval(refreshTimer))

const pickupRows = computed(() => props.readyForPickup?.data ?? [])
</script>

<template>
    <AppLayout title="Audiometry">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <Ear class="w-5 h-5 text-teal-600"/>
                        Screening Audiometry
                    </h1>
                    <p class="text-slate-400 text-xs mt-0.5">Audiometry exam management</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"/>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"/>
                        </span>
                        Live · 15s
                    </div>
                </div>
            </div>
        </template>

        <!-- Summary cards -->
        <div class="grid grid-cols-4 gap-3 mb-5">
            <div v-for="card in [
                { label: 'Today\'s Queue', value: summary.today,          color: '#0d9488', bg: '#f0fdfa', border: '#99f6e4' },
                { label: 'Pending',        value: summary.pending,         color: '#3b82f6', bg: '#eff6ff', border: '#bfdbfe' },
                { label: 'Released Today', value: summary.released_today,  color: '#10b981', bg: '#f0fdf4', border: '#bbf7d0' },
                { label: 'Unclaimed',      value: summary.unclaimed,       color: '#f59e0b', bg: '#fffbeb', border: '#fde68a' },
            ]" :key="card.label"
                class="rounded-xl border px-4 py-3"
                :style="{ background: card.bg, borderColor: card.border }">
                <p class="text-2xl font-black" :style="{ color: card.color }">{{ card.value }}</p>
                <p class="text-xs font-semibold mt-0.5" :style="{ color: card.color }">{{ card.label }}</p>
            </div>
        </div>

        <!-- Tab nav -->
        <div class="flex items-center gap-1 mb-5 bg-slate-100 p-1 rounded-xl w-fit flex-wrap">
            <button v-for="tab in [
                { key: 'today',   label: 'Today\'s Queue',   count: summary.today,         icon: 'ear'     },
                { key: 'pending', label: 'Pending Release',  count: summary.pending,        icon: 'pending' },
                { key: 'pickup',  label: 'Ready for Pickup', count: (summary.waiting_pickup + summary.unclaimed), icon: 'pickup' },
                { key: 'history', label: 'History',          count: summary.released_today, icon: 'history' },
            ]" :key="tab.key"
                @click="activeTab = tab.key"
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all',
                    activeTab === tab.key
                        ? 'bg-white shadow-sm text-slate-800'
                        : 'text-slate-500 hover:text-slate-700'
                ]">
                <Ear      v-if="tab.key === 'today'"   class="w-3.5 h-3.5"/>
                <Clock    v-if="tab.key === 'pending'" class="w-3.5 h-3.5"/>
                <PackageCheck v-if="tab.key === 'pickup'"  class="w-3.5 h-3.5"/>
                <FileText v-if="tab.key === 'history'" class="w-3.5 h-3.5"/>
                {{ tab.label }}
                <span :class="[
                    'px-1.5 py-0.5 rounded-md text-xs font-black min-w-[20px] text-center',
                    activeTab === tab.key ? 'bg-teal-600 text-white' : 'bg-slate-200 text-slate-600'
                ]">{{ tab.count }}</span>
            </button>
        </div>

        <!-- ══ TAB 1: TODAY'S QUEUE ══ -->
        <div v-if="activeTab === 'today'">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="p-3">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                        <Input v-model="queueSearch" placeholder="Search patient name or code..."
                            class="pl-9 h-8 text-xs"/>
                    </div>
                </div>
            </div>

            <div v-if="!todayQueue.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <div class="w-12 h-12 bg-teal-50 rounded-xl flex items-center justify-center mx-auto mb-3">
                    <Ear class="w-6 h-6 text-teal-400"/>
                </div>
                <p class="text-sm font-semibold text-slate-500">No audiometry patients today</p>
                <p class="text-xs text-slate-400 mt-1">Patients with AUDIOMETRY service ordered will appear here</p>
            </div>

            <div v-else-if="!filteredQueue.length"
                class="bg-white rounded-xl border border-slate-200 py-12 text-center">
                <p class="text-sm font-semibold text-slate-400">No patients match current filter</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">Queue</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Queue Status</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Exam Status</th>
                            <th class="px-4 py-2.5 w-40"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="item in filteredQueue" :key="item.id"
                            class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-4 py-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black font-mono text-sm border-2"
                                    :style="{
                                        background: item.status === 'serving' ? '#ecfdf5' : item.status === 'calling' ? '#eff6ff' : '#f8fafc',
                                        color: item.status === 'serving' ? '#065f46' : item.status === 'calling' ? '#1e40af' : '#475569',
                                        borderColor: item.status === 'serving' ? '#6ee7b7' : item.status === 'calling' ? '#93c5fd' : '#e2e8f0',
                                    }">
                                    {{ item.queue_number }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                        style="background-color:#0d9488">
                                        {{ item.patient.full_name?.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800 leading-tight">{{ item.patient.full_name }}</p>
                                        <p class="text-xs text-slate-400 font-mono">{{ item.patient.patient_code }} · {{ item.patient.age_sex }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1.5 ml-9">
                                    <span v-if="item.visit"
                                        class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{ background: visitTypeBadge[item.visit.visit_type]?.bg, color: visitTypeBadge[item.visit.visit_type]?.color }">
                                        {{ visitTypeBadge[item.visit.visit_type]?.label }}
                                    </span>
                                    <span v-if="item.visit?.employer_company" class="text-xs text-slate-400">
                                        · {{ item.visit.employer_company }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5" :style="{ color: queueStatusConfig[item.status]?.color }">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :style="{ background: queueStatusConfig[item.status]?.dot }"/>
                                    <span class="text-xs font-semibold">{{ queueStatusConfig[item.status]?.label ?? item.status }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                        :style="{ background: audStatusConfig[item.visit?.aud_status ?? 'none']?.dot }"/>
                                    <span class="text-xs font-semibold"
                                        :style="{ color: audStatusConfig[item.visit?.aud_status ?? 'none']?.color }">
                                        {{ audStatusConfig[item.visit?.aud_status ?? 'none']?.label }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <button
                                        v-if="item.visit?.id && !item.visit?.is_started && item.status !== 'completed'"
                                        :disabled="startingId === item.visit.id"
                                        class="inline-flex items-center gap-1.5 text-xs h-7 px-2.5 rounded-md font-semibold text-white transition-all disabled:opacity-60"
                                        style="background-color:#0d9488;"
                                        @click="startExam(item.visit.id)">
                                        <Ear class="w-3 h-3"/>
                                        {{ startingId === item.visit.id ? 'Saving...' : 'Collected' }}
                                    </button>
                                    <Link v-if="item.visit?.id" :href="route('audiometry.enter', item.visit.id)">
                                        <Button size="sm"
                                            class="text-xs h-7 gap-1.5 text-white opacity-80 group-hover:opacity-100 transition-opacity"
                                            :style="{
                                                backgroundColor:
                                                    item.visit?.is_released  ? '#10b981' :
                                                    item.visit?.is_started   ? '#3b82f6' : '#94a3b8'
                                            }">
                                            {{ item.visit?.is_released  ? 'View'         :
                                               item.visit?.is_started   ? 'Enter Results' : 'Enter' }}
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

        <!-- ══ TAB 2: PENDING ══ -->
        <div v-if="activeTab === 'pending'">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="search || date" @click="clearFilters"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700">
                        <X class="w-3 h-3"/> Clear
                    </button>
                </div>
                <div class="p-3 flex items-center gap-2">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                        <Input v-model="search" placeholder="Search patient..." class="pl-9 h-8 text-xs" @keyup.enter="applyFilters"/>
                    </div>
                    <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                        <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                        <Input v-model="date" type="date" class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32" @change="applyFilters"/>
                    </div>
                    <Button size="sm" class="h-8 text-xs gap-1.5" style="background-color:#0d9488; color:white;" @click="applyFilters">
                        <Search class="w-3.5 h-3.5"/> Search
                    </Button>
                </div>
            </div>

            <div v-if="!pending.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <CheckCircle2 class="w-10 h-10 text-emerald-400 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No pending audiometry results</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Request No.</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Date</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Status</th>
                            <th class="px-4 py-2.5 w-24"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in pending.data" :key="r.id" class="hover:bg-teal-50/30 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{ background: visitTypeBadge[r.visit_type]?.bg, color: visitTypeBadge[r.visit_type]?.color }">
                                        {{ visitTypeBadge[r.visit_type]?.label }}
                                    </span>
                                    <span v-if="r.employer" class="text-xs text-slate-400">{{ r.employer }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono font-bold px-2 py-1 rounded" style="background:#f0fdfa; color:#0f766e; border:1px solid #99f6e4;">
                                    {{ r.request_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ r.visit_date }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <Loader2 v-if="r.status === 'draft'" class="w-3 h-3 text-blue-500 animate-spin"/>
                                    <CheckCircle2 v-else-if="r.status === 'collecting'" class="w-3 h-3 text-teal-500"/>
                                    <span class="text-xs font-semibold"
                                        :class="{
                                            'text-blue-600':  r.status === 'draft',
                                            'text-teal-600':  r.status === 'collecting',
                                        }">
                                        {{ r.status === 'draft' ? 'Draft' : 'Started' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('audiometry.enter', r.visit_id)">
                                    <Button size="sm" class="text-xs h-7 gap-1 text-white opacity-60 group-hover:opacity-100" style="background-color:#0d9488;">
                                        {{ r.status === 'draft' ? 'Continue' : 'Enter Results' }}
                                        <ChevronRight class="w-3 h-3"/>
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">{{ pending.from }}–{{ pending.to }} of {{ pending.total }} records</p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in pending.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background-color:#0d9488' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ TAB 3: READY FOR PICKUP ══ -->
        <div v-if="activeTab === 'pickup'">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 flex items-center gap-3">
                    <PackageCheck class="w-8 h-8 text-emerald-500 flex-shrink-0"/>
                    <div>
                        <p class="text-2xl font-black text-emerald-700">{{ summary.waiting_pickup }}</p>
                        <p class="text-xs font-semibold text-emerald-600">Waiting for Pickup</p>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-center gap-3">
                    <AlertTriangle class="w-8 h-8 text-amber-500 flex-shrink-0"/>
                    <div>
                        <p class="text-2xl font-black text-amber-700">{{ summary.unclaimed }}</p>
                        <p class="text-xs font-semibold text-amber-600">Unclaimed Results</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="pickupSearch || pickupDate || pickupClaim !== 'all'"
                        @click="clearPickupFilters"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700">
                        <X class="w-3 h-3"/> Clear
                    </button>
                </div>
                <div class="p-3 space-y-2.5">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1 max-w-sm">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="pickupSearch" placeholder="Search patient..." class="pl-9 h-8 text-xs" @keyup.enter="applyPickupFilters"/>
                        </div>
                        <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                            <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                            <Input v-model="pickupDate" type="date" class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32" @change="applyPickupFilters"/>
                        </div>
                        <Button size="sm" class="h-8 text-xs gap-1.5" style="background-color:#10b981; color:white;" @click="applyPickupFilters">
                            <Search class="w-3.5 h-3.5"/> Search
                        </Button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs text-slate-400 font-medium w-14 flex-shrink-0">Status</span>
                        <button v-for="s in [
                            { value: 'all',       label: 'All'       },
                            { value: 'waiting',   label: 'Waiting'   },
                            { value: 'unclaimed', label: 'Unclaimed' },
                        ]" :key="s.value"
                            @click="pickupClaim = s.value; applyPickupFilters()"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-all"
                            :style="pickupClaim === s.value
                                ? 'background-color:#10b981;color:white;border-color:#10b981;'
                                : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                            {{ s.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!readyForPickup?.data?.length"
                class="bg-white rounded-xl border border-slate-200 py-20 text-center">
                <PackageOpen class="w-12 h-12 text-emerald-300 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-500">No results awaiting pickup</p>
            </div>

            <div v-else class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Request No.</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-44">Released At</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Status</th>
                            <th class="px-4 py-2.5 w-52 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in readyForPickup.data" :key="r.id"
                            class="transition-colors group"
                            :class="r.claim_status === 'unclaimed' ? 'hover:bg-amber-50/40' : 'hover:bg-emerald-50/30'">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs flex-shrink-0 text-white"
                                        :style="{ backgroundColor: r.claim_status === 'unclaimed' ? '#d97706' : '#059669' }">
                                        {{ r.patient_name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800 leading-tight">{{ r.patient_name }}</p>
                                        <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono font-bold px-2 py-1 rounded"
                                    :style="r.claim_status === 'unclaimed'
                                        ? 'background:#fffbeb; color:#b45309; border:1px solid #fde68a;'
                                        : 'background:#f0fdf4; color:#047857; border:1px solid #bbf7d0;'">
                                    {{ r.request_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 text-emerald-600">
                                    <CheckCircle2 class="w-3.5 h-3.5"/>
                                    <span class="text-xs font-medium">{{ r.released_at }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="r.claim_status === 'waiting'"
                                    class="inline-flex items-center gap-1 text-xs font-bold px-2 py-1 rounded-full"
                                    style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0;">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"/>
                                    Waiting
                                </span>
                                <span v-else-if="r.claim_status === 'unclaimed'"
                                    class="inline-flex items-center gap-1 text-xs font-bold px-2 py-1 rounded-full"
                                    style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                                    <AlertTriangle class="w-3 h-3"/> Unclaimed
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-70 group-hover:opacity-100 transition-opacity">
                                    <Link :href="route('audiometry.enter', r.visit_id)">
                                        <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                            <Ear class="w-3 h-3"/> View
                                        </Button>
                                    </Link>
                                    <a :href="route('audiometry.print', r.visit_id)" target="_blank">
                                        <Button variant="outline" size="sm" class="text-xs h-7">Print</Button>
                                    </a>
                                    <button v-if="r.claim_status !== 'claimed'"
                                        :disabled="claimingId === r.id"
                                        @click="markClaimed(r.id)"
                                        class="flex items-center gap-1 text-xs h-7 px-2.5 rounded-md font-semibold text-white transition-all disabled:opacity-50"
                                        style="background-color:#10b981;">
                                        <PackageCheck class="w-3 h-3"/>
                                        {{ claimingId === r.id ? '...' : 'Claimed' }}
                                    </button>
                                    <button v-if="r.claim_status === 'waiting'"
                                        :disabled="unclaimingId === r.id"
                                        @click="markUnclaimed(r.id)"
                                        class="flex items-center gap-1 text-xs h-7 px-2.5 rounded-md font-semibold transition-all disabled:opacity-50"
                                        style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                                        <AlertTriangle class="w-3 h-3"/>
                                        {{ unclaimingId === r.id ? '...' : 'Unclaimed' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">{{ readyForPickup.from }}–{{ readyForPickup.to }} of {{ readyForPickup.total }} records</p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in readyForPickup.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background-color:#0d9488' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ TAB 4: HISTORY ══ -->
        <div v-if="activeTab === 'history'">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm mb-4">
                <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-b border-slate-100">
                    <span class="flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <SlidersHorizontal class="w-3.5 h-3.5"/> Filter
                    </span>
                    <button v-if="search || date" @click="clearFilters"
                        class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-700">
                        <X class="w-3 h-3"/> Clear
                    </button>
                </div>
                <div class="p-3 flex items-center gap-2">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                        <Input v-model="search" placeholder="Search patient..." class="pl-9 h-8 text-xs" @keyup.enter="applyFilters"/>
                    </div>
                    <div class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                        <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                        <Input v-model="date" type="date" class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32" @change="applyFilters"/>
                    </div>
                    <Button size="sm" class="h-8 text-xs gap-1.5" style="background-color:#0d9488; color:white;" @click="applyFilters">
                        <Search class="w-3.5 h-3.5"/> Search
                    </Button>
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
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-36">Request No.</th>
                            <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-44">Released</th>
                            <th class="px-4 py-2.5 w-28"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="r in history.data" :key="r.id" class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-slate-800">{{ r.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ r.patient_code }} · {{ r.age_sex }}</p>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                        :style="{ background: visitTypeBadge[r.visit_type]?.bg, color: visitTypeBadge[r.visit_type]?.color }">
                                        {{ visitTypeBadge[r.visit_type]?.label }}
                                    </span>
                                    <span v-if="r.employer" class="text-xs text-slate-400">{{ r.employer }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-mono font-bold px-2 py-1 rounded"
                                    style="background:#f0fdf4; color:#047857; border:1px solid #bbf7d0;">
                                    {{ r.request_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5 text-emerald-600">
                                    <CheckCircle2 class="w-3.5 h-3.5"/>
                                    <span class="text-xs font-medium">{{ r.released_at }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1.5 opacity-60 group-hover:opacity-100">
                                    <Link :href="route('audiometry.enter', r.visit_id)">
                                        <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                            <Ear class="w-3 h-3"/> View
                                        </Button>
                                    </Link>
                                    <a :href="route('audiometry.print', r.visit_id)" target="_blank">
                                        <Button variant="outline" size="sm" class="text-xs h-7">Print</Button>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <p class="text-xs text-slate-400">{{ history.from }}–{{ history.to }} of {{ history.total }} records</p>
                    <div class="flex items-center gap-0.5">
                        <template v-for="link in history.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" preserve-state
                                class="px-2.5 py-1 text-xs rounded transition-colors"
                                :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                                :style="link.active ? 'background-color:#0d9488' : ''"
                                v-html="link.label"/>
                            <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
