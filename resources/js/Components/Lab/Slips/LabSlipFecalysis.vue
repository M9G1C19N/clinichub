<script setup>
import LabSlipHeader from './LabSlipHeader.vue'
import LabSlipFooter from './LabSlipFooter.vue'

const props = defineProps({
    patient:    Object,
    visit:      Object,
    labRequest: Object,
    results:    Object,
})

const r = (code) => props.results?.[code]?.value ?? ''
const isAb = (code) => props.results?.[code]?.is_abnormal ?? false
const rs = (code) => isAb(code) ? 'font-weight:900;color:#dc2626;' : 'font-weight:600;'
</script>

<template>
    <div style="font-family:Arial,sans-serif;font-size:10px;color:#111;background:white;width:210mm;padding:5mm 7mm;box-sizing:border-box;">

        <LabSlipHeader title="FECALYSIS" :patient="patient" :visit="visit"/>

        <!-- RESULTS — 2 columns: Macroscopic + Microscopic -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 20px;margin-bottom:10px;margin-top:8px;">

            <!-- LEFT: Macroscopic -->
            <div>
                <div style="font-size:9px;color:#555;margin-bottom:8px;">Macroscopic Examination</div>
                <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                    <tr v-for="row in [
                        {code:'ST_COL', name:'Color'},
                        {code:'ST_CON', name:'Consistency'},
                        {code:'ST_FOB', name:'FOBT'},
                    ]" :key="row.code">
                        <td style="padding:5px 3px;width:45%;font-weight:600;">{{ row.name }}</td>
                        <td style="padding:5px 3px;border-bottom:1px solid #ccc;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>
                </table>
            </div>

            <!-- RIGHT: Microscopic -->
            <div>
                <div style="font-size:9px;color:#555;margin-bottom:8px;">Microscopic Examination</div>
                <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                    <tr v-for="row in [
                        {code:'ST_RBC', name:'RBC'},
                        {code:'ST_PUS', name:'Pus Cells'},
                        {code:'ST_FAT', name:'Fat Globules'},
                        {code:'ST_OVA', name:'Ova/Parasite'},
                    ]" :key="row.code">
                        <td style="padding:5px 3px;width:45%;font-weight:600;">{{ row.name }}</td>
                        <td style="padding:5px 3px;border-bottom:1px solid #ccc;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
