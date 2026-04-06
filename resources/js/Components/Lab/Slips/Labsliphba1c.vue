<script setup>
import LabSlipHeader from './LabSlipHeader.vue'
import LabSlipFooter from './LabSlipFooter.vue'

const props = defineProps({
    patient:    Object,
    visit:      Object,
    labRequest: Object,
    results:    Object,
})

const r    = (code) => props.results?.[code]?.value ?? ''
const isAb = (code) => props.results?.[code]?.is_abnormal ?? false
const rs   = (code) => isAb(code) ? 'font-weight:900;color:#dc2626;' : ''
</script>

<template>
    <div style="font-family:Arial,sans-serif;font-size:10px;color:#111;background:white;width:210mm;padding:5mm 7mm;box-sizing:border-box;">

        <!-- Title is "IMMUNOLOGY SEROLOGY" (no "AND") — matches Image 4 exactly -->
        <LabSlipHeader title="IMMUNOLOGY SEROLOGY" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- Single row table: EXAMINATION | RESULT | REFERENCE RANGE -->
        <table style="width:100%;border-collapse:collapse;font-size:9.5px;margin-top:10px;margin-bottom:8px;">
            <thead>
                <tr>
                    <th style="text-align:left;padding:3px 6px;border-bottom:1.5px solid #111;font-size:9px;width:35%;">EXAMINATION</th>
                    <th style="text-align:left;padding:3px 6px;border-bottom:1.5px solid #111;font-size:9px;width:30%;">RESULT</th>
                    <th style="text-align:left;padding:3px 6px;border-bottom:1.5px solid #111;font-size:9px;width:35%;">REFERENCE RANGE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:8px 6px;border-bottom:1px solid #e5e5e5;">HBA1c</td>
                    <td style="padding:8px 6px;border-bottom:1px solid #e5e5e5;" :style="rs('HBA1C')">{{ r('HBA1C') }}</td>
                    <td style="padding:8px 6px;border-bottom:1px solid #e5e5e5;font-size:8.5px;color:#444;">&lt; 6.5%</td>
                </tr>
            </tbody>
        </table>

        <div v-if="labRequest?.remarks" style="font-size:9px;color:#555;margin-bottom:4px;">
            Note: {{ labRequest.remarks }}
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
