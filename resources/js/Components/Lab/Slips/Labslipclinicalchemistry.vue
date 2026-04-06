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

        <LabSlipHeader title="CLINICAL CHEMISTRY" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- Results table — 4 columns: EXAMINATION | RESULT | RESULT | REFERENCE RANGE -->
        <!-- The double RESULT column matches the physical form (one col for written, one for printed) -->
        <table style="width:100%;border-collapse:collapse;font-size:9.5px;margin-top:10px;margin-bottom:8px;">
            <thead>
                <tr>
                    <th style="text-align:left;padding:3px 6px;border-bottom:1.5px solid #111;font-size:9px;width:30%;">EXAMINATION</th>
                    <th style="text-align:left;padding:3px 6px;border-bottom:1.5px solid #111;font-size:9px;width:20%;">RESULT</th>
                    <th style="text-align:left;padding:3px 6px;border-bottom:1.5px solid #111;font-size:9px;width:20%;">RESULT</th>
                    <th style="text-align:left;padding:3px 6px;border-bottom:1.5px solid #111;font-size:9px;width:30%;">REFERENCE RANGE</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in [
                    { code:'FT3', name:'FT3', range:'3.1 - 6.8 pmol/L' },
                    { code:'FT4', name:'FT4', range:'12 - 22 pmol/L'   },
                    { code:'TSH', name:'TSH', range:'0.3 - 4.2 mIU/L'  },
                ]" :key="row.code">
                    <td style="padding:8px 6px;border-bottom:1px solid #e5e5e5;">{{ row.name }}</td>
                    <td style="padding:8px 6px;border-bottom:1px solid #e5e5e5;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    <td style="padding:8px 6px;border-bottom:1px solid #e5e5e5;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    <td style="padding:8px 6px;border-bottom:1px solid #e5e5e5;font-size:8.5px;color:#444;">{{ row.range }}</td>
                </tr>
            </tbody>
        </table>

        <div v-if="labRequest?.remarks" style="font-size:9px;color:#555;margin-bottom:4px;">
            Note: {{ labRequest.remarks }}
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
