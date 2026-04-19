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

        <LabSlipHeader title="URINALYSIS" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- RESULTS — 2 columns, flex:1 to fill space -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 20px;margin-bottom:6px;flex:1;">

            <!-- LEFT: Qualitative Examination -->
            <div>
                <div style="font-weight:700;font-size:11px;text-align:center;margin-bottom:5px;border-bottom:1.5px solid #aaa;padding-bottom:3px;">
                    QUALITATIVE EXAMINATION
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                    <tr v-for="row in [
                        {code:'UA_COLOR', name:'Color'},
                        {code:'UA_TRANS', name:'Transparency'},
                        {code:'UA_PH',    name:'pH'},
                        {code:'UA_SG',    name:'Specific Gravity'},
                        {code:'UA_SUGAR', name:'Sugar'},
                        {code:'UA_PRO',   name:'Protein'},
                        {code:'UA_NITRI', name:'Nitrite'},
                        {code:'UA_UROBI', name:'Urobilinogen'},
                        {code:'UA_BLOOD', name:'Blood'},
                        {code:'UA_KET',   name:'Ketones'},
                        {code:'UA_BILI',  name:'Bilirubin'},
                    ]" :key="row.code">
                        <td style="padding:3.5px 4px;width:55%;border-bottom:1px solid #f0f0f0;">{{ row.name }}</td>
                        <td style="padding:3.5px 4px;border-bottom:1px solid #f0f0f0;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>
                </table>
            </div>

            <!-- RIGHT: Microscopic Examination -->
            <div>
                <div style="font-weight:700;font-size:11px;text-align:center;margin-bottom:5px;border-bottom:1.5px solid #aaa;padding-bottom:3px;">
                    MICROSCOPIC EXAMINATION
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                    <tr v-for="row in [
                        {code:'UA_RBC', name:'Red Blood Cells'},
                        {code:'UA_PUS', name:'Pus Cells'},
                        {code:'UA_EPI', name:'Epithelial Cells'},
                        {code:'UA_MUC', name:'Mucus Threads'},
                        {code:'UA_AMO', name:'Amorphous Subs.'},
                        {code:'UA_BAC', name:'Bacteria'},
                        {code:'UA_CRY', name:'Crystals'},
                        {code:'UA_CAS', name:'Casts'},
                        {code:'UA_OTH', name:'Others'},
                    ]" :key="row.code">
                        <td style="padding:3.5px 4px;width:55%;border-bottom:1px solid #f0f0f0;">{{ row.name }}</td>
                        <td style="padding:3.5px 4px;border-bottom:1px solid #f0f0f0;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top:6px;font-size:10.5px;color:#555;">NOTE:</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding:2px 4px;font-size:10.5px;">{{ labRequest?.remarks ?? '' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
