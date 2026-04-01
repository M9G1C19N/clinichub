<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Plus, Search, Receipt, Users, TrendingUp,
    Clock, CheckCircle2, AlertCircle, Calendar,
    FlaskConical, ScanLine, TestTube, Stethoscope,
    AlertTriangle, ChevronRight, FileText,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE as visitTypeBadge } from '@/config/visitTypes.js'

const props = defineProps({
    today:   Object,
    history: Object,
    unpaid:  Object,
    filters: Object,
    summary: Object,
})

const activeTab = ref(props.filters?.tab ?? 'today')
const search    = ref(props.filters?.search ?? '')
const status    = ref(props.filters?.status ?? '')
const date      = ref(props.filters?.date ?? '')

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
})

function applyFilters() {
    router.get(route('reception.index'), {
        tab:    activeTab.value,
        search: search.value || undefined,
        status: status.value || undefined,
        date:   date.value || undefined,
    }, { preserveState: true, replace: true })
}

function switchTab(tab) {
    activeTab.value = tab
    applyFilters()
}

// Config

const invoiceStatusConfig = {
    unpaid:    { bg: '#fef2f2', color: '#dc2626', label: 'Unpaid'   },
    partial:   { bg: '#fffbeb', color: '#b45309', label: 'Partial'  },
    paid:      { bg: '#f0fdf4', color: '#15803d', label: 'Paid'     },
    cancelled: { bg: '#f8fafc', color: '#64748b', label: 'Cancelled'},
}

const roomStatusConfig = {
    none:       { dot: '#cbd5e1', label: '—'          },
    pending:    { dot: '#f59e0b', label: 'Pending'    },
    processing: { dot: '#3b82f6', label: 'Processing' },
    released:   { dot: '#10b981', label: 'Released'   },
    completed:  { dot: '#10b981', label: 'Released'   },
    cancelled:  { dot: '#94a3b8', label: 'Cancelled'  },
}

const doctorStatusConfig = {
    pending:     { dot: '#f59e0b', label: 'Pending'     },
    in_progress: { dot: '#3b82f6', label: 'In Progress' },
    finalized:   { dot: '#10b981', label: 'Finalized'   },
}

// Compute overall journey completeness
function journeyComplete(visit) {
    const j = visit.journey
    const checks = []
    if (j.lab)       checks.push(j.lab.is_released)
    if (j.xray)      checks.push(j.xray.is_released)
    if (j.drug_test) checks.push(j.drug_test.is_released)
    checks.push(j.doctor.is_finalized)
    return checks.length > 0 && checks.every(Boolean)
}

function journeyProgress(visit) {
    const j = visit.journey
    let done = 0, total = 0
    if (j.lab)       { total++; if (j.lab.is_released) done++ }
    if (j.xray)      { total++; if (j.xray.is_released) done++ }
    if (j.drug_test) { total++; if (j.drug_test.is_released) done++ }
    total++
    if (j.doctor.is_finalized) done++
    return { done, total }
}

// Active data based on tab
function currentData() {
    if (activeTab.value === 'today')   return props.today
    if (activeTab.value === 'unpaid')  return props.unpaid
    if (activeTab.value === 'history') return props.history
    return props.today
}
</script>

<template>
    <AppLayout title="Reception">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <Receipt class="w-5 h-5 text-blue-600"/>
                        Reception & Billing
                    </h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        {{ new Date().toLocaleDateString('en-PH', {
                            weekday:'long', year:'numeric', month:'long', day:'numeric'
                        }) }}
                    </p>
                </div>
                <Link :href="route('reception.create')">
                    <Button style="background-color:#1B4F9B" class="gap-2 text-white">
                        <Plus class="w-4 h-4"/>
                        New Visit
                    </Button>
                </Link>
            </div>
        </template>

        <!-- Summary Cards -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <Users class="w-5 h-5 text-blue-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Today's Visits</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.total_today }}</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <TrendingUp class="w-5 h-5 text-emerald-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Revenue Today</p>
                    <p class="text-xl font-black text-slate-800">{{ summary.total_revenue }}</p>
                </div>
            </div>
            <div @click="switchTab('unpaid')"
                class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-all"
                :class="activeTab === 'unpaid' ? 'ring-2 ring-red-400' : ''">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <AlertCircle class="w-5 h-5 text-red-500"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Pending Payment</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.unpaid_count }}</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                    <CheckCircle2 class="w-5 h-5 text-teal-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Completed Today</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.completed }}</p>
                </div>
            </div>
        </div>

        <!-- Tab Nav -->
        <div class="flex items-center gap-1 mb-4 bg-slate-100 p-1 rounded-xl w-fit">
            <button v-for="tab in [
                { key:'today',   label:'Today',           count: summary.total_today  },
                { key:'unpaid',  label:'Pending Payment', count: summary.unpaid_count },
                { key:'history', label:'History',         count: null                 },
            ]" :key="tab.key"
                @click="switchTab(tab.key)"
                :class="['flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all',
                    activeTab === tab.key ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700']">
                <Receipt v-if="tab.key === 'today'" class="w-3.5 h-3.5"/>
                <AlertCircle v-if="tab.key === 'unpaid'" class="w-3.5 h-3.5"/>
                <FileText v-if="tab.key === 'history'" class="w-3.5 h-3.5"/>
                {{ tab.label }}
                <span v-if="tab.count !== null"
                    :class="['px-1.5 py-0.5 rounded-md text-xs font-black min-w-[20px] text-center',
                        activeTab === tab.key
                            ? tab.key === 'unpaid' ? 'bg-red-500 text-white' : 'bg-blue-600 text-white'
                            : 'bg-slate-200 text-slate-600']">
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-3 mb-4 flex items-center gap-3">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400"/>
                <Input v-model="search" placeholder="Search patient name or code..."
                    class="pl-9 h-8 text-xs"/>
            </div>
            <!-- Date picker for history tab -->
            <div v-if="activeTab === 'history'"
                class="flex items-center gap-1.5 border border-slate-200 rounded-lg px-2.5 h-8 bg-white">
                <Calendar class="w-3.5 h-3.5 text-slate-400"/>
                <Input v-model="date" type="date"
                    class="border-0 shadow-none p-0 h-auto focus-visible:ring-0 text-xs w-32"
                    @change="applyFilters"/>
            </div>
            <!-- Status filter -->
            <Select v-model="status" @update:modelValue="applyFilters">
                <SelectTrigger class="w-36 h-8 text-xs">
                    <SelectValue placeholder="All Status"/>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="x">All Status</SelectItem>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="in_progress">In Progress</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                </SelectContent>
            </Select>
            <Button v-if="search || date || status"
                variant="outline" size="sm" class="h-8 text-xs"
                @click="() => { search=''; date=''; status=''; applyFilters() }">
                Clear
            </Button>
        </div>

        <!-- Visit Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div v-if="!currentData().data?.length" class="py-16 text-center">
                <Receipt class="w-10 h-10 text-slate-200 mx-auto mb-3"/>
                <p class="text-sm font-semibold text-slate-400">
                    {{ activeTab === 'today' ? 'No visits today' :
                       activeTab === 'unpaid' ? 'No pending payments' : 'No records found' }}
                </p>
            </div>

            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80">
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Time</th>
                        <!-- Journey columns -->
                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">
                            <FlaskConical class="w-3.5 h-3.5 mx-auto text-blue-400"/>
                            <span class="block text-xs">Lab</span>
                        </th>
                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">
                            <ScanLine class="w-3.5 h-3.5 mx-auto text-purple-400"/>
                            <span class="block text-xs">X-Ray</span>
                        </th>
                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">
                            <TestTube class="w-3.5 h-3.5 mx-auto text-rose-400"/>
                            <span class="block text-xs">Drug</span>
                        </th>
                        <th class="text-center px-3 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">
                            <Stethoscope class="w-3.5 h-3.5 mx-auto text-emerald-400"/>
                            <span class="block text-xs">Doctor</span>
                        </th>
                        <!-- Billing -->
                        <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-32">Invoice</th>
                        <th class="px-4 py-2.5 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <tr v-for="visit in currentData().data" :key="visit.id"
                        class="hover:bg-slate-50/60 transition-colors group">

                        <!-- Patient -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white font-bold text-xs flex-shrink-0"
                                    style="background-color:#1B4F9B">
                                    {{ visit.patient_name?.charAt(0) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800 leading-tight">
                                        {{ visit.patient_name }}
                                    </p>
                                    <p class="text-xs text-slate-400 font-mono">{{ visit.patient_code }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 mt-1.5 ml-9">
                                <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                    :style="{
                                        background: visitTypeBadge[visit.visit_type]?.bg,
                                        color: visitTypeBadge[visit.visit_type]?.color
                                    }">
                                    {{ visitTypeBadge[visit.visit_type]?.label }}
                                </span>
                                <span v-if="visit.case_number"
                                    class="text-xs font-mono font-bold text-slate-500">
                                    CN: {{ visit.case_number }}
                                </span>
                                <span v-else-if="visit.is_field_visit"
                                    class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                    style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                                    Field Visit
                                </span>
                                <span v-if="visit.employer_company" class="text-xs text-slate-400">
                                    {{ visit.employer_company }}
                                </span>
                            </div>
                        </td>


                        <!-- Time -->
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1 text-xs text-slate-500">
                                <Clock class="w-3 h-3"/>
                                {{ visit.visit_time }}
                            </div>
                            <p v-if="activeTab === 'history'"
                                class="text-xs text-slate-400 mt-0.5">{{ visit.visit_date }}</p>
                        </td>

                        <!-- Lab -->
                        <td class="px-3 py-3 text-center">
                            <div v-if="visit.journey.lab">
                                <span class="w-2 h-2 rounded-full inline-block mb-1"
                                    :style="{ background: roomStatusConfig[visit.journey.lab.status]?.dot }"/>
                                <p class="text-xs font-semibold leading-none"
                                    :style="{ color: roomStatusConfig[visit.journey.lab.status]?.dot }">
                                    {{ roomStatusConfig[visit.journey.lab.status]?.label }}
                                </p>
                                <span v-if="visit.journey.lab.has_abnormal"
                                    class="text-xs text-red-600 font-bold">⚠</span>
                            </div>
                            <span v-else class="text-xs text-slate-200">—</span>
                        </td>

                        <!-- X-Ray -->
                        <td class="px-3 py-3 text-center">
                            <div v-if="visit.journey.xray">
                                <span class="w-2 h-2 rounded-full inline-block mb-1"
                                    :style="{ background: roomStatusConfig[visit.journey.xray.status]?.dot }"/>
                                <p class="text-xs font-semibold leading-none"
                                    :style="{ color: roomStatusConfig[visit.journey.xray.status]?.dot }">
                                    {{ roomStatusConfig[visit.journey.xray.status]?.label }}
                                </p>
                            </div>
                            <span v-else class="text-xs text-slate-200">—</span>
                        </td>

                        <!-- Drug Test -->
                        <td class="px-3 py-3 text-center">
                            <div v-if="visit.journey.drug_test">
                                <span class="w-2 h-2 rounded-full inline-block mb-1"
                                    :style="{ background: roomStatusConfig[visit.journey.drug_test.status]?.dot }"/>
                                <p class="text-xs font-semibold leading-none"
                                    :style="{ color:
                                        visit.journey.drug_test.result === 'positive' ? '#dc2626' :
                                        visit.journey.drug_test.result === 'negative' ? '#15803d' :
                                        roomStatusConfig[visit.journey.drug_test.status]?.dot }">
                                    {{ visit.journey.drug_test.result
                                        ? visit.journey.drug_test.result.toUpperCase()
                                        : roomStatusConfig[visit.journey.drug_test.status]?.label }}
                                </p>
                            </div>
                            <span v-else class="text-xs text-slate-200">—</span>
                        </td>

                        <!-- Doctor -->
                        <td class="px-3 py-3 text-center">
                            <span class="w-2 h-2 rounded-full inline-block mb-1"
                                :style="{ background: doctorStatusConfig[visit.journey.doctor.status]?.dot }"/>
                            <p class="text-xs font-semibold leading-none"
                                :style="{ color: doctorStatusConfig[visit.journey.doctor.status]?.dot }">
                                {{ visit.journey.doctor.classification
                                    ? 'Class ' + visit.journey.doctor.classification
                                    : doctorStatusConfig[visit.journey.doctor.status]?.label }}
                            </p>
                        </td>

                        <!-- Invoice / Billing -->
                        <td class="px-4 py-3">
                            <div v-if="visit.invoice">
                                <p class="text-xs font-mono text-slate-500">{{ visit.invoice.invoice_number }}</p>
                                <p class="text-sm font-bold text-slate-800">
                                    ₱{{ Number(visit.invoice.total_amount).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                                </p>
                                <span class="text-xs font-semibold px-1.5 py-0.5 rounded"
                                    :style="{
                                        background: invoiceStatusConfig[visit.invoice.status]?.bg,
                                        color: invoiceStatusConfig[visit.invoice.status]?.color
                                    }">
                                    {{ invoiceStatusConfig[visit.invoice.status]?.label }}
                                </span>
                                <p v-if="visit.invoice.status !== 'paid'"
                                    class="text-xs font-bold text-red-600 mt-0.5">
                                    Balance: ₱{{ Number(visit.invoice.balance).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                                </p>
                            </div>
                            <span v-else class="text-xs text-slate-300">—</span>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <Link v-if="visit.invoice"
                                    :href="route('reception.show', visit.invoice.id)">
                                    <Button variant="outline" size="sm" class="text-xs h-7 gap-1 w-full">
                                        <Receipt class="w-3 h-3"/> Invoice
                                    </Button>
                                </Link>
                                <Link :href="route('patients.show', visit.patient_id)">
                                    <Button variant="outline" size="sm" class="text-xs h-7 gap-1 w-full">
                                        <Users class="w-3 h-3"/> Profile
                                    </Button>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="currentData().data?.length > 0"
                class="px-4 py-3 border-t flex items-center justify-between bg-slate-50/50">
                <p class="text-xs text-slate-400">
                    {{ currentData().from }}–{{ currentData().to }} of {{ currentData().total }}
                </p>
                <div class="flex items-center gap-0.5">
                    <template v-for="link in currentData().links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" preserve-state
                            class="px-2.5 py-1 text-xs rounded transition-colors"
                            :class="link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100'"
                            :style="link.active ? 'background-color:#1B4F9B' : ''"
                            v-html="link.label"/>
                        <span v-else class="px-2.5 py-1 text-xs text-slate-300" v-html="link.label"/>
                    </template>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
