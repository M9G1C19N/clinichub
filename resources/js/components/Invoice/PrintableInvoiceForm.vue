<script setup>
import { computed } from 'vue'
import { VISIT_TYPE_LABEL as visitTypeLabel } from '@/config/visitTypes.js'

const props = defineProps({
    invoice:  Object,
    patient:  Object,
    visit:    Object,
    items:    Array,
    payments: Array,
})

const fmt = (val) =>
    Number(val ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const netTotal = computed(() =>
    Number(props.invoice?.total_amount ?? 0) - Number(props.invoice?.discount_amount ?? 0)
)

// Max 8 item rows to match the pre-printed form
const MAX_ROWS = 8
const fillerCount = computed(() =>
    Math.max(0, MAX_ROWS - (props.items?.length ?? 0) - (props.invoice?.discount_amount > 0 ? 1 : 0))
)
</script>

<template>
    <!--
        DATA-ONLY print for pre-printed 20cm × 13cm billing form.
        No borders, no labels, no headers — only the variable data.
        Column widths and row heights must match the physical form exactly.
        Adjust the pixel values below if data drifts on the actual printer.
    -->
    <div style="
        width: 200mm;
        height: 130mm;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
        font-size: 9pt;
        color: #000;
        background: transparent;
        position: relative;
        overflow: hidden;
        padding: 0;
        margin: 0;
    ">

        <!-- ── ZONE 1: Header (21mm) — only print DATE top-right ── -->
        <div style="
            height: 30mm;
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            padding-bottom: 1.5mm;
            padding-right: 4mm;
        ">
            <span style="font-size:8.5pt; font-weight:700;">{{ invoice.created_at }}</span>
        </div>

        <!-- ── ZONE 2: Bill To row (8mm) — company top, patient name below; nothing on right ── -->
        <div style="
            height: 10mm;
            display: flex;
            align-items: center;

        ">
            <!-- Bill To: company name (primary), patient name below it -->
            <div style="
                padding-left: 15mm;
                font-size: 9pt;
                font-weight: 700;
                text-transform: uppercase;
                white-space: nowrap;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                gap: 0;
                line-height: 1.25;
            ">
                <span v-if="visit.employer_company">{{ visit.employer_company }}</span>
                <!-- <span style="font-size:8pt; font-weight:400;">{{ patient.full_name }}</span> -->
            </div>
        </div>

        <!-- ── ZONE 3: Column headers row (7mm) — all pre-printed, leave blank ── -->
        <div style="height: 4mm;"></div>

        <!-- ── ZONE 4: Items table — 8 rows × 7mm each = 56mm ── -->
        <!--
            Column widths (content area 194mm after 3mm left+right padding):
            Left (Bill To area): 68mm  QTY: 15mm  UNIT: 17mm
            ARTICLES: 42mm  UNIT PRICE: 26mm  AMOUNT: 26mm
        -->
        <table style="
            width: 185mm;
            margin: 0 3mm;
            border-collapse: collapse;
            table-layout: fixed;
        ">
            <colgroup>
                <col style="width:60mm"/><!-- Bill To area (blank in item rows) -->
                <col style="width:15mm"/><!-- QTY -->
                <col style="width:17mm"/><!-- UNIT -->
                <col style="width:42mm"/><!-- ARTICLES -->
                <col style="width:29mm"/><!-- UNIT PRICE -->
                <col style="width:29mm"/><!-- AMOUNT -->
            </colgroup>
            <tbody>
                <!-- Actual item rows -->
                <tr v-for="item in items" :key="item.id" style="height:7mm;">
                    <td></td>
                    <td style="text-align:center; vertical-align:middle; font-size:9pt;">
                        {{ item.quantity }}
                    </td>
                    <td style="text-align:center; vertical-align:middle; font-size:8.5pt; color:#333;">
                        pc
                    </td>
                    <td style="vertical-align:middle; font-size:9pt; padding-left:1.5mm;">
                        {{ item.service_name }}
                    </td>
                    <td style="text-align:right; vertical-align:middle; font-size:9pt; padding-right:3mm;">
                        {{ fmt(item.unit_price) }}
                    </td>
                    <td style="text-align:right; vertical-align:middle; font-size:9pt; padding-right:2mm;">
                        {{ fmt(item.subtotal) }}
                    </td>
                </tr>

                <!-- Discount row -->
                <tr v-if="invoice.discount_amount > 0" style="height:7mm;">
                    <td colspan="4" style="text-align:right; vertical-align:middle; font-size:8.5pt; padding-right:2mm; color:#c00;">
                        Discount
                    </td>
                    <td style="text-align:right; vertical-align:middle; font-size:8.5pt; padding-right:3mm; color:#c00;">
                        - {{ fmt(invoice.discount_amount) }}
                    </td>
                    <td></td>
                </tr>

                <!-- Filler rows to fill remaining blank lines on form -->
                <tr v-for="n in fillerCount" :key="'f'+n" style="height:7mm;">
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            </tbody>
        </table>

        <!-- ── ZONE 5: Re row (6.5mm) — value only, label is pre-printed ── -->
        <div style="
            height: 2.5mm;
            display: flex;
            align-items: center;
            padding: 0 3mm;
        ">
            <!-- Offset ~8mm past pre-printed "Re:" label -->
            <span style="padding-left:11mm; font-size:9pt; position:relative; top:-15mm;">
                {{ patient.full_name }}
            </span>
        </div>

        <!-- ── ZONE 6: CN row (6.5mm) — value only, label is pre-printed ── -->
        <div style="
            height: 6.5mm;
            display: flex;
            align-items: center;
            padding: 0 3mm;
        ">
            <!-- Offset ~8mm past pre-printed "CN:" label -->
            <span style="padding-left:11mm; font-size:9pt; position:relative; top:-5mm;">
                {{ visit.case_number ?? '' }}
            </span>
        </div>

        <!-- ── ZONE 7: Signature / Total row (14mm) — only print total amount ── -->
        <!--
            The TOTAL AMOUNT DUE label + box are pre-printed on the right side.
            We only print the amount value inside that box.
            Tweak padding-right to land inside the box.
        -->
        <div style="
            height: 5mm;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10mm;
        ">
            <span style="font-size:12pt; font-weight:900; letter-spacing:0.3px;">
                ₱ {{ fmt(netTotal) }}
            </span>
        </div>

        <!-- ── ZONE 8: BIR footer (6.5mm) — all pre-printed, leave blank ── -->

    </div>
</template>
