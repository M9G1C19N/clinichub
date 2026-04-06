<script setup>
import { ref, onMounted } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import PrintableInvoice from '@/Components/Invoice/PrintableInvoice.vue'
import PrintableReceipt from '@/Components/Invoice/PrintableReceipt.vue'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Receipt, CreditCard, CheckCircle2,
    Clock, User, Building2, Printer, Hash,
} from 'lucide-vue-next'
import { printQueueSlip } from '@/utils/printQueueSlip.js'

const page = usePage()

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

const props = defineProps({
    invoice: Object,
    patient: Object,
    visit:   Object,
    items:   Array,
    payments:Array,
    ticket:  { type: Object, default: null },
})

// ── Queue Slip Print Modal ──────────────────────────────
const showSlipModal = ref(false)
const slipTicket    = ref(props.ticket)

// Auto-show print dialog when a new ticket was just created (flash)
onMounted(() => {
    const flashTicket = page.props.flash?.newTicket
    if (flashTicket) {
        slipTicket.value    = flashTicket
        showSlipModal.value = true
    }
})

const showPaymentForm = ref(false)

const paymentForm = useForm({
    amount:           props.invoice.balance,
    method:           'cash',
    reference_number: '',
    notes:            '',
})

function submitPayment() {
    paymentForm.post(route('reception.payment', props.invoice.id), {
        onSuccess: () => {
            showPaymentForm.value = false
            paymentForm.reset()
        }
    })
}

const statusColor = {
    unpaid:    'text-red-600 bg-red-100',
    partial:   'text-amber-700 bg-amber-100',
    paid:      'text-emerald-700 bg-emerald-100',
    cancelled: 'text-slate-600 bg-slate-100',
}

const methodLabel = {
    cash:       'Cash',
    gcash:      'GCash',
    maya:       'Maya',
    card:       'Credit/Debit Card',
    philhealth: 'PhilHealth',
    other:      'Other',
}

const visitTypeLabel = {
    opd:            'OPD',
    pre_employment: 'Pre-Employment',
    follow_up:      'Follow-up',
    lab_only:       'Lab Only',
}
</script>

<template>
    <AppLayout :title="invoice.invoice_number">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('reception.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-800">{{ invoice.invoice_number }}</h1>
                            <span :class="['text-xs font-bold px-2.5 py-1 rounded-full uppercase', statusColor[invoice.status]]">
                                {{ invoice.status }}
                            </span>
                        </div>
                        <p class="text-slate-400 text-xs mt-0.5">
                            Created {{ invoice.created_at }} by {{ invoice.created_by }}
                        </p>
                        <div v-if="visit.case_number" class="text-xs text-slate-500">
                            Case No.: <strong class="text-slate-800 font-mono text-sm">{{ visit.case_number }}</strong>
                        </div>
                        <div v-else-if="visit.is_field_visit"
                            class="text-xs font-semibold px-2 py-0.5 rounded"
                            style="background:#fffbeb; color:#b45309;">
                            Field Visit — No case no. yet
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="slipTicket" variant="outline" size="sm"
                        class="gap-2 no-print"
                        @click="showSlipModal = true">
                        <Hash class="w-4 h-4"/>
                        Queue Slip
                    </Button>
                    <Button variant="outline" size="sm" class="gap-2 no-print" @click="printBillingInvoice">
                        <Printer class="w-4 h-4"/>
                        BIR Invoice
                    </Button>
                    <Button variant="outline" size="sm" class="gap-2 no-print" @click="printSystemReceipt">
                        <Printer class="w-4 h-4"/>
                        System Receipt
                    </Button>
                    <Button v-if="invoice.status !== 'paid' && invoice.status !== 'cancelled'"
                        @click="showPaymentForm = !showPaymentForm"
                        class="gap-2" style="background-color:#1B4F9B">
                        <CreditCard class="w-4 h-4"/>
                        Record Payment
                    </Button>
                </div>
            </div>
        </template>

        <div class="flex gap-5 items-start">

            <!-- ── LEFT: Invoice Details ─────────── -->
            <div class="flex-1 min-w-0 space-y-4">

                <!-- Patient + Visit Info -->
                <div class="bg-card rounded-xl border shadow-sm p-5">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3 flex items-center gap-2">
                                <User class="w-3.5 h-3.5"/> Patient
                            </p>
                            <p class="text-base font-bold text-slate-800">{{ patient.full_name }}</p>
                            <p class="text-xs text-muted-foreground font-mono">{{ patient.patient_code }}</p>
                            <p class="text-xs text-muted-foreground mt-1">{{ patient.age_sex }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3 flex items-center gap-2">
                                <Building2 class="w-3.5 h-3.5"/> Visit Details
                            </p>
                            <p class="text-sm font-semibold text-slate-700">
                                {{ visitTypeLabel[visit.visit_type] }}
                            </p>
                            <p v-if="visit.employer_company" class="text-xs text-muted-foreground">
                                {{ visit.employer_company }}
                            </p>
                            <p class="text-xs text-muted-foreground flex items-center gap-1 mt-1">
                                <Clock class="w-3 h-3"/>
                                {{ visit.visit_date }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Services</h3>
                    </div>

                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Service</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Code</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Unit Price</th>
                                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase">Qty</th>
                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in items" :key="item.id">
                                <td class="px-5 py-3.5 text-sm font-medium text-slate-700">{{ item.service_name }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs font-mono font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded">
                                        {{ item.service_code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right text-sm text-slate-700">
                                    ₱ {{ Number(item.unit_price).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                                </td>
                                <td class="px-4 py-3.5 text-right text-sm text-slate-700">{{ item.quantity }}</td>
                                <td class="px-5 py-3.5 text-right text-sm font-semibold text-slate-800">
                                    ₱ {{ Number(item.subtotal).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Totals -->
                    <div class="px-5 py-4 border-t bg-slate-50 space-y-2">
                        <div class="flex justify-between text-sm text-slate-500">
                            <span>Subtotal</span>
                            <span>₱ {{ Number(invoice.total_amount).toLocaleString('en-PH', {minimumFractionDigits:2}) }}</span>
                        </div>
                        <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm text-emerald-600">
                            <span>Discount</span>
                            <span>- ₱ {{ Number(invoice.discount_amount).toLocaleString('en-PH', {minimumFractionDigits:2}) }}</span>
                        </div>
                        <div class="flex justify-between text-sm text-emerald-600">
                            <span>Paid</span>
                            <span>- ₱ {{ Number(invoice.paid_amount).toLocaleString('en-PH', {minimumFractionDigits:2}) }}</span>
                        </div>
                        <Separator/>
                        <div class="flex justify-between text-base font-black">
                            <span style="color:#1B4F9B">Balance Due</span>
                            <span style="color:#1B4F9B">
                                ₱ {{ Number(invoice.balance).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div v-if="payments.length > 0" class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Payment History</h3>
                    </div>
                    <div class="divide-y divide-border">
                        <div v-for="pmt in payments" :key="pmt.id"
                            class="px-5 py-3.5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600"/>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ methodLabel[pmt.method] }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ pmt.created_at }} · by {{ pmt.received_by }}</p>
                                    <p v-if="pmt.reference_number" class="text-xs text-slate-500 font-mono">
                                        Ref: {{ pmt.reference_number }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-sm font-bold text-emerald-700">
                                + ₱ {{ Number(pmt.amount).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── RIGHT: Payment Form ───────────── -->
            <div class="w-72 flex-shrink-0 space-y-4">

                <!-- Status card -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Payment Status</p>
                    <div class="text-center py-3">
                        <p class="text-3xl font-black" style="color:#1B4F9B">
                            ₱ {{ Number(invoice.balance).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                        </p>
                        <p class="text-xs text-muted-foreground mt-1">Balance Due</p>
                        <span :class="['mt-2 inline-block text-xs font-bold px-3 py-1 rounded-full uppercase', statusColor[invoice.status]]">
                            {{ invoice.status }}
                        </span>
                    </div>
                </div>

                <!-- Payment Form -->
                <div v-if="showPaymentForm && invoice.status !== 'paid'"
                    class="bg-card rounded-xl border shadow-sm p-4 space-y-3">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Record Payment</p>

                    <div class="space-y-1.5">
                        <Label class="text-xs">Amount (₱) <span class="text-red-500">*</span></Label>
                        <Input v-model="paymentForm.amount"
                            type="number" min="0.01" :max="invoice.balance" step="0.01"
                            :class="paymentForm.errors.amount ? 'border-red-400' : ''"/>
                        <p v-if="paymentForm.errors.amount" class="text-xs text-red-500">{{ paymentForm.errors.amount }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs">Payment Method</Label>
                        <Select v-model="paymentForm.method">
                            <SelectTrigger class="h-8 text-xs"><SelectValue/></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="cash">Cash</SelectItem>
                                <SelectItem value="gcash">GCash</SelectItem>
                                <SelectItem value="maya">Maya</SelectItem>
                                <SelectItem value="card">Credit/Debit Card</SelectItem>
                                <SelectItem value="philhealth">PhilHealth</SelectItem>
                                <SelectItem value="other">Other</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div v-if="paymentForm.method !== 'cash'" class="space-y-1.5">
                        <Label class="text-xs">Reference Number</Label>
                        <Input v-model="paymentForm.reference_number"
                            placeholder="Transaction ref #" class="h-8 text-xs"/>
                    </div>

                    <!-- Quick amount buttons -->
                    <div>
                        <p class="text-xs text-muted-foreground mb-1.5">Quick amount</p>
                        <div class="flex gap-1.5 flex-wrap">
                            <button v-for="amt in [invoice.balance, 500, 1000]" :key="amt"
                                type="button"
                                @click="paymentForm.amount = amt"
                                class="text-xs px-2.5 py-1 border rounded-lg hover:bg-slate-50 transition-colors font-semibold text-slate-600">
                                ₱{{ Number(amt).toLocaleString() }}
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button type="button" variant="outline" size="sm" class="flex-1 text-xs"
                            @click="showPaymentForm = false">Cancel</Button>
                        <Button type="button" size="sm" class="flex-1 text-xs gap-1"
                            style="background-color:#1B4F9B"
                            :disabled="paymentForm.processing"
                            @click="submitPayment">
                            <CheckCircle2 class="w-3.5 h-3.5"/>
                            {{ paymentForm.processing ? 'Saving...' : 'Confirm' }}
                        </Button>
                    </div>
                </div>

                <!-- Paid confirmation -->
                <div v-if="invoice.status === 'paid'"
                    class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
                    <CheckCircle2 class="w-10 h-10 text-emerald-500 mx-auto mb-2"/>
                    <p class="text-sm font-bold text-emerald-700">Fully Paid</p>
                    <p class="text-xs text-emerald-600 mt-0.5">{{ invoice.paid_at }}</p>
                </div>

            </div>
        </div>

    </AppLayout>

     <!-- Printable Invoice — hidden on screen, visible only on print -->
<div id="billing-invoice-area" style="display:none;">
    <PrintableInvoice
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

<!-- ── Queue Slip Print Modal ──────────────────────────── -->
<Teleport to="body">
    <div v-if="showSlipModal && slipTicket"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 no-print"
        style="background:rgba(0,0,0,.55)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xs overflow-hidden">
            <!-- Header -->
            <div class="px-5 py-4 border-b flex items-center justify-between" style="background:#0F2044">
                <div class="flex items-center gap-2">
                    <Printer class="w-4 h-4 text-white"/>
                    <h2 class="text-sm font-bold text-white">Queue Slip</h2>
                </div>
                <button @click="showSlipModal = false"
                    class="text-white/60 hover:text-white text-xl leading-none w-7 h-7 flex items-center justify-center rounded-lg hover:bg-white/10">
                    &times;
                </button>
            </div>

            <!-- Slip Preview -->
            <div class="p-5">
                <div class="border-2 border-slate-200 rounded-xl p-4 text-center space-y-2 bg-slate-50">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Saint Peter Diagnostics</p>
                    <div class="text-5xl font-black text-slate-800 tracking-wider font-mono leading-none py-2">
                        {{ slipTicket.ticket_number }}
                    </div>
                    <span class="inline-block text-xs font-bold px-3 py-1 rounded-full text-white"
                        :style="{
                            background: slipTicket.priority === 'urgent'   ? '#dc2626' :
                                        slipTicket.priority === 'pregnant' ? '#db2777' :
                                        slipTicket.priority === 'pwd'      ? '#2563eb' :
                                        slipTicket.priority === 'senior'   ? '#d97706' : '#64748b'
                        }">
                        {{ (slipTicket.priority ?? 'regular').toUpperCase() }}
                    </span>
                    <p class="text-sm font-bold text-slate-800 pt-1">{{ slipTicket.patient_name }}</p>
                    <p class="text-xs text-slate-400 font-mono">{{ slipTicket.patient_code }}</p>
                    <div class="flex flex-wrap gap-1 justify-center pt-1">
                        <span v-for="svc in slipTicket.services" :key="svc"
                            class="text-xs px-2 py-0.5 rounded bg-slate-200 text-slate-600 font-semibold">
                            {{ svc }}
                        </span>
                    </div>
                    <div v-if="slipTicket.rooms?.length"
                        class="flex items-center justify-center gap-2 pt-1 flex-wrap">
                        <template v-for="(r, i) in slipTicket.rooms" :key="r.room">
                            <span v-if="i > 0" class="text-slate-300 text-xs">→</span>
                            <div class="text-center">
                                <div class="text-xs text-slate-500">{{ r.room.replace('_',' ') }}</div>
                                <div class="text-sm font-black text-slate-700 font-mono">{{ r.queue_number }}</div>
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-slate-400 pt-1">{{ slipTicket.issued_at }}</p>
                </div>
            </div>

            <div class="px-5 pb-5 flex gap-3">
                <button @click="showSlipModal = false"
                    class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-600 hover:bg-slate-50 font-semibold transition-colors">
                    Close
                </button>
                <button @click="printQueueSlip(slipTicket)"
                    class="flex-1 py-2.5 text-white rounded-xl text-sm font-bold transition-colors hover:opacity-90 flex items-center justify-center gap-2"
                    style="background:#1B4F9B">
                    <Printer class="w-4 h-4"/>
                    Print Slip
                </button>
            </div>
        </div>
    </div>
</Teleport>
</template>
