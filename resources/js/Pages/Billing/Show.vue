<script setup>
import { ref } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import PrintableInvoice from '@/Components/Invoice/PrintableInvoice.vue'
import PrintableReceipt from '@/Components/Invoice/PrintableReceipt.vue'
import PrintableInvoiceForm from '@/Components/Invoice/PrintableInvoiceForm.vue'
import {
    Receipt, CheckCircle2, AlertTriangle, Clock,
    Banknote, CreditCard, Smartphone, DollarSign,
    Printer, XCircle, Plus, User, Building2,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE } from '@/config/visitTypes.js'

const props = defineProps({
    invoice:  Object,
    patient:  Object,
    visit:    Object,
    items:    Array,
    payments: Array,
})

const peso = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

// ── Payment form ──────────────────────────────────────
const showPaymentForm = ref(false)
const payForm = useForm({
    amount:           props.invoice.balance ?? '',
    method:           'cash',
    reference_number: '',
    notes:            '',
})

function submitPayment() {
    payForm.post(route('billing.payment', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => {
            showPaymentForm.value = false
            payForm.reset()
            payForm.amount = 0
        },
    })
}

// ── Discount form ─────────────────────────────────────
const showDiscountForm = ref(false)
const discForm = useForm({
    discount_amount: props.invoice.discount_amount ?? 0,
    notes:           props.invoice.notes ?? '',
})

function submitDiscount() {
    discForm.post(route('billing.discount', props.invoice.id), {
        preserveScroll: true,
        onSuccess: () => { showDiscountForm.value = false },
    })
}

// ── Void ──────────────────────────────────────────────
function voidInvoice() {
    if (!confirm('Void this invoice? This cannot be undone.')) return
    router.post(route('billing.void', props.invoice.id))
}

// ── Company billing toggle ────────────────────────────
function toggleCompanyBilling() {
    router.post(route('billing.toggle-company', props.invoice.id), {}, { preserveScroll: true })
}

// ── Print ─────────────────────────────────────────────
function printBillingInvoice() {
    const content = document.getElementById('billing-invoice-area')
    if (!content) return
    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:148mm;border:none;'
    document.body.appendChild(iframe)
    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        @page { size: 210mm 148mm landscape; margin: 0; }
        body { background:white; }
        img { max-width:100%; }
    </style></head>
    <body>${content.innerHTML}</body></html>`)
    doc.close()
    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => { if (document.body.contains(iframe)) document.body.removeChild(iframe) }, 2000)
    }
}

function printSystemReceipt() {
    const content = document.getElementById('system-receipt-area')
    if (!content) return
    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:297mm;border:none;'
    document.body.appendChild(iframe)
    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Sales Receipt</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        @page { size: A4 portrait; margin: 10mm; }
        body { background:white; font-family:Arial,sans-serif; }
        img { max-width:100%; }
    </style></head>
    <body>${content.innerHTML}</body></html>`)
    doc.close()
    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => { if (document.body.contains(iframe)) document.body.removeChild(iframe) }, 2000)
    }
}

function printFormData() {
    const content = document.getElementById('billing-form-data-area')
    if (!content) return
    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:200mm;height:130mm;border:none;'
    document.body.appendChild(iframe)
    const doc = iframe.contentDocument || iframe.contentWindow.document
    doc.open()
    doc.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        @page { size: 200mm 130mm; margin: 0; }
        body { background:transparent; }
        img { max-width:100%; }
    </style></head>
    <body>${content.innerHTML}</body></html>`)
    doc.close()
    iframe.onload = () => {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
        setTimeout(() => { if (document.body.contains(iframe)) document.body.removeChild(iframe) }, 2000)
    }
}

const statusConfig = {
    unpaid:    { label: 'Unpaid',  class: 'bg-red-100 text-red-700',        icon: AlertTriangle },
    partial:   { label: 'Partial', class: 'bg-amber-100 text-amber-700',    icon: Clock         },
    paid:      { label: 'Paid',    class: 'bg-emerald-100 text-emerald-700', icon: CheckCircle2  },
    cancelled: { label: 'Void',    class: 'bg-slate-100 text-slate-500',    icon: XCircle       },
}

const paymentMethods = [
    { value: 'cash',       label: 'Cash',       icon: Banknote    },
    { value: 'gcash',      label: 'GCash',      icon: Smartphone  },
    { value: 'maya',       label: 'Maya',       icon: Smartphone  },
    { value: 'card',       label: 'Card',       icon: CreditCard  },
    { value: 'philhealth', label: 'PhilHealth', icon: Receipt     },
    { value: 'other',      label: 'Other',      icon: DollarSign  },
]

const methodColor = {
    cash:       'text-emerald-600 bg-emerald-50',
    gcash:      'text-blue-600 bg-blue-50',
    maya:       'text-purple-600 bg-purple-50',
    card:       'text-slate-600 bg-slate-50',
    philhealth: 'text-teal-600 bg-teal-50',
    other:      'text-slate-500 bg-slate-50',
}
</script>

<template>
    <AppLayout :title="`Invoice ${invoice.invoice_number}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('billing.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-800">{{ invoice.invoice_number }}</h1>
                            <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', statusConfig[invoice.status]?.class]">
                                {{ statusConfig[invoice.status]?.label }}
                            </span>
                            <span v-if="invoice.billed_to_company"
                                class="text-xs font-bold px-2.5 py-1 rounded-full bg-purple-100 text-purple-700 flex items-center gap-1">
                                <Building2 class="w-3 h-3"/> Company Billing
                            </span>
                        </div>
                        <p class="text-slate-400 text-xs mt-0.5">{{ invoice.created_at }} · by {{ invoice.created_by }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" class="gap-2 text-xs" @click="printBillingInvoice">
                        <Printer class="w-3.5 h-3.5"/> BIR Invoice
                    </Button>
                    <Button variant="outline" size="sm" class="gap-2 text-xs" @click="printFormData"
                        style="border-color:#1B4F9B;color:#1B4F9B;">
                        <Printer class="w-3.5 h-3.5"/> Form Data
                    </Button>
                    <Button variant="outline" size="sm" class="gap-2 text-xs" @click="printSystemReceipt">
                        <Printer class="w-3.5 h-3.5"/> System Receipt
                    </Button>
                    <Button v-if="invoice.status !== 'paid' && invoice.status !== 'cancelled'"
                        size="sm" class="gap-2 text-xs text-white" style="background:#1B4F9B;"
                        @click="showPaymentForm = !showPaymentForm">
                        <Plus class="w-3.5 h-3.5"/>
                        {{ showPaymentForm ? 'Cancel' : 'Record Payment' }}
                    </Button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-3 gap-5">

            <!-- LEFT: Patient + Visit info -->
            <div class="space-y-4">

                <!-- Patient -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3 flex items-center gap-1.5">
                        <User class="w-3.5 h-3.5"/> Patient
                    </p>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                            style="background:#1B4F9B">
                            {{ patient.full_name?.charAt(0) }}
                        </div>
                        <div>
                            <Link :href="route('patients.show', patient.id)"
                                class="font-bold text-slate-800 text-sm hover:text-blue-700 transition-colors">
                                {{ patient.full_name }}
                            </Link>
                            <p class="text-xs text-slate-400 font-mono">{{ patient.patient_code }}</p>
                            <p class="text-xs text-slate-500">{{ patient.age_sex }}</p>
                        </div>
                    </div>
                    <p v-if="patient.contact" class="text-xs text-slate-500">📞 {{ patient.contact }}</p>
                </div>

                <!-- Visit -->
                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-2">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-2 flex items-center gap-1.5">
                        <Receipt class="w-3.5 h-3.5"/> Visit Details
                    </p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Type</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded"
                            :style="{
                                background: VISIT_TYPE_BADGE[visit.visit_type]?.bg,
                                color: VISIT_TYPE_BADGE[visit.visit_type]?.color,
                            }">
                            {{ VISIT_TYPE_BADGE[visit.visit_type]?.label }}
                        </span>
                    </div>
                    <div v-if="visit.case_number" class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Case No.</span>
                        <span class="font-mono font-bold text-slate-700">{{ visit.case_number }}</span>
                    </div>
                    <div v-if="visit.employer_company" class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Company</span>
                        <span class="font-semibold text-purple-700">{{ visit.employer_company }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Visit Date</span>
                        <span class="font-semibold text-slate-700">{{ visit.visit_date }}</span>
                    </div>
                    <div v-if="visit.is_field_visit" class="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200">
                        Field Visit
                    </div>
                    <Separator/>

                    <!-- Company billing toggle (only for pre-employment with employer) -->
                    <div v-if="visit.visit_type === 'pre_employment' && visit.employer_company" class="pt-1">
                        <button @click="toggleCompanyBilling"
                            :class="['w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold border transition-all',
                                invoice.billed_to_company
                                    ? 'bg-purple-100 text-purple-700 border-purple-300 hover:bg-purple-200'
                                    : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-purple-50 hover:text-purple-700 hover:border-purple-200']">
                            <Building2 class="w-3.5 h-3.5 flex-shrink-0"/>
                            {{ invoice.billed_to_company ? 'Billed to Company' : 'Mark as Company Billing' }}
                        </button>
                        <p class="text-xs text-slate-400 mt-1 pl-1">
                            {{ invoice.billed_to_company ? 'This invoice is included in company billing reports.' : 'Click to flag for company billing.' }}
                        </p>
                    </div>
                </div>

                <!-- Amount summary -->
                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-2">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-2">Summary</p>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal</span>
                        <span class="font-semibold">{{ peso(invoice.total_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Discount</span>
                        <span class="font-semibold text-emerald-600">
                            {{ invoice.discount_amount > 0 ? '-' + peso(invoice.discount_amount) : '—' }}
                        </span>
                    </div>
                    <Separator/>
                    <div class="flex justify-between text-sm font-bold">
                        <span>Amount Due</span>
                        <span>{{ peso(invoice.total_amount - invoice.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Paid</span>
                        <span class="font-semibold text-emerald-700">{{ peso(invoice.paid_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-black"
                        :class="invoice.balance > 0 ? 'text-red-600' : 'text-emerald-600'">
                        <span>Balance</span>
                        <span>{{ invoice.balance > 0 ? peso(invoice.balance) : 'PAID' }}</span>
                    </div>

                    <!-- Discount button -->
                    <Button v-if="invoice.status !== 'paid' && invoice.status !== 'cancelled'"
                        variant="outline" size="sm" class="w-full text-xs gap-1.5 mt-2"
                        @click="showDiscountForm = !showDiscountForm">
                        {{ showDiscountForm ? 'Cancel Discount' : 'Apply Discount' }}
                    </Button>

                    <!-- Void button -->
                    <Button v-if="invoice.status === 'unpaid'"
                        variant="outline" size="sm"
                        class="w-full text-xs gap-1.5 text-red-600 border-red-200 hover:bg-red-50"
                        @click="voidInvoice">
                        <XCircle class="w-3.5 h-3.5"/> Void Invoice
                    </Button>
                </div>
            </div>

            <!-- RIGHT: Items + Payments + Forms -->
            <div class="col-span-2 space-y-4">

                <!-- Payment form -->
                <div v-if="showPaymentForm"
                    class="bg-card rounded-xl border-2 border-blue-200 shadow-sm p-5 space-y-4">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <Plus class="w-4 h-4 text-blue-600"/> Record Payment
                        <span class="ml-auto text-xs font-normal text-slate-400">
                            Balance: <strong class="text-red-600">{{ peso(invoice.balance) }}</strong>
                        </span>
                    </h3>

                    <!-- Payment method selector -->
                    <div class="space-y-1.5">
                        <Label class="text-xs">Payment Method</Label>
                        <div class="grid grid-cols-3 gap-2">
                            <button v-for="m in paymentMethods" :key="m.value"
                                type="button"
                                @click="payForm.method = m.value"
                                :class="['flex items-center gap-2 px-3 py-2.5 rounded-xl border-2 text-xs font-semibold transition-all',
                                    payForm.method === m.value
                                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                                        : 'border-slate-200 text-slate-500 hover:border-slate-300']">
                                <component :is="m.icon" class="w-3.5 h-3.5 flex-shrink-0"/>
                                {{ m.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Amount + Reference -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs">Amount (₱)</Label>
                            <Input v-model="payForm.amount" type="number" step="0.01"
                                :max="invoice.balance"
                                class="font-mono font-bold text-lg h-11"/>
                            <p v-if="payForm.errors.amount" class="text-xs text-red-500">{{ payForm.errors.amount }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs">Reference No. <span class="text-slate-400">(GCash/Maya/Card)</span></Label>
                            <Input v-model="payForm.reference_number"
                                placeholder="e.g. GCash reference number"
                                class="h-11"/>
                        </div>
                    </div>

                    <!-- Quick amount buttons -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs text-slate-400">Quick:</span>
                        <button v-for="amt in [50, 100, 200, 500, 1000]" :key="amt"
                            type="button"
                            @click="payForm.amount = Math.min(amt, invoice.balance)"
                            class="text-xs px-2.5 py-1 bg-slate-100 hover:bg-blue-100 hover:text-blue-700 rounded-lg transition-colors font-semibold">
                            ₱{{ amt }}
                        </button>
                        <button type="button"
                            @click="payForm.amount = invoice.balance"
                            class="text-xs px-2.5 py-1 bg-emerald-100 text-emerald-700 hover:bg-emerald-200 rounded-lg transition-colors font-semibold">
                            Full balance ₱{{ invoice.balance }}
                        </button>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t">
                        <Button variant="outline" size="sm" @click="showPaymentForm = false">Cancel</Button>
                        <Button size="sm" class="gap-2 text-white" style="background:#1B4F9B;"
                            :disabled="payForm.processing || !payForm.amount"
                            @click="submitPayment">
                            <CheckCircle2 class="w-4 h-4"/>
                            {{ payForm.processing ? 'Saving...' : 'Confirm Payment' }}
                        </Button>
                    </div>
                </div>

                <!-- Discount form -->
                <div v-if="showDiscountForm"
                    class="bg-card rounded-xl border-2 border-emerald-200 shadow-sm p-5 space-y-3">
                    <h3 class="text-sm font-bold text-slate-800">Apply Discount</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <Label class="text-xs">Discount Amount (₱)</Label>
                            <Input v-model="discForm.discount_amount" type="number" step="0.01"
                                :max="invoice.total_amount"/>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs">Reason</Label>
                            <Input v-model="discForm.notes" placeholder="e.g. Senior citizen, PWD, employee"/>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <Button variant="outline" size="sm" @click="showDiscountForm = false">Cancel</Button>
                        <Button size="sm" class="gap-2 text-white" style="background:#10B981;"
                            :disabled="discForm.processing"
                            @click="submitDiscount">
                            Apply Discount
                        </Button>
                    </div>
                </div>

                <!-- Line items -->
                <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b">
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Services Billed</h3>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 border-b">
                                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Service</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit Price</th>
                                <th class="text-center px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Qty</th>
                                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in items" :key="item.id" class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <p class="font-semibold text-slate-800 text-sm">{{ item.service_name }}</p>
                                    <p class="text-xs font-mono text-slate-400">{{ item.service_code }}</p>
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-slate-600">{{ peso(item.unit_price) }}</td>
                                <td class="px-4 py-3 text-center text-sm text-slate-600">{{ item.quantity }}</td>
                                <td class="px-5 py-3 text-right font-bold text-slate-800">{{ peso(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-slate-50">
                                <td colspan="3" class="px-5 py-3 text-sm font-bold text-right text-slate-700">Total</td>
                                <td class="px-5 py-3 text-right text-lg font-black text-slate-800">{{ peso(invoice.total_amount) }}</td>
                            </tr>
                            <tr v-if="invoice.discount_amount > 0" class="bg-emerald-50">
                                <td colspan="3" class="px-5 py-2 text-sm font-semibold text-right text-emerald-700">Discount</td>
                                <td class="px-5 py-2 text-right font-bold text-emerald-700">-{{ peso(invoice.discount_amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Payment history -->
                <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b flex items-center justify-between">
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                            Payment History ({{ payments.length }})
                        </h3>
                        <span v-if="invoice.paid_at" class="text-xs text-emerald-600 font-semibold">
                            Fully paid {{ invoice.paid_at }}
                        </span>
                    </div>

                    <div v-if="payments.length === 0"
                        class="py-10 text-center text-slate-400 text-sm">
                        No payments recorded yet
                    </div>

                    <div v-else>
                        <div v-for="p in payments" :key="p.id"
                            class="flex items-center gap-4 px-5 py-3.5 border-b last:border-b-0 hover:bg-slate-50">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                :class="methodColor[p.method] ?? 'text-slate-500 bg-slate-50'">
                                <Banknote v-if="p.method === 'cash'" class="w-4 h-4"/>
                                <Smartphone v-else-if="['gcash','maya'].includes(p.method)" class="w-4 h-4"/>
                                <CreditCard v-else-if="p.method === 'card'" class="w-4 h-4"/>
                                <Receipt v-else class="w-4 h-4"/>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800 text-sm">{{ peso(p.amount) }}</span>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded capitalize"
                                        :class="methodColor[p.method] ?? 'text-slate-500 bg-slate-50'">
                                        {{ p.method }}
                                    </span>
                                    <span v-if="p.reference_number"
                                        class="text-xs font-mono text-slate-400">
                                        Ref: {{ p.reference_number }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-400">
                                    <span>{{ p.created_at }}</span>
                                    <span v-if="p.received_by">· by {{ p.received_by }}</span>
                                </div>
                                <p v-if="p.notes" class="text-xs text-slate-400 italic mt-0.5">{{ p.notes }}</p>
                            </div>
                            <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0"/>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>

    <div id="billing-invoice-area" style="display:none;">
        <PrintableInvoice
            :invoice="invoice"
            :patient="patient"
            :visit="visit"
            :items="items"
            :payments="payments"
        />
    </div>

    <div id="billing-form-data-area" style="display:none;">
        <PrintableInvoiceForm
            :invoice="invoice"
            :patient="patient"
            :visit="visit"
            :items="items"
            :payments="payments"
        />
    </div>

    <div id="system-receipt-area" style="display:none;">
        <PrintableReceipt
            :invoice="invoice"
            :patient="patient"
            :visit="visit"
            :items="items"
            :payments="payments"
        />
    </div>
</template>
