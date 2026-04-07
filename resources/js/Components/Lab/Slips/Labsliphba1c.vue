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
    <div style="font-family:Arial,sans-serif;font-size:12px;color:#111;background:white;
                width:210mm;min-height:140mm;max-height:148mm;padding:6mm 9mm;
                box-sizing:border-box;display:flex;flex-direction:column;">

        <LabSlipHeader title="GLYCATED HEMOGLOBIN (HbA1c)" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- Single row table, flex:1 to fill space -->
        <table style="width:100%;border-collapse:collapse;font-size:11px;margin-top:14px;margin-bottom:8px;align-self:start;">
            <thead>
                <tr>
                    <th style="text-align:left;padding:5px 7px;border-bottom:1.5px solid #111;font-size:10.5px;width:35%;">EXAMINATION</th>
                    <th style="text-align:left;padding:5px 7px;border-bottom:1.5px solid #111;font-size:10.5px;width:30%;">RESULT</th>
                    <th style="text-align:left;padding:5px 7px;border-bottom:1.5px solid #111;font-size:10.5px;width:35%;">REFERENCE RANGE</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding:18px 7px;border-bottom:1px solid #e5e5e5;font-size:12px;">HBA1c</td>
                    <td style="padding:18px 7px;border-bottom:1px solid #e5e5e5;font-size:14px;" :style="rs('HBA1C')">{{ r('HBA1C') }}</td>
                    <td style="padding:18px 7px;border-bottom:1px solid #e5e5e5;font-size:10.5px;color:#444;">&lt; 6.5%</td>
                </tr>
            </tbody>
        </table>

        <div v-if="labRequest?.remarks" style="font-size:11px;color:#555;margin-bottom:5px;">
            Note: {{ labRequest.remarks }}
        </div>

        <!-- Spacer to push footer down -->
        <div style="flex:1;"></div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
