<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Receipt, Search, DollarSign, AlertTriangle,
    CheckCircle2, Clock, TrendingUp, Banknote,
    CreditCard, Smartphone, ChevronRight,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE } from '@/config/visitTypes.js'

const props = defineProps({
    invoices: Object,
    summary:  Object,
    byMethod: Object,
    filters:  Object,
})

const search    = ref(props.filters.search ?? '')
const status    = ref(props.filters.status ?? '')
const date      = ref(props.filters.date   ?? '')
const method    = ref(props.filters.method ?? '')

function doSearch() {
    router.get(route('billing.index'), {
        search: search.value,
        status: status.value,
        date:   date.value,
        method: method.value,
    }, { preserveState: true, replace: true })
}

function clearFilters() {
    search.value = ''
    status.value = ''
    date.value   = ''
    method.value = ''
    doSearch()
}

const peso = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const statusConfig = {
    unpaid:    { label: 'Unpaid',    class: 'bg-red-100 text-red-700'     },
    partial:   { label: 'Partial',   class: 'bg-amber-100 text-amber-700' },
    paid:      { label: 'Paid',      class: 'bg-emerald-100 text-emerald-700' },
    cancelled: { label: 'Void',      class: 'bg-slate-100 text-slate-500'  },
}

const methodIcon = {
    cash:       Banknote,
    gcash:      Smartphone,
    maya:       Smartphone,
    card:       CreditCard,
    philhealth: Receipt,
    other:      DollarSign,
}

const methodColor = {
    cash:       'text-emerald-600',
    gcash:      'text-blue-600',
    maya:       'text-purple-600',
    card:       'text-slate-600',
    philhealth: 'text-teal-600',
    other:      'text-slate-500',
}
</script>

<template>
    <AppLayout title="Billing">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Billing</h1>
                    <p class="text-slate-400 text-xs mt-0.5">Invoice management and payment tracking</p>
                </div>
            </div>
        </template>

        <!-- ── Summary Cards ──────────────────────────── -->
        <div class="grid grid-cols-4 gap-4 mb-5">

            <!-- Today billed -->
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <Receipt class="w-5 h-5 text-blue-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Billed Today</p>
                    <p class="text-xl font-black text-slate-800">{{ peso(summary.total_today) }}</p>
                </div>
            </div>

            <!-- Collected today -->
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <TrendingUp class="w-5 h-5 text-emerald-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Collected Today</p>
                    <p class="text-xl font-black text-emerald-700">{{ peso(summary.collected_today) }}</p>
                    <p class="text-xs text-slate-400">{{ summary.paid_today }} invoices paid</p>
                </div>
            </div>

            <!-- Unpaid -->
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-shadow"
                @click="status = 'unpaid'; doSearch()">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <AlertTriangle class="w-5 h-5 text-red-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Unpaid</p>
                    <p class="text-xl font-black text-red-600">{{ peso(summary.unpaid_balance) }}</p>
                    <p class="text-xs text-slate-400">{{ summary.unpaid_count }} invoices</p>
                </div>
            </div>

            <!-- Partial -->
            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-shadow"
                @click="status = 'partial'; doSearch()">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <Clock class="w-5 h-5 text-amber-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Partial</p>
                    <p class="text-xl font-black text-amber-600">{{ peso(summary.partial_balance) }}</p>
                    <p class="text-xs text-slate-400">{{ summary.partial_count }} invoices</p>
                </div>
            </div>
        </div>

        <!-- ── Payment method breakdown ──────────────── -->
        <div v-if="Object.keys(byMethod).length > 0"
            class="bg-card rounded-xl border shadow-sm p-4 mb-5">
            <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">
                Today's Collections by Method
            </p>
            <div class="flex items-center gap-6 flex-wrap">
                <div v-for="(total, m) in byMethod" :key="m"
                    class="flex items-center gap-2">
                    <component :is="methodIcon[m] ?? DollarSign"
                        class="w-4 h-4" :class="methodColor[m] ?? 'text-slate-500'"/>
                    <div>
                        <p class="text-xs font-semibold capitalize text-slate-700">{{ m }}</p>
                        <p class="text-sm font-black" :class="methodColor[m] ?? 'text-slate-700'">
                            {{ peso(total) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filters ────────────────────────────────── -->
        <div class="flex items-center gap-3 mb-4 flex-wrap">
            <!-- Search -->
            <div class="relative flex-1 min-w-48">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                <input v-model="search" @keyup.enter="doSearch"
                    placeholder="Invoice no., patient name or code..."
                    class="w-full h-9 pl-9 pr-4 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            </div>
            <!-- Status -->
            <select v-model="status" @change="doSearch"
                class="h-9 px-3 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="unpaid">Unpaid</option>
                <option value="partial">Partial</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Void</option>
            </select>
            <!-- Date -->
            <input v-model="date" type="date" @change="doSearch"
                class="h-9 px-3 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"/>
            <!-- Method -->
            <select v-model="method" @change="doSearch"
                class="h-9 px-3 text-sm border border-slate-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Methods</option>
                <option value="cash">Cash</option>
                <option value="gcash">GCash</option>
                <option value="maya">Maya</option>
                <option value="card">Card</option>
                <option value="philhealth">PhilHealth</option>
            </select>
            <!-- Clear -->
            <Button v-if="search || status || date || method"
                variant="outline" size="sm" class="h-9 text-xs" @click="clearFilters">
                Clear
            </Button>
            <Button size="sm" class="h-9 text-xs text-white gap-1.5" style="background:#1B4F9B;" @click="doSearch">
                <Search class="w-3.5 h-3.5"/> Search
            </Button>
        </div>

        <!-- ── Invoice Table ──────────────────────────── -->
        <div v-if="!invoices.data?.length"
            class="bg-card rounded-xl border shadow-sm py-20 text-center">
            <Receipt class="w-12 h-12 text-slate-200 mx-auto mb-3"/>
            <p class="text-slate-500 font-semibold">No invoices found</p>
            <p class="text-slate-400 text-sm mt-1">Try adjusting your filters</p>
        </div>

        <div v-else class="bg-card rounded-xl border shadow-sm overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr style="background:#0F2044">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Invoice</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Patient</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Type</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Total</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Paid</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Balance</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="inv in invoices.data" :key="inv.id"
                        class="hover:bg-slate-50 transition-colors group"
                        :class="inv.status === 'cancelled' ? 'opacity-50' : ''">

                        <!-- Invoice number -->
                        <td class="px-5 py-3.5">
                            <p class="font-mono font-bold text-blue-700 text-sm">{{ inv.invoice_number }}</p>
                            <p v-if="inv.case_number" class="text-xs font-mono text-slate-400">CN: {{ inv.case_number }}</p>
                        </td>

                        <!-- Patient -->
                        <td class="px-4 py-3.5">
                            <p class="font-semibold text-slate-800 text-sm">{{ inv.patient_name }}</p>
                            <p class="text-xs text-slate-400 font-mono">{{ inv.patient_code }}</p>
                            <p v-if="inv.employer" class="text-xs text-purple-600 font-semibold mt-0.5">{{ inv.employer }}</p>
                        </td>

                        <!-- Visit type -->
                        <td class="px-4 py-3.5">
                            <span v-if="inv.visit_type"
                                class="text-xs font-semibold px-2 py-0.5 rounded"
                                :style="{
                                    background: VISIT_TYPE_BADGE[inv.visit_type]?.bg,
                                    color: VISIT_TYPE_BADGE[inv.visit_type]?.color,
                                }">
                                {{ VISIT_TYPE_BADGE[inv.visit_type]?.label }}
                            </span>
                        </td>

                        <!-- Amounts -->
                        <td class="px-4 py-3.5 text-right font-semibold text-sm text-slate-700">
                            {{ peso(inv.total_amount) }}
                            <p v-if="inv.discount_amount > 0" class="text-xs text-emerald-600 font-normal">
                                -{{ peso(inv.discount_amount) }} disc.
                            </p>
                        </td>
                        <td class="px-4 py-3.5 text-right font-semibold text-sm text-emerald-700">
                            {{ peso(inv.paid_amount) }}
                            <!-- Payment methods used -->
                            <div class="flex items-center justify-end gap-1 mt-0.5">
                                <component v-for="m in inv.payment_methods" :key="m"
                                    :is="methodIcon[m] ?? DollarSign"
                                    class="w-3 h-3" :class="methodColor[m]"/>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-right font-black text-sm"
                            :class="inv.balance > 0 ? 'text-red-600' : 'text-slate-400'">
                            {{ inv.balance > 0 ? peso(inv.balance) : '—' }}
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-3.5">
                            <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', statusConfig[inv.status]?.class]">
                                {{ statusConfig[inv.status]?.label ?? inv.status }}
                            </span>
                        </td>

                        <!-- Date -->
                        <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                            <p>{{ inv.created_at }}</p>
                            <p v-if="inv.paid_at" class="text-emerald-600 text-xs">Paid {{ inv.paid_at }}</p>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3.5">
                            <Link :href="route('billing.show', inv.id)"
                                class="flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800
                                       opacity-0 group-hover:opacity-100 transition-opacity">
                                View <ChevronRight class="w-3 h-3"/>
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="px-5 py-3 border-t flex items-center justify-between text-xs text-slate-500">
                <span>{{ invoices.from }}–{{ invoices.to }} of {{ invoices.total }} invoices</span>
                <div class="flex items-center gap-1">
                    <template v-for="link in invoices.links" :key="link.label">
                        <Link v-if="link.url" :href="link.url" preserve-state
                            class="px-2.5 py-1 rounded border text-xs transition-colors"
                            :class="link.active ? 'text-white border-transparent' : 'border-slate-200 hover:border-slate-300'"
                            :style="link.active ? 'background:#1B4F9B' : ''"
                            v-html="link.label"/>
                        <span v-else class="px-2 py-1 text-slate-300" v-html="link.label"/>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
