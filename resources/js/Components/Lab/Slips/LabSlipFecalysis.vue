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
const rs   = (code) => isAb(code) ? 'font-weight:900;color:#dc2626;' : 'font-weight:600;'
</script>

<template>
    <div style="font-family:Arial,sans-serif;font-size:12px;color:#111;background:white;
                width:210mm;min-height:140mm;max-height:148mm;padding:6mm 9mm;
                box-sizing:border-box;display:flex;flex-direction:column;">

        <LabSlipHeader title="FECALYSIS" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- RESULTS — 2 columns, flex:1 to fill space -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 24px;flex:1;margin-bottom:6px;align-content:start;">

            <!-- LEFT: Macroscopic -->
            <div>
                <div style="font-size:11px;color:#555;font-weight:700;margin-bottom:10px;border-bottom:1px solid #ccc;padding-bottom:4px;">
                    Macroscopic Examination
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                    <tr v-for="row in [
                        {code:'ST_COL', name:'Color'},
                        {code:'ST_CON', name:'Consistency'},
                        {code:'ST_FOB', name:'FOBT'},
                    ]" :key="row.code">
                        <td style="padding:8px 4px;width:45%;font-weight:600;border-bottom:1px solid #e0e0e0;">{{ row.name }}</td>
                        <td style="padding:8px 4px;border-bottom:1px solid #ccc;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>
                </table>
            </div>

            <!-- RIGHT: Microscopic -->
            <div>
                <div style="font-size:11px;color:#555;font-weight:700;margin-bottom:10px;border-bottom:1px solid #ccc;padding-bottom:4px;">
                    Microscopic Examination
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                    <tr v-for="row in [
                        {code:'ST_RBC', name:'RBC'},
                        {code:'ST_PUS', name:'Pus Cells'},
                        {code:'ST_FAT', name:'Fat Globules'},
                        {code:'ST_OVA', name:'Ova/Parasite'},
                    ]" :key="row.code">
                        <td style="padding:8px 4px;width:45%;font-weight:600;border-bottom:1px solid #e0e0e0;">{{ row.name }}</td>
                        <td style="padding:8px 4px;border-bottom:1px solid #ccc;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
