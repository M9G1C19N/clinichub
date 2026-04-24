<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Building2, Printer, ChevronDown, ChevronUp, FileText, Plus, CheckCircle2,
    Banknote, CreditCard, Smartphone, Receipt as ReceiptIcon, DollarSign,
    Search, X, Filter,
} from 'lucide-vue-next'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'

const props = defineProps({
    companies: Array,
    summary:   Object,
    filters:   Object,
})

const peso = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const visitTypeLabel = {
    pre_employment: 'Pre-Employment',
    annual_pe:      'Annual PE',
    exit_pe:        'Exit PE',
}

const visitTypeBadge = {
    pre_employment: 'bg-blue-100 text-blue-700',
    annual_pe:      'bg-violet-100 text-violet-700',
    exit_pe:        'bg-orange-100 text-orange-700',
}

const statusClass = {
    unpaid:  'bg-red-100 text-red-700',
    partial: 'bg-amber-100 text-amber-700',
    paid:    'bg-emerald-100 text-emerald-700',
}

// ── Expand/collapse ────────────────────────────────────
const expanded = ref({})
function toggle(company) {
    expanded.value[company] = !expanded.value[company]
}

// ── Filters ────────────────────────────────────────────
const filterForm = ref({
    date_from:  props.filters?.date_from  ?? '',
    date_to:    props.filters?.date_to    ?? '',
    visit_type: props.filters?.visit_type ?? '',
    status:     props.filters?.status     ?? '',
    company:    props.filters?.company    ?? '',
})

const hasActiveFilters = () =>
    filterForm.value.date_from  ||
    filterForm.value.date_to    ||
    filterForm.value.visit_type ||
    filterForm.value.status     ||
    filterForm.value.company

function applyFilters() {
    router.get(route('billing.company-billing'), filterForm.value, {
        preserveScroll: true,
        preserveState:  true,
    })
}

function resetFilters() {
    filterForm.value = { date_from: '', date_to: '', visit_type: '', status: '', company: '' }
    router.get(route('billing.company-billing'), {}, { preserveScroll: true })
}

// ── Company payment form ───────────────────────────────
const payingCompany = ref(null)
const payForm = useForm({
    company:          '',
    amount:           '',
    method:           'cash',
    reference_number: '',
    notes:            '',
})

function openPayForm(c) {
    payingCompany.value  = c.company
    payForm.company      = c.company
    payForm.amount       = c.total_balance.toFixed(2)
    payForm.method       = 'cash'
    payForm.reference_number = ''
    payForm.notes        = ''
}

function closePayForm() {
    payingCompany.value = null
    payForm.reset()
}

function submitCompanyPayment() {
    payForm.post(route('billing.company-payment'), {
        preserveScroll: true,
        onSuccess: () => closePayForm(),
    })
}

const paymentMethods = [
    { value: 'cash',       label: 'Cash',       icon: Banknote    },
    { value: 'gcash',      label: 'GCash',      icon: Smartphone  },
    { value: 'maya',       label: 'Maya',       icon: Smartphone  },
    { value: 'card',       label: 'Card',       icon: CreditCard  },
    { value: 'philhealth', label: 'PhilHealth', icon: ReceiptIcon },
    { value: 'other',      label: 'Other',      icon: DollarSign  },
]

// ── Print helpers ──────────────────────────────────────
function printSOA(companyName) {
    const id = 'soa-' + companyName.replace(/\s+/g, '-')
    const content = document.getElementById(id)
    if (!content) return
    openPrintWindow(content.innerHTML)
}

function printAllSOA() {
    const parts = props.companies.map(c => {
        const id = 'soa-' + c.company.replace(/\s+/g, '-')
        const el = document.getElementById(id)
        return el ? `<div class="soa-page">${el.innerHTML}</div>` : ''
    }).filter(Boolean)
    if (!parts.length) return
    openPrintWindow(parts.join(''), true)
}

function openPrintWindow(bodyContent, multiPage = false) {
    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:297mm;border:none;'
    document.body.appendChild(iframe)
    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        @page { size: A4 portrait; margin: 12mm; }
        body { background:white; font-family:Arial,sans-serif; font-size:11px; color:#111; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        img { max-width:100%; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:6px 8px; text-align:left; }
        th { border-bottom:1.5px solid #111; font-size:10px; text-transform:uppercase; }
        td { border-bottom:1px solid #e5e5e5; }
        ${multiPage ? '.soa-page { page-break-after:always; } .soa-page:last-child { page-break-after:avoid; }' : ''}
    </style></head>
    <body>${bodyContent}</body></html>`)
    doc.close()
    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => { if (document.body.contains(iframe)) document.body.removeChild(iframe) }, 2000)
    }
}
</script>

<template>
    <AppLayout title="Company Billing">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('billing.index')">
                        <button class="h-8 w-8 flex items-center justify-center rounded-md border border-slate-200 hover:bg-slate-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Company Billing</h1>
                        <p class="text-slate-400 text-xs">Pre-Employment, Annual PE &amp; Exit PE invoices billed to companies</p>
                    </div>
                </div>
                <button v-if="companies.length"
                    @click="printAllSOA"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl text-white text-sm font-bold shadow-sm hover:opacity-90"
                    style="background:#0F2044;">
                    <Printer class="w-4 h-4"/> Print All SOA
                </button>
            </div>
        </template>

        <div class="space-y-5">

            <!-- Summary cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                        <Building2 class="w-5 h-5 text-purple-600"/>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Companies</p>
                        <p class="text-2xl font-black text-slate-800">{{ summary.company_count }}</p>
                    </div>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                        <span class="text-red-600 font-black text-sm">₱</span>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 font-medium">Outstanding</p>
                        <p class="text-lg font-black text-red-600">{{ peso(summary.total_balance) }}</p>
                    </div>
                </div>
                <!-- Per-type breakdowns -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">Pre-Emp</span>
                        <span class="text-xs text-slate-400">{{ summary.by_type.pre_employment.count }} inv</span>
                    </div>
                    <p class="text-sm font-black" :class="summary.by_type.pre_employment.balance > 0 ? 'text-red-600' : 'text-emerald-600'">
                        {{ peso(summary.by_type.pre_employment.balance) }}
                    </p>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-violet-100 text-violet-700">Annual PE</span>
                        <span class="text-xs text-slate-400">{{ summary.by_type.annual_pe.count }} inv</span>
                    </div>
                    <p class="text-sm font-black" :class="summary.by_type.annual_pe.balance > 0 ? 'text-red-600' : 'text-emerald-600'">
                        {{ peso(summary.by_type.annual_pe.balance) }}
                    </p>
                </div>
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="text-xs font-bold px-1.5 py-0.5 rounded bg-orange-100 text-orange-700">Exit PE</span>
                        <span class="text-xs text-slate-400">{{ summary.by_type.exit_pe.count }} inv</span>
                    </div>
                    <p class="text-sm font-black" :class="summary.by_type.exit_pe.balance > 0 ? 'text-red-600' : 'text-emerald-600'">
                        {{ peso(summary.by_type.exit_pe.balance) }}
                    </p>
                </div>
            </div>

            <!-- Filters bar -->
            <div class="bg-card rounded-xl border shadow-sm p-4">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <!-- Date From -->
                    <div>
                        <label class="text-xs font-semibold text-slate-500 block mb-1">Date From</label>
                        <input v-model="filterForm.date_from" type="date"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"/>
                    </div>
                    <!-- Date To -->
                    <div>
                        <label class="text-xs font-semibold text-slate-500 block mb-1">Date To</label>
                        <input v-model="filterForm.date_to" type="date"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"/>
                    </div>
                    <!-- Visit Type -->
                    <div>
                        <label class="text-xs font-semibold text-slate-500 block mb-1">Visit Type</label>
                        <select v-model="filterForm.visit_type"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                            <option value="">All Types</option>
                            <option value="pre_employment">Pre-Employment</option>
                            <option value="annual_pe">Annual PE</option>
                            <option value="exit_pe">Exit PE</option>
                        </select>
                    </div>
                    <!-- Status -->
                    <div>
                        <label class="text-xs font-semibold text-slate-500 block mb-1">Status</label>
                        <select v-model="filterForm.status"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                            <option value="">All Statuses</option>
                            <option value="unpaid">Unpaid / Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <!-- Company Search -->
                    <div>
                        <label class="text-xs font-semibold text-slate-500 block mb-1">Company</label>
                        <div class="relative">
                            <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none"/>
                            <input v-model="filterForm.company" type="text" placeholder="Search company..."
                                class="w-full border rounded-lg pl-8 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white"/>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-3">
                    <button v-if="hasActiveFilters()" @click="resetFilters"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold text-slate-600 border-slate-200 hover:bg-slate-50 transition-colors">
                        <X class="w-3.5 h-3.5"/> Clear
                    </button>
                    <button @click="applyFilters"
                        class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg text-xs font-bold text-white transition-colors"
                        style="background:#0F2044;">
                        <Filter class="w-3.5 h-3.5"/> Apply Filters
                    </button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="companies.length === 0"
                class="text-center py-20 text-slate-400">
                <Building2 class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p class="font-semibold">No company billing records found.</p>
                <p class="text-sm mt-1">Try adjusting the filters, or mark invoices as "Company Billing" from the invoice detail page.</p>
            </div>

            <!-- Company cards -->
            <div v-for="c in companies" :key="c.company" class="bg-card rounded-xl border shadow-sm overflow-hidden">

                <!-- Company header row -->
                <div class="flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-slate-50 transition-colors"
                    @click="toggle(c.company)">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                            :class="c.unpaid_count === 0 ? 'bg-emerald-100' : 'bg-purple-100'">
                            <Building2 class="w-5 h-5" :class="c.unpaid_count === 0 ? 'text-emerald-600' : 'text-purple-600'"/>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-bold text-slate-800 text-sm">{{ c.company }}</p>
                                <span v-if="c.unpaid_count === 0"
                                    class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                                    Fully Settled
                                </span>
                            </div>
                            <div class="flex items-center gap-3 mt-0.5">
                                <p class="text-xs text-slate-400">{{ c.invoice_count }} invoice{{ c.invoice_count !== 1 ? 's' : '' }} · {{ c.unpaid_count }} unpaid/partial</p>
                                <span v-if="c.by_type.pre_employment.count" class="text-xs font-semibold px-1.5 py-0.5 rounded bg-blue-50 text-blue-600">PE ×{{ c.by_type.pre_employment.count }}</span>
                                <span v-if="c.by_type.annual_pe.count" class="text-xs font-semibold px-1.5 py-0.5 rounded bg-violet-50 text-violet-600">Annual ×{{ c.by_type.annual_pe.count }}</span>
                                <span v-if="c.by_type.exit_pe.count" class="text-xs font-semibold px-1.5 py-0.5 rounded bg-orange-50 text-orange-600">Exit ×{{ c.by_type.exit_pe.count }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-xs text-slate-400">Total Outstanding</p>
                            <p class="font-black text-base" :class="c.total_balance > 0 ? 'text-red-600' : 'text-emerald-600'">
                                {{ c.total_balance > 0 ? peso(c.total_balance) : 'PAID' }}
                            </p>
                        </div>
                        <button v-if="c.unpaid_count > 0"
                            @click.stop="openPayForm(c)"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold text-emerald-700 border-emerald-200 bg-emerald-50 hover:bg-emerald-100 transition-colors">
                            <Plus class="w-3.5 h-3.5"/> Record Payment
                        </button>
                        <button @click.stop="printSOA(c.company)"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold text-purple-700 border-purple-200 bg-purple-50 hover:bg-purple-100 transition-colors">
                            <Printer class="w-3.5 h-3.5"/> Print SOA
                        </button>
                        <ChevronDown v-if="!expanded[c.company]" class="w-4 h-4 text-slate-400"/>
                        <ChevronUp v-else class="w-4 h-4 text-slate-400"/>
                    </div>
                </div>

                <!-- Company payment form (inline) -->
                <div v-if="payingCompany === c.company"
                    class="border-t border-emerald-200 bg-emerald-50 px-5 py-4 space-y-3">
                    <div class="flex items-center justify-between mb-1">
                        <h4 class="text-sm font-bold text-emerald-800 flex items-center gap-2">
                            <CheckCircle2 class="w-4 h-4"/> Record Company Payment — {{ c.company }}
                        </h4>
                        <button @click="closePayForm" class="text-xs text-slate-400 hover:text-slate-600">Cancel</button>
                    </div>
                    <p class="text-xs text-emerald-700">
                        Payment will be applied to <strong>{{ c.unpaid_count }}</strong> unpaid/partial invoice(s), oldest first.
                        Outstanding balance: <strong>{{ peso(c.total_balance) }}</strong>
                    </p>

                    <div class="grid grid-cols-6 gap-2">
                        <button v-for="m in paymentMethods" :key="m.value"
                            type="button"
                            @click="payForm.method = m.value"
                            :class="['flex items-center gap-1.5 px-2.5 py-2 rounded-xl border-2 text-xs font-semibold transition-all',
                                payForm.method === m.value
                                    ? 'border-emerald-500 bg-white text-emerald-700'
                                    : 'border-slate-200 bg-white text-slate-500 hover:border-emerald-300']">
                            <component :is="m.icon" class="w-3.5 h-3.5 flex-shrink-0"/>
                            {{ m.label }}
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600 block mb-1">Amount (₱)</label>
                            <input v-model="payForm.amount" type="number" step="0.01" min="0.01"
                                class="w-full border rounded-lg px-3 py-2 text-sm font-bold font-mono focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white"/>
                            <p v-if="payForm.errors.amount" class="text-xs text-red-500 mt-1">{{ payForm.errors.amount }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600 block mb-1">Reference No.</label>
                            <input v-model="payForm.reference_number" type="text"
                                placeholder="Check no. / GCash ref"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white"/>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600 block mb-1">Notes</label>
                            <input v-model="payForm.notes" type="text"
                                placeholder="Optional notes"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white"/>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-slate-500">Quick fill:</span>
                        <button type="button"
                            @click="payForm.amount = c.total_balance.toFixed(2)"
                            class="text-xs px-2.5 py-1 bg-emerald-200 text-emerald-800 hover:bg-emerald-300 rounded-lg font-semibold transition-colors">
                            Full balance {{ peso(c.total_balance) }}
                        </button>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button @click="submitCompanyPayment"
                            :disabled="payForm.processing || !payForm.amount"
                            class="flex items-center gap-2 px-5 py-2 rounded-xl text-white text-sm font-bold shadow-sm hover:opacity-90 disabled:opacity-50"
                            style="background:#059669;">
                            <CheckCircle2 class="w-4 h-4"/>
                            {{ payForm.processing ? 'Processing...' : 'Confirm Payment' }}
                        </button>
                    </div>
                </div>

                <!-- Invoice list (expandable) -->
                <div v-if="expanded[c.company]" class="border-t">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 border-b">
                                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Invoice</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Patient</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Type</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Paid</th>
                                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="inv in c.invoices" :key="inv.id" class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <Link :href="route('billing.show', inv.id)"
                                        class="font-mono font-bold text-blue-700 hover:underline text-xs">
                                        {{ inv.invoice_number }}
                                    </Link>
                                    <span v-if="inv.billed_to_company"
                                        class="ml-1.5 text-xs font-semibold text-purple-600 bg-purple-50 px-1.5 py-0.5 rounded">
                                        Company
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-800 text-sm">{{ inv.patient_name }}</p>
                                    <p class="text-xs font-mono text-slate-400">{{ inv.patient_code }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', visitTypeBadge[inv.visit_type] ?? 'bg-slate-100 text-slate-500']">
                                        {{ visitTypeLabel[inv.visit_type] ?? inv.visit_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ inv.visit_date ?? inv.created_at }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full capitalize', statusClass[inv.status] ?? 'bg-slate-100 text-slate-500']">
                                        {{ inv.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-slate-600">{{ peso(inv.total_amount - inv.discount_amount) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-emerald-600 font-semibold">{{ peso(inv.paid_amount) }}</td>
                                <td class="px-5 py-3 text-right font-bold"
                                    :class="inv.balance > 0 ? 'text-red-600' : 'text-emerald-600'">
                                    {{ inv.balance > 0 ? peso(inv.balance) : 'Paid' }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100 border-t-2 border-slate-300">
                                <td colspan="5" class="px-5 py-3 text-sm font-bold text-slate-700 text-right">Totals</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-800">{{ peso(c.invoices.reduce((s, i) => s + (i.total_amount - i.discount_amount), 0)) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-emerald-600">{{ peso(c.invoices.reduce((s, i) => s + i.paid_amount, 0)) }}</td>
                                <td class="px-5 py-3 text-right font-black text-red-600 text-base">{{ peso(c.total_balance) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

        <!-- Hidden SOA print areas -->
        <div style="display:none;">
            <div v-for="c in companies" :key="'soa-' + c.company"
                :id="'soa-' + c.company.replace(/\s+/g, '-')">

                <!-- Letterhead -->
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img :src="CLINIC_LOGO" style="width:56px;height:56px;object-fit:contain;flex-shrink:0;"/>
                        <div>
                            <div style="font-weight:900;font-size:13px;line-height:1.2;color:#111;">SAINT PETER DIAGNOSTICS AND LABORATORY</div>
                            <div style="font-size:10px;color:#333;font-weight:600;">{{ CLINIC_INFO.subtitle }}</div>
                            <div style="font-size:9.5px;color:#444;">{{ CLINIC_INFO.addressFull }}</div>
                            <div style="font-size:9.5px;color:#444;">Tel: {{ CLINIC_INFO.phone }} / {{ CLINIC_INFO.phoneSmart }}</div>
                            <div style="font-size:9px;color:#555;">{{ CLINIC_INFO.email }}</div>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:20px;font-weight:900;letter-spacing:1px;color:#111;line-height:1;">STATEMENT OF ACCOUNT</div>
                        <div style="font-size:9px;color:#555;margin-top:4px;">
                            As of <strong>{{ new Date().toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' }) }}</strong>
                        </div>
                        <div v-if="filters?.date_from || filters?.date_to" style="font-size:9px;color:#555;margin-top:2px;">
                            Period: <strong>{{ filters.date_from || '—' }}</strong> to <strong>{{ filters.date_to || '—' }}</strong>
                        </div>
                    </div>
                </div>

                <div style="height:2px;background:#111;margin-bottom:6px;"></div>

                <!-- Billed To -->
                <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #aaa;">
                    <div>
                        <div style="font-size:9px;color:#555;text-transform:uppercase;letter-spacing:.5px;">Billed To</div>
                        <div style="font-size:15px;font-weight:900;text-transform:uppercase;color:#111;">{{ c.company }}</div>
                    </div>
                    <div style="text-align:right;font-size:9.5px;color:#555;">
                        <div>Total Invoices: <strong style="color:#111;">{{ c.invoice_count }}</strong></div>
                        <div>Outstanding Balance: <strong style="color:#dc2626;font-size:12px;">{{ peso(c.total_balance) }}</strong></div>
                    </div>
                </div>

                <!-- Invoice table -->
                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:6px 8px;border-bottom:1.5px solid #111;font-size:10px;text-transform:uppercase;">Invoice</th>
                            <th style="text-align:left;padding:6px 8px;border-bottom:1.5px solid #111;font-size:10px;text-transform:uppercase;">Patient</th>
                            <th style="text-align:left;padding:6px 8px;border-bottom:1.5px solid #111;font-size:10px;text-transform:uppercase;">Type</th>
                            <th style="text-align:left;padding:6px 8px;border-bottom:1.5px solid #111;font-size:10px;text-transform:uppercase;">Date</th>
                            <th style="text-align:left;padding:6px 8px;border-bottom:1.5px solid #111;font-size:10px;text-transform:uppercase;">Status</th>
                            <th style="text-align:right;padding:6px 8px;border-bottom:1.5px solid #111;font-size:10px;text-transform:uppercase;">Amount</th>
                            <th style="text-align:right;padding:6px 8px;border-bottom:1.5px solid #111;font-size:10px;text-transform:uppercase;">Paid</th>
                            <th style="text-align:right;padding:6px 8px;border-bottom:1.5px solid #111;font-size:10px;text-transform:uppercase;">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="inv in c.invoices" :key="inv.id">
                            <td style="padding:6px 8px;border-bottom:1px solid #e5e5e5;font-family:monospace;font-size:10px;">{{ inv.invoice_number }}</td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e5e5e5;">
                                <div style="font-weight:600;">{{ inv.patient_name }}</div>
                                <div style="font-size:9px;color:#888;font-family:monospace;">{{ inv.patient_code }}</div>
                            </td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e5e5e5;font-size:10px;">
                                {{ visitTypeLabel[inv.visit_type] ?? inv.visit_type }}
                            </td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e5e5e5;font-size:10px;color:#555;">{{ inv.visit_date ?? inv.created_at }}</td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e5e5e5;font-size:10px;text-transform:capitalize;">{{ inv.status }}</td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e5e5e5;text-align:right;">{{ peso(inv.total_amount - inv.discount_amount) }}</td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e5e5e5;text-align:right;color:#059669;">{{ peso(inv.paid_amount) }}</td>
                            <td style="padding:6px 8px;border-bottom:1px solid #e5e5e5;text-align:right;font-weight:700;"
                                :style="{ color: inv.balance > 0 ? '#dc2626' : '#059669' }">
                                {{ inv.balance > 0 ? peso(inv.balance) : 'PAID' }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <!-- Per-type subtotals (only shown when 2+ types present) -->
                        <template v-if="[c.by_type.pre_employment.count, c.by_type.annual_pe.count, c.by_type.exit_pe.count].filter(Boolean).length > 1">
                            <tr v-if="c.by_type.pre_employment.count" style="background:#f8f8f8;">
                                <td colspan="7" style="padding:5px 8px;font-size:10px;text-align:right;color:#555;">
                                    Pre-Employment subtotal ({{ c.by_type.pre_employment.count }} invoices)
                                </td>
                                <td style="padding:5px 8px;text-align:right;font-weight:700;font-size:10px;"
                                    :style="{ color: c.by_type.pre_employment.balance > 0 ? '#dc2626' : '#059669' }">
                                    {{ peso(c.by_type.pre_employment.balance) }}
                                </td>
                            </tr>
                            <tr v-if="c.by_type.annual_pe.count" style="background:#f8f8f8;">
                                <td colspan="7" style="padding:5px 8px;font-size:10px;text-align:right;color:#555;">
                                    Annual PE subtotal ({{ c.by_type.annual_pe.count }} invoices)
                                </td>
                                <td style="padding:5px 8px;text-align:right;font-weight:700;font-size:10px;"
                                    :style="{ color: c.by_type.annual_pe.balance > 0 ? '#dc2626' : '#059669' }">
                                    {{ peso(c.by_type.annual_pe.balance) }}
                                </td>
                            </tr>
                            <tr v-if="c.by_type.exit_pe.count" style="background:#f8f8f8;">
                                <td colspan="7" style="padding:5px 8px;font-size:10px;text-align:right;color:#555;">
                                    Exit PE subtotal ({{ c.by_type.exit_pe.count }} invoices)
                                </td>
                                <td style="padding:5px 8px;text-align:right;font-weight:700;font-size:10px;"
                                    :style="{ color: c.by_type.exit_pe.balance > 0 ? '#dc2626' : '#059669' }">
                                    {{ peso(c.by_type.exit_pe.balance) }}
                                </td>
                            </tr>
                        </template>
                        <tr style="border-top:2px solid #111;">
                            <td colspan="7" style="padding:8px;font-weight:900;text-align:right;font-size:12px;">TOTAL OUTSTANDING BALANCE</td>
                            <td style="padding:8px;text-align:right;font-weight:900;font-size:14px;color:#dc2626;">{{ peso(c.total_balance) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Footer note -->
                <div style="margin-top:24px;font-size:10px;color:#555;border-top:1px solid #ccc;padding-top:8px;">
                    This is a computer-generated Statement of Account. For inquiries, please contact the clinic billing department.
                </div>
            </div>
        </div>

    </AppLayout>
</template>
