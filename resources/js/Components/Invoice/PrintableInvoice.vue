<script setup>
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
defineProps({
    invoice: Object,
    patient: Object,
    visit:   Object,
    items:   Array,
    payments:Array,
})

const visitTypeLabel = {
    opd:            'OPD Consultation',
    pre_employment: 'Pre-Employment Medical',
    follow_up:      'Follow-up Consultation',
    lab_only:       'Laboratory Only',
}

const methodLabel = {
    cash:       'Cash',
    gcash:      'GCash',
    maya:       'Maya',
    card:       'Credit/Debit Card',
    philhealth: 'PhilHealth',
    other:      'Other',
}
</script>

<template>
    <div id="print-area"
        class="bg-white"
        style="font-family: 'Arial', sans-serif; font-size: 12px; color: #111; max-width: 800px; margin: 0 auto; padding: 32px;">

        <!-- ── HEADER ─────────────────────────────── -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; padding-bottom:16px; border-bottom:2px solid #0F2044;">

            <!-- Clinic Info -->
            <div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                    <div style="width:40px; height:40px; background:#1B4F9B; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                        <img :src="CLINIC_LOGO" style="width:50px; height:50px; object-fit:contain;"/>
                    </div>
                    <div>
                        <div style="font-weight:900; font-size:16px; color:#0F2044;">ST. PETER DIAGNOSTICS</div>
                        <div style="font-weight:700; font-size:12px; color:#1B4F9B;">& LABORATORY · Medical and Dental Clinic</div>
                    </div>
                </div>
                <div style="font-size:11px; color:#555; margin-top:4px;">
                    <div>Brgy. Ladgaron, Claver, Surigao del Norte</div>
                    <div>Contact No.: 09204043408</div>
                    <div>sainpeterdiagnosticsandlaboratory.com</div>
                </div>
            </div>

            <!-- Invoice Info -->
            <div style="text-align:right;">
                <div style="font-size:22px; font-weight:900; color:#0F2044; letter-spacing:1px;">INVOICE</div>
                <div style="margin-top:8px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px;">
                    <table style="border-collapse:collapse;">
                        <tr>
                            <td style="color:#666; padding:2px 12px 2px 0; font-size:11px;">Invoice No.</td>
                            <td style="font-weight:700; color:#0F2044; font-size:12px;">{{ invoice.invoice_number }}</td>
                        </tr>
                        <tr>
                            <td style="color:#666; padding:2px 12px 2px 0; font-size:11px;">Date</td>
                            <td style="font-weight:600; color:#333; font-size:11px;">{{ invoice.created_at }}</td>
                        </tr>
                        <tr>
                            <td style="color:#666; padding:2px 12px 2px 0; font-size:11px;">Status</td>
                            <td>
                                <span :style="{
                                    fontWeight: '700',
                                    fontSize: '11px',
                                    padding: '2px 8px',
                                    borderRadius: '4px',
                                    background: invoice.status === 'paid' ? '#d1fae5' :
                                                invoice.status === 'partial' ? '#fef3c7' : '#fee2e2',
                                    color: invoice.status === 'paid' ? '#065f46' :
                                           invoice.status === 'partial' ? '#92400e' : '#991b1b',
                                }">
                                    {{ invoice.status.toUpperCase() }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── BILL TO ──────────────────────────────── -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">

            <div style="background:#f8fafc; border-radius:8px; padding:14px;">
                <div style="font-size:10px; font-weight:700; color:#1B4F9B; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">
                    Patient Information
                </div>
                <div style="font-weight:700; font-size:14px; color:#0F2044;">{{ patient.full_name }}</div>
                <div style="color:#555; margin-top:3px; font-size:11px;">
                    <span>Patient Code: </span>
                    <span style="font-weight:600; font-family:monospace;">{{ patient.patient_code }}</span>
                </div>
                <div style="color:#555; font-size:11px;">{{ patient.age_sex }}</div>
            </div>

            <div style="background:#f8fafc; border-radius:8px; padding:14px;">
                <div style="font-size:10px; font-weight:700; color:#1B4F9B; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">
                    Visit Information
                </div>
                <div style="font-weight:600; color:#333; font-size:12px;">{{ visitTypeLabel[visit.visit_type] }}</div>
                <div v-if="visit.employer_company" style="color:#555; font-size:11px; margin-top:2px;">
                    Company: <strong>{{ visit.employer_company }}</strong>
                </div>
                <div style="color:#555; font-size:11px; margin-top:2px;">Date: {{ visit.visit_date }}</div>
                <div style="color:#555; font-size:11px; margin-top:2px;">
                    Issued by: {{ invoice.created_by }}
                </div>
            </div>

        </div>

        <!-- ── SERVICES TABLE ──────────────────────── -->
        <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
            <thead>
                <tr style="background:#0F2044; color:white;">
                    <th style="text-align:left; padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-radius:6px 0 0 6px;">Service</th>
                    <th style="text-align:center; padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Code</th>
                    <th style="text-align:right; padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Unit Price</th>
                    <th style="text-align:center; padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">Qty</th>
                    <th style="text-align:right; padding:10px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; border-radius:0 6px 6px 0;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(item, i) in items" :key="item.id"
                    :style="{ background: i % 2 === 0 ? '#f8fafc' : 'white' }">
                    <td style="padding:10px 14px; font-size:12px; color:#333; border-bottom:1px solid #e2e8f0;">
                        {{ item.service_name }}
                    </td>
                    <td style="padding:10px 14px; text-align:center; font-family:monospace; font-weight:700; font-size:11px; color:#1B4F9B; border-bottom:1px solid #e2e8f0;">
                        {{ item.service_code }}
                    </td>
                    <td style="padding:10px 14px; text-align:right; font-size:12px; color:#333; border-bottom:1px solid #e2e8f0;">
                        ₱ {{ Number(item.unit_price).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                    </td>
                    <td style="padding:10px 14px; text-align:center; font-size:12px; color:#333; border-bottom:1px solid #e2e8f0;">
                        {{ item.quantity }}
                    </td>
                    <td style="padding:10px 14px; text-align:right; font-weight:700; font-size:12px; color:#333; border-bottom:1px solid #e2e8f0;">
                        ₱ {{ Number(item.subtotal).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ── TOTALS ──────────────────────────────── -->
        <div style="display:flex; justify-content:flex-end; margin-bottom:24px;">
            <div style="width:280px;">
                <div style="display:flex; justify-content:space-between; padding:6px 0; font-size:12px; color:#555; border-bottom:1px solid #e2e8f0;">
                    <span>Subtotal</span>
                    <span>₱ {{ Number(invoice.total_amount).toLocaleString('en-PH', {minimumFractionDigits:2}) }}</span>
                </div>
                <div v-if="invoice.discount_amount > 0"
                    style="display:flex; justify-content:space-between; padding:6px 0; font-size:12px; color:#059669; border-bottom:1px solid #e2e8f0;">
                    <span>Discount</span>
                    <span>- ₱ {{ Number(invoice.discount_amount).toLocaleString('en-PH', {minimumFractionDigits:2}) }}</span>
                </div>
                <div v-if="invoice.paid_amount > 0"
                    style="display:flex; justify-content:space-between; padding:6px 0; font-size:12px; color:#059669; border-bottom:1px solid #e2e8f0;">
                    <span>Amount Paid</span>
                    <span>- ₱ {{ Number(invoice.paid_amount).toLocaleString('en-PH', {minimumFractionDigits:2}) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:10px 0; font-size:15px; font-weight:900; color:#0F2044; border-top:2px solid #0F2044; margin-top:4px;">
                    <span>BALANCE DUE</span>
                    <span>₱ {{ Number(invoice.balance).toLocaleString('en-PH', {minimumFractionDigits:2}) }}</span>
                </div>
            </div>
        </div>

        <!-- ── PAYMENT HISTORY ─────────────────────── -->
        <div v-if="payments.length > 0" style="margin-bottom:24px;">
            <div style="font-size:10px; font-weight:700; color:#1B4F9B; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px;">
                Payment Record
            </div>
            <table style="width:100%; border-collapse:collapse; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="text-align:left; padding:8px 12px; font-size:11px; color:#555; font-weight:700;">Date</th>
                        <th style="text-align:left; padding:8px 12px; font-size:11px; color:#555; font-weight:700;">Method</th>
                        <th style="text-align:left; padding:8px 12px; font-size:11px; color:#555; font-weight:700;">Reference</th>
                        <th style="text-align:left; padding:8px 12px; font-size:11px; color:#555; font-weight:700;">Received By</th>
                        <th style="text-align:right; padding:8px 12px; font-size:11px; color:#555; font-weight:700;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="pmt in payments" :key="pmt.id">
                        <td style="padding:8px 12px; font-size:11px; color:#333; border-top:1px solid #e2e8f0;">{{ pmt.created_at }}</td>
                        <td style="padding:8px 12px; font-size:11px; color:#333; border-top:1px solid #e2e8f0; font-weight:600;">{{ methodLabel[pmt.method] }}</td>
                        <td style="padding:8px 12px; font-size:11px; color:#555; border-top:1px solid #e2e8f0; font-family:monospace;">{{ pmt.reference_number || '—' }}</td>
                        <td style="padding:8px 12px; font-size:11px; color:#555; border-top:1px solid #e2e8f0;">{{ pmt.received_by }}</td>
                        <td style="padding:8px 12px; font-size:11px; font-weight:700; color:#059669; text-align:right; border-top:1px solid #e2e8f0;">
                            ₱ {{ Number(pmt.amount).toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ── FOOTER ──────────────────────────────── -->
        <div style="border-top:1px solid #e2e8f0; padding-top:16px; margin-top:8px;">
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:24px; margin-bottom:16px;">
                <div style="text-align:center;">
                    <div style="border-top:1px solid #333; padding-top:6px; margin-top:40px; font-size:11px; color:#555;">
                        Cashier / Receptionist
                    </div>
                    <div style="font-size:10px; color:#999; margin-top:2px;">{{ invoice.created_by }}</div>
                </div>
                <div style="text-align:center;">
                    <div style="border-top:1px solid #333; padding-top:6px; margin-top:40px; font-size:11px; color:#555;">
                        Patient Signature
                    </div>
                </div>
                <div style="text-align:center;">
                    <div style="border-top:1px solid #333; padding-top:6px; margin-top:40px; font-size:11px; color:#555;">
                        Authorized Signature
                    </div>
                </div>
            </div>

            <div style="text-align:center; font-size:10px; color:#999; margin-top:8px;">
                This is a computer-generated invoice. · St. Peter Diagnostics & Laboratory · ClinicHub v4.0
            </div>
        </div>

    </div>
</template>
