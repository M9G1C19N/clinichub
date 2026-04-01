<script setup>
import LabSlipHeader from './LabSlipHeader.vue'
import LabSlipFooter from './LabSlipFooter.vue'

const props = defineProps({
    patient:    Object,
    visit:      Object,
    labRequest: Object,
    results:    Object, // keyed by test_code
})

const r = (code) => props.results?.[code]?.value ?? ''
const isAb = (code) => props.results?.[code]?.is_abnormal ?? false
const rs = (code) => isAb(code) ? 'font-weight:900;color:#dc2626;' : 'font-weight:600;'
</script>

<template>
    <div style="font-family:Arial,sans-serif;font-size:10px;color:#111;background:white;width:210mm;padding:5mm 7mm;box-sizing:border-box;">

        <LabSlipHeader title="COMPLETE BLOOD COUNT" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- RESULTS TABLE — 2 columns -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px;margin-bottom:6px;">

            <!-- LEFT -->
            <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:2px 3px;border-bottom:1px solid #333;font-size:9px;">EXAMINATION</th>
                        <th style="text-align:left;padding:2px 3px;border-bottom:1px solid #333;font-size:9px;">RESULT</th>
                        <th style="text-align:left;padding:2px 3px;border-bottom:1px solid #333;font-size:9px;">REFERENCE RANGE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in [
                        {code:'HGB', name:'Hgb',     range:'M=135-175/F=115-155 g/L'},
                        {code:'HCT', name:'Hct',     range:'M=40-52/F=35-48 %'},
                        {code:'RBC', name:'RBC',     range:'M=4.5-5.2/F=3.9-5.6 x10 12/L'},
                        {code:'PLT', name:'Platelet',range:'150-400 x10 9/L'},
                        {code:'WBC', name:'WBC',     range:'4.0-11.0 x10 9/L'},
                    ]" :key="row.code">
                        <td style="padding:3px 3px;">{{ row.name }}</td>
                        <td style="padding:3px 3px;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:3px 3px;font-size:8px;color:#555;">{{ row.range }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="padding:4px 3px 1px;font-size:9px;color:#333;font-weight:700;">OTHERS:</td>
                    </tr>
                    <tr>
                        <td style="padding:2px 3px;">Blood Type</td>
                        <td style="padding:2px 3px;font-weight:700;">{{ r('BTYPE') }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <!-- RIGHT -->
            <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:2px 3px;border-bottom:1px solid #333;font-size:9px;">EXAMINATION</th>
                        <th style="text-align:left;padding:2px 3px;border-bottom:1px solid #333;font-size:9px;">RESULT</th>
                        <th style="text-align:left;padding:2px 3px;border-bottom:1px solid #333;font-size:9px;">REFERENCE RANGE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in [
                        {code:'SEG', name:'Segmenters',  range:'25-75%'},
                        {code:'LYM', name:'Lymphocytes', range:'15-35%'},
                        {code:'MON', name:'Monocytes',   range:'2-8%'},
                        {code:'EOS', name:'Eosinophils', range:'0-4%'},
                        {code:'BAS', name:'Basophils',   range:'0-1%'},
                    ]" :key="row.code">
                        <td style="padding:3px 3px;">{{ row.name }}</td>
                        <td style="padding:3px 3px;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:3px 3px;font-size:8px;color:#555;">{{ row.range }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
