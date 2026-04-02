<script setup>
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import { VISIT_TYPE_LABEL as visitTypeLabel } from '@/config/visitTypes.js'

const props = defineProps({
    invoice:  Object,
    patient:  Object,
    visit:    Object,
    items:    Array,
    payments: Array,
})

const methodLabel = {
    cash:       'Cash',
    gcash:      'GCash',
    maya:       'Maya',
    card:       'Credit/Debit Card',
    philhealth: 'PhilHealth',
    other:      'Other',
}

const formatAmount = (val) =>
    Number(val ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const invoiceStatusConfig = {
    unpaid:   { label: 'UNPAID',   color: '#dc2626' },
    partial:  { label: 'PARTIAL',  color: '#b45309' },
    paid:     { label: 'PAID',     color: '#15803d' },
    cancelled:{ label: 'CANCELLED',color: '#64748b' },
}
</script>

<template>
    <div style="
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #111;
        background: white;
        padding: 20px 24px;
        max-width: 210mm;
        margin: 0 auto;
    ">

        <!-- ── HEADER ─────────────────────────────── -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #0F2044; padding-bottom:10px; margin-bottom:12px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <img :src="CLINIC_LOGO" style="width:52px; height:52px; object-fit:contain;"/>
                <div>
                    <div style="font-weight:900; font-size:14px; color:#0F2044;">{{ CLINIC_INFO.name.toUpperCase() }}</div>
                    <div style="font-size:9.5px; color:#1B4F9B; font-weight:700; font-style:italic;">{{ CLINIC_INFO.subtitle }}</div>
                    <div style="font-size:9px; color:#444;">{{ CLINIC_INFO.addressFull }}</div>
                    <div style="font-size:9px; color:#444;">{{ CLINIC_INFO.contact }}</div>
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:20px; font-weight:900; color:#0F2044; letter-spacing:1px;">OFFICIAL RECEIPT</div>
                <div style="font-size:10px; margin-top:4px; color:#555;">
                    Invoice No.: <strong style="color:#0F2044; font-size:13px;">{{ invoice.invoice_number }}</strong>
                </div>
                <div style="font-size:10px; margin-top:2px; color:#555;">
                    Date: <strong>{{ invoice.created_at }}</strong>
                </div>
                <div style="margin-top:4px;">
                    <span style="font-size:11px; font-weight:900; padding:2px 10px; border-radius:4px;"
                        :style="{
                            background: invoiceStatusConfig[invoice.status]?.color + '20',
                            color: invoiceStatusConfig[invoice.status]?.color,
                            border: '1.5px solid ' + (invoiceStatusConfig[invoice.status]?.color)
                        }">
                        {{ invoiceStatusConfig[invoice.status]?.label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- ── PATIENT INFO ───────────────────────── -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:4px; margin-bottom:12px; font-size:10px; background:#f8fafc; padding:8px 10px; border-radius:6px; border:1px solid #e2e8f0;">
            <div>
                <span style="color:#555;">Patient Name:</span>
                <strong style="margin-left:6px; font-size:12px;">{{ patient.full_name }}</strong>
            </div>
            <div>
                <span style="color:#555;">Patient Code:</span>
                <strong style="margin-left:6px; font-family:monospace;">{{ patient.patient_code }}</strong>
            </div>
            <div>
                <span style="color:#555;">Age / Sex:</span>
                <strong style="margin-left:6px;">{{ patient.age_sex }}</strong>
            </div>
            <div>
                <span style="color:#555;">Visit Type:</span>
                <strong style="margin-left:6px;">{{ visitTypeLabel[visit.visit_type] ?? visit.visit_type }}</strong>
            </div>
            <div v-if="visit.employer_company">
                <span style="color:#555;">Company:</span>
                <strong style="margin-left:6px; color:#7c3aed;">{{ visit.employer_company }}</strong>
            </div>
            <div>
                <span style="color:#555;">Visit Date:</span>
                <strong style="margin-left:6px;">{{ visit.visit_date }}</strong>
            </div>
        </div>

        <!-- ── SERVICES TABLE ─────────────────────── -->
        <table style="width:100%; border-collapse:collapse; margin-bottom:12px;">
            <thead>
                <tr style="background:#0F2044; color:white;">
                    <th style="padding:6px 10px; text-align:left; font-size:10px; font-weight:700; width:50%;">Service / Article</th>
                    <th style="padding:6px 10px; text-align:center; font-size:10px; font-weight:700; width:10%;">Qty</th>
                    <th style="padding:6px 10px; text-align:right; font-size:10px; font-weight:700; width:20%;">Unit Price</th>
                    <th style="padding:6px 10px; text-align:right; font-size:10px; font-weight:700; width:20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, idx) in items" :key="item.id"
                    :style="{ background: idx % 2 === 0 ? '#fff' : '#f8fafc' }">
                    <td style="padding:5px 10px; font-size:10.5px; border-bottom:1px solid #e2e8f0;">
                        {{ item.service_name }}
                    </td>
                    <td style="padding:5px 10px; text-align:center; font-size:10.5px; border-bottom:1px solid #e2e8f0;">
                        {{ item.quantity }}
                    </td>
                    <td style="padding:5px 10px; text-align:right; font-size:10.5px; border-bottom:1px solid #e2e8f0;">
                        ₱ {{ formatAmount(item.unit_price) }}
                    </td>
                    <td style="padding:5px 10px; text-align:right; font-size:10.5px; border-bottom:1px solid #e2e8f0; font-weight:600;">
                        ₱ {{ formatAmount(item.subtotal) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ── TOTALS + PAYMENT ───────────────────── -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">

            <!-- Payment info -->
            <div style="font-size:10px; max-width:55%;">
                <div v-if="payments && payments.length > 0">
                    <div style="font-weight:700; margin-bottom:4px; color:#0F2044;">Payment Record:</div>
                    <div v-for="p in payments" :key="p.id"
                        style="display:flex; gap:8px; margin-bottom:2px; padding:3px 8px; background:#f0fdf4; border-radius:4px; border:1px solid #bbf7d0;">
                        <span style="font-weight:600; color:#15803d;">₱ {{ formatAmount(p.amount) }}</span>
                        <span style="color:#555;">via {{ methodLabel[p.method] ?? p.method }}</span>
                        <span v-if="p.reference_number" style="color:#888;">· Ref: {{ p.reference_number }}</span>
                        <span style="color:#888;">· {{ p.created_at }}</span>
                    </div>
                </div>
                <div v-else style="color:#b45309; font-style:italic;">No payment recorded yet.</div>
            </div>

            <!-- Amounts -->
            <div style="min-width:200px; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
                <div style="display:flex; justify-content:space-between; padding:5px 12px; border-bottom:1px solid #e2e8f0; font-size:10px;">
                    <span style="color:#555;">Subtotal</span>
                    <span>₱{{ formatAmount(Number(invoice.total_amount) + Number(invoice.discount_amount ?? 0)) }}</span>
                </div>
                <div v-if="invoice.discount_amount > 0"
                    style="display:flex; justify-content:space-between; padding:5px 12px; border-bottom:1px solid #e2e8f0; font-size:10px;">
                    <span style="color:#dc2626;">Discount</span>
                    <span style="color:#dc2626;">- ₱ {{ formatAmount(invoice.discount_amount) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:6px 12px; border-bottom:1px solid #e2e8f0; background:#0F2044; color:white;">
                    <span style="font-weight:700; font-size:10.5px;">TOTAL</span>
                    <span style="font-weight:900; font-size:13px;">₱ {{ formatAmount(invoice.total_amount) }}</span>
                </div>
                <div v-if="invoice.paid_amount > 0"
                    style="display:flex; justify-content:space-between; padding:5px 12px; border-bottom:1px solid #e2e8f0; font-size:10px; background:#f0fdf4;">
                    <span style="color:#15803d; font-weight:600;">Paid</span>
                    <span style="color:#15803d; font-weight:700;">₱ {{ formatAmount(invoice.paid_amount) }}</span>
                </div>
                <div v-if="invoice.balance > 0"
                    style="display:flex; justify-content:space-between; padding:5px 12px; font-size:10px; background:#fef2f2;">
                    <span style="color:#dc2626; font-weight:600;">Balance Due</span>
                    <span style="color:#dc2626; font-weight:700;">₱ {{ formatAmount(invoice.balance) }}</span>
                </div>
            </div>
        </div>

        <!-- ── SIGNATURE ──────────────────────────── -->
        <div style="border-top:1.5px solid #0F2044; padding-top:10px; display:flex; justify-content:space-between; align-items:flex-end;">
            <div style="font-size:9.5px; color:#555; max-width:50%;">
                <em>I hereby acknowledge receipt of the above services and charges.</em>
            </div>
            <div style="display:flex; gap:40px;">
                <div style="text-align:center; font-size:9.5px;">
                    <div style="border-top:1px solid #333; padding-top:3px; min-width:130px;">
                        Signature Over Printed Name
                    </div>
                </div>
                <div style="text-align:center; font-size:9.5px;">
                    <div style="border-top:1px solid #333; padding-top:3px; min-width:130px;">
                        Received By / Cashier
                    </div>
                </div>
            </div>
        </div>

        <!-- ── FOOTER ─────────────────────────────── -->
        <div style="border-top:1px solid #e2e8f0; margin-top:8px; padding-top:5px; display:flex; justify-content:space-between; font-size:8px; color:#94a3b8;">
            <div>
                <img :src="CLINIC_LOGO" style="width:12px; height:12px; object-fit:contain; opacity:0.5; display:inline; vertical-align:middle;"/>
                <strong style="color:#0F2044; margin-left:4px;">{{ CLINIC_INFO.name }}</strong>
                · {{ CLINIC_INFO.addressFull }}
            </div>
            <div>{{ patient.full_name }} · {{ invoice.invoice_number }} · System Receipt</div>
        </div>

    </div>
</template>
