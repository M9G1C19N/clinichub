<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Plus, Search, Receipt, Users,
    TrendingUp, Clock, CheckCircle2,
    AlertCircle,
} from 'lucide-vue-next'

const props = defineProps({
    visits:  Object,
    summary: Object,
    filters: Object,
})

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? 'all')

let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => applyFilters(), 400)
})
watch(status, () => applyFilters())

function applyFilters() {
    router.get(route('reception.index'), {
        search: search.value || undefined,
        status: status.value !== 'all' ? status.value : undefined,
    }, { preserveState: true, replace: true })
}

const visitTypeBadge = {
    opd:            'bg-blue-100 text-blue-700',
    pre_employment: 'bg-purple-100 text-purple-700',
    follow_up:      'bg-amber-100 text-amber-700',
    lab_only:       'bg-teal-100 text-teal-700',
}

const visitTypeLabel = {
    opd:            'OPD',
    pre_employment: 'Pre-Employment',
    follow_up:      'Follow-up',
    lab_only:       'Lab Only',
}

const invoiceStatusBadge = {
    unpaid:    'bg-red-100 text-red-600',
    partial:   'bg-amber-100 text-amber-700',
    paid:      'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-slate-100 text-slate-500',
}

const visitStatusBadge = {
    pending:     'bg-slate-100 text-slate-600',
    in_progress: 'bg-blue-100 text-blue-700',
    completed:   'bg-emerald-100 text-emerald-700',
    cancelled:   'bg-red-100 text-red-600',
}
</script>

<template>
    <AppLayout title="Reception">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Reception & Billing</h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        Today · {{ new Date().toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) }}
                    </p>
                </div>
                <Link :href="route('reception.create')">
                    <Button style="background-color:#1B4F9B" class="gap-2">
                        <Plus class="w-4 h-4"/>
                        New Visit
                    </Button>
                </Link>
            </div>
        </template>

        <!-- Summary Cards -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <Users class="w-5 h-5 text-blue-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Total Visits</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.total_visits }}</p>
                </div>
            </div>
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <TrendingUp class="w-5 h-5 text-emerald-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Revenue Today</p>
                    <p class="text-xl font-black text-slate-800">{{ summary.total_revenue }}</p>
                </div>
            </div>
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <AlertCircle class="w-5 h-5 text-red-500"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Unpaid</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.unpaid }}</p>
                </div>
            </div>
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                    <CheckCircle2 class="w-5 h-5 text-teal-600"/>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Completed</p>
                    <p class="text-2xl font-black text-slate-800">{{ summary.completed }}</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card rounded-xl border shadow-sm p-4 mb-5">
            <div class="flex gap-3">
                <div class="flex-1 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                    <Input v-model="search" placeholder="Search patient name or code..." class="pl-9"/>
                </div>
                <Select v-model="status">
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="All Status"/>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="in_progress">In Progress</SelectItem>
                        <SelectItem value="completed">Completed</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Visits Table -->
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden">

            <div v-if="!visits.data.length" class="py-16 text-center">
                <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <Receipt class="w-7 h-7 text-slate-300"/>
                </div>
                <p class="text-sm font-medium text-slate-400">No visits today</p>
                <p class="text-xs text-slate-300 mt-1">Click "New Visit" to register a patient</p>
            </div>

            <table v-else class="w-full">
                <thead>
                    <tr style="background-color:#0F2044">
                        <th class="text-left px-5 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Patient</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Type</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Time</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Services</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Visit Status</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Invoice</th>
                        <th class="text-left px-4 py-3.5 text-xs font-semibold text-white/70 uppercase tracking-wider">Balance</th>
                        <th class="px-4 py-3.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="visit in visits.data" :key="visit.id"
                        class="hover:bg-slate-50 transition-colors group">

                        <!-- Patient -->
                        <td class="px-5 py-3.5">
                            <p class="text-sm font-semibold text-slate-800">{{ visit.patient_name }}</p>
                            <p class="text-xs text-muted-foreground font-mono">{{ visit.patient_code }}</p>
                        </td>

                        <!-- Visit Type -->
                        <td class="px-4 py-3.5">
                            <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', visitTypeBadge[visit.visit_type]]">
                                {{ visitTypeLabel[visit.visit_type] }}
                            </span>
                            <p v-if="visit.employer_company" class="text-xs text-muted-foreground mt-0.5">
                                {{ visit.employer_company }}
                            </p>
                        </td>

                        <!-- Time -->
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-1 text-xs text-slate-500">
                                <Clock class="w-3 h-3"/>
                                {{ visit.visit_date }}
                            </div>
                        </td>

                        <!-- Services count -->
                        <td class="px-4 py-3.5">
                            <span class="text-sm font-semibold text-slate-700">
                                {{ visit.services_count }} service{{ visit.services_count !== 1 ? 's' : '' }}
                            </span>
                        </td>

                        <!-- Visit Status -->
                        <td class="px-4 py-3.5">
                            <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', visitStatusBadge[visit.status]]">
                                {{ visit.status.replace('_', ' ') }}
                            </span>
                        </td>

                        <!-- Invoice -->
                        <td class="px-4 py-3.5">
                            <div v-if="visit.invoice">
                                <p class="text-xs font-mono font-semibold text-slate-700">{{ visit.invoice.invoice_number }}</p>
                                <p class="text-xs font-bold text-slate-800">{{ visit.invoice.total_amount }}</p>
                            </div>
                            <span v-else class="text-xs text-slate-400">—</span>
                        </td>

                        <!-- Balance -->
                        <td class="px-4 py-3.5">
                            <div v-if="visit.invoice">
                                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', invoiceStatusBadge[visit.invoice.status]]">
                                    {{ visit.invoice.status }}
                                </span>
                                <p v-if="visit.invoice.status !== 'paid'"
                                    class="text-xs font-bold text-red-600 mt-0.5">
                                    {{ visit.invoice.balance }}
                                </p>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3.5">
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <Link v-if="visit.invoice"
                                    :href="route('reception.show', visit.invoice.id)">
                                    <Button variant="outline" size="sm" class="text-xs gap-1.5">
                                        <Receipt class="w-3.5 h-3.5"/>
                                        Invoice
                                    </Button>
                                </Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="visits.data.length > 0"
                class="px-5 py-4 border-t flex items-center justify-between">
                <p class="text-sm text-slate-500">
                    Showing {{ visits.from }}–{{ visits.to }} of {{ visits.total }} visits
                </p>
                <div class="flex items-center gap-1">
                    <template v-for="link in visits.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" preserve-state
                            :class="['px-3 py-1.5 text-sm rounded-lg transition-colors',
                                link.active ? 'text-white font-semibold' : 'text-slate-500 hover:bg-slate-100']"
                            :style="link.active ? 'background-color:#1B4F9B' : ''"
                            v-html="link.label"/>
                        <span v-else class="px-3 py-1.5 text-sm text-slate-300" v-html="link.label"/>
                    </template>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
