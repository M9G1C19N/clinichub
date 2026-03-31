<script setup>
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import { VISIT_TYPE_LABEL as visitTypeLabel } from '@/config/visitTypes.js'

const props = defineProps({
    invoice: Object,
    patient: Object,
    visit:   Object,
    items:   Array,
    payments:Array,
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
</script>

<template>
    <!-- Half-sheet A5 landscape: 210mm wide × ~148mm tall -->
    <div style="
        font-family: Arial, sans-serif;
        font-size: 10px;
        color: #111;
        background: white;
        width: 210mm;
        min-height: 130mm;
        max-height: 148mm;
        padding: 6mm 8mm 5mm 8mm;
        box-sizing: border-box;
        position: relative;
    ">

        <!-- ── TOP HEADER ROW ─────────────────────── -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">

            <!-- Left: Logo + Clinic -->
            <div style="display:flex; align-items:center; gap:8px;">
                <img :src="CLINIC_LOGO" style="width:42px; height:42px; object-fit:contain; flex-shrink:0;"/>
                <div>
                    <div style="font-weight:900; font-size:12px; color:#111; line-height:1.2;">
                        SAINT PETER DIAGNOSTIC AND LABORATORY
                    </div>
                    <div style="font-size:9px; color:#333; font-weight:600;">Medical and Dental Clinic</div>
                    <div style="font-size:8.5px; color:#444;">{{ CLINIC_INFO.doctor ?? 'MIRA, ROLAND ENTENIA' }} - Proprietor</div>
                    <div style="font-size:8px; color:#555;">VAT Reg TIN: {{ CLINIC_INFO.tin ?? '920-048-935-000' }}</div>
                    <div style="font-size:8px; color:#555;">{{ CLINIC_INFO.addressFull }}</div>
                </div>
            </div>

            <!-- Right: BILLING INVOICE + Date + No -->
            <div style="text-align:right;">
                <div style="font-size:18px; font-weight:900; color:#111; letter-spacing:1px; line-height:1;">
                    BILLING INVOICE
                </div>
                <div style="font-size:9px; margin-top:4px; color:#333;">
                    Date: <strong>{{ invoice.created_at }}</strong>
                </div>
                <div style="font-size:9px; margin-top:2px; color:#333;">
                    No. <strong style="font-size:12px;">{{ invoice.invoice_number }}</strong>
                </div>
            </div>
        </div>

        <!-- ── BILL TO ROW ─────────────────────────── -->
        <div style="display:flex; gap:12px; margin-bottom:4px; border-top:1.5px solid #111; border-bottom:1px solid #aaa; padding:3px 0;">
            <div style="flex:1;">
                <span style="font-weight:700; font-size:9.5px;">BILL TO: </span>
                <span style="font-size:10px; font-weight:700; text-transform:uppercase;">
                    {{ patient.full_name }}
                </span>
                <span style="font-size:9px; color:#555; margin-left:8px;">
                    {{ patient.age_sex }}
                </span>
            </div>
            <div style="font-size:9px; color:#555;">
                Visit Type: <strong>{{ visitTypeLabel[visit.visit_type] ?? visit.visit_type }}</strong>
            </div>
            <div v-if="visit.employer_company" style="font-size:9px; color:#555;">
                Company: <strong>{{ visit.employer_company }}</strong>
            </div>
        </div>

        <!-- ── ITEMS TABLE ─────────────────────────── -->
        <table style="width:100%; border-collapse:collapse; margin-bottom:4px;">
            <thead>
                <tr style="background:#f0f0f0;">
                    <th style="border:1px solid #aaa; padding:3px 6px; text-align:center; font-size:9px; font-weight:700; width:8%;">QTY</th>
                    <th style="border:1px solid #aaa; padding:3px 6px; text-align:center; font-size:9px; font-weight:700; width:10%;">UNIT</th>
                    <th style="border:1px solid #aaa; padding:3px 6px; text-align:left;   font-size:9px; font-weight:700;">ARTICLES</th>
                    <th style="border:1px solid #aaa; padding:3px 6px; text-align:right;  font-size:9px; font-weight:700; width:16%;">UNIT PRICE</th>
                    <th style="border:1px solid #aaa; padding:3px 6px; text-align:right;  font-size:9px; font-weight:700; width:16%;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <!-- Service items -->
                <tr v-for="item in items" :key="item.id">
                    <td style="border:1px solid #ccc; padding:2.5px 6px; text-align:center; font-size:9.5px;">
                        {{ item.quantity }}
                    </td>
                    <td style="border:1px solid #ccc; padding:2.5px 6px; text-align:center; font-size:9px; color:#555;">
                        pc
                    </td>
                    <td style="border:1px solid #ccc; padding:2.5px 6px; font-size:9.5px;">
                        {{ item.service_name }}
                    </td>
                    <td style="border:1px solid #ccc; padding:2.5px 6px; text-align:right; font-size:9.5px;">
                        ₱ {{ formatAmount(item.unit_price) }}
                    </td>
                    <td style="border:1px solid #ccc; padding:2.5px 6px; text-align:right; font-size:9.5px;">
                        ₱ {{ formatAmount(item.subtotal) }}
                    </td>
                </tr>

                <!-- Discount row if applicable -->
                <tr v-if="invoice.discount_amount > 0">
                    <td colspan="4" style="border:1px solid #ccc; padding:2.5px 6px; text-align:right; font-size:9px; color:#c00;">
                        Discount
                    </td>
                    <td style="border:1px solid #ccc; padding:2.5px 6px; text-align:right; font-size:9px; color:#c00;">
                        - ₱ {{ formatAmount(invoice.discount_amount) }}
                    </td>
                </tr>

                <!-- Empty filler rows to match the physical form look (min 6 rows) -->
                <tr v-for="n in Math.max(0, 6 - items.length - (invoice.discount_amount > 0 ? 1 : 0))"
                    :key="'empty-' + n">
                    <td style="border:1px solid #ccc; padding:2.5px 6px; height:16px;"></td>
                    <td style="border:1px solid #ccc; padding:2.5px 6px;"></td>
                    <td style="border:1px solid #ccc; padding:2.5px 6px;"></td>
                    <td style="border:1px solid #ccc; padding:2.5px 6px;"></td>
                    <td style="border:1px solid #ccc; padding:2.5px 6px;"></td>
                </tr>
            </tbody>
        </table>

        <!-- ── BOTTOM ROW ──────────────────────────── -->
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:5px;">

            <!-- Left: Re / CN / Payment info -->
            <div style="flex:1; font-size:9px;">
                <div style="margin-bottom:2px;">
                    <span style="font-weight:700;">Re: </span>
                    <span style="border-bottom:1px solid #aaa; display:inline-block; min-width:200px; padding-bottom:1px;">
                        {{ visitTypeLabel[visit.visit_type] ?? '' }} — {{ patient.full_name }}
                    </span>
                </div>
               <div>
                    <span style="font-weight:700;">CN: </span>
                    <span style="border-bottom:1px solid #aaa; display:inline-block; min-width:200px; padding-bottom:1px;">
                        {{ visit.case_number ?? invoice.invoice_number }}
                    </span>
                </div>
                <div class="text-xs text-slate-500">
                    Case No.: <strong class="text-slate-800 font-mono">{{ visit.case_number }}</strong>
                </div>
                <!-- Payment method if paid -->
                <div v-if="payments && payments.length > 0" style="margin-top:3px; color:#555;">
                    Payment:
                    <span v-for="(p, i) in payments" :key="p.id">
                        {{ methodLabel[p.method] ?? p.method }}
                        <span v-if="p.reference_number"> (Ref: {{ p.reference_number }})</span>
                        <span v-if="i < payments.length - 1">, </span>
                    </span>
                </div>
            </div>

            <!-- Right: Totals -->
            <div style="min-width:180px;">
                <table style="width:100%; border-collapse:collapse; font-size:9.5px;">
                    <tr>
                        <td style="padding:2px 6px; text-align:right; color:#555;">Subtotal:</td>
                        <td style="padding:2px 6px; text-align:right; font-weight:600; border-bottom:1px solid #ccc; min-width:90px;">
                            ₱ {{ formatAmount(invoice.total_amount + (invoice.discount_amount ?? 0)) }}
                        </td>
                    </tr>
                    <tr v-if="invoice.discount_amount > 0">
                        <td style="padding:2px 6px; text-align:right; color:#c00;">Discount:</td>
                        <td style="padding:2px 6px; text-align:right; color:#c00; border-bottom:1px solid #ccc;">
                            - ₱ {{ formatAmount(invoice.discount_amount) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:2px 6px; text-align:right; font-weight:900; font-size:10px; border-top:2px solid #111;">
                            TOTAL AMOUNT DUE:
                        </td>
                        <td style="padding:2px 6px; text-align:right; font-weight:900; font-size:12px; border-top:2px solid #111; border-bottom:2px solid #111;">
                            ₱ {{ formatAmount(invoice.total_amount) }}
                        </td>
                    </tr>
                    <tr v-if="invoice.paid_amount > 0">
                        <td style="padding:2px 6px; text-align:right; color:#15803d;">Paid:</td>
                        <td style="padding:2px 6px; text-align:right; color:#15803d; font-weight:700;">
                            ₱ {{ formatAmount(invoice.paid_amount) }}
                        </td>
                    </tr>
                    <tr v-if="invoice.balance > 0">
                        <td style="padding:2px 6px; text-align:right; color:#dc2626;">Balance:</td>
                        <td style="padding:2px 6px; text-align:right; color:#dc2626; font-weight:700;">
                            ₱ {{ formatAmount(invoice.balance) }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ── SIGNATURE ROW ───────────────────────── -->
        <div style="display:flex; justify-content:space-between; align-items:flex-end; border-top:1px solid #aaa; padding-top:4px;">
            <div style="font-size:8.5px; color:#333;">
                <em>I hereby received the above items / services with their corresponding charges as stated above.</em>
            </div>
            <div style="font-size:8.5px; text-align:center; min-width:140px;">
                <div style="border-bottom:1px solid #333; margin-bottom:2px; min-height:18px;"></div>
                <div>Signature Over Printed Name</div>
            </div>
            <div style="font-size:8.5px; text-align:center; min-width:120px;">
                <div style="border-bottom:1px solid #333; margin-bottom:2px; min-height:18px;"></div>
                <div>Issued By: _______________</div>
            </div>
        </div>

        <!-- ── BIR ACCREDITATION FOOTER ────────────── -->
        <div style="border-top:1px solid #ccc; margin-top:3px; padding-top:2px; display:flex; justify-content:space-between; align-items:center;">
            <div style="font-size:7px; color:#555; max-width:70%;">
                500 Bkits (2x50) 0001-25000 &nbsp;
                BIR Authority to Print No.{{ CLINIC_INFO.birAtp ?? '2AU0002833352' }} &nbsp;
                Date Issued: 03-10-2021; Valid until: 03-09-2026<br/>
                Uptown Press Surigao, Inc. VAT Reg. TIN: 000-275-209-000 &nbsp;
                044438 Navarro St., Surigao City<br/>
                <strong>"THIS DOCUMENT IS NOT VALID FOR CLAIMING INPUT TAXES"</strong><br/>
                <em>THIS BILLING INVOICE SHALL BE VALID FOR FIVE (5) YEARS FROM THE DATE OF ATP</em>
            </div>
            <div style="font-size:8.5px; text-align:right; color:#555;">
                <div style="font-size:7.5px;">Printer's Accreditation No.: 10SMP2019000000001</div>
                <div style="font-size:11px; font-weight:900; color:#111;">
                    No. &nbsp; {{ invoice.invoice_number?.replace('INV-', '') }}
                </div>
            </div>
        </div>

    </div>
</template>
