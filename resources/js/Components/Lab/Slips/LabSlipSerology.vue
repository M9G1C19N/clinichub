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

        <LabSlipHeader title="IMMUNOLOGY AND SEROLOGY" :patient="patient" :visit="visit"/>

        <!-- RESULTS — 2 columns -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 20px;margin-bottom:6px;margin-top:10px;">

            <!-- LEFT: Dengue + Pregnancy -->
            <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:2px 3px;border-bottom:1px solid #333;font-size:9px;width:60%;">EXAMINATION</th>
                        <th style="text-align:left;padding:2px 3px;border-bottom:1px solid #333;font-size:9px;">RESULT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in [
                        {code:'DNG_NS1', name:'Dengue NS1 Ag'},
                        {code:'DNG_IGG', name:'Dengue IgG'},
                        {code:'DNG_IGM', name:'Dengue IgM'},
                    ]" :key="row.code">
                        <td style="padding:4px 3px;">{{ row.name }}</td>
                        <td style="padding:4px 3px;border-bottom:1px dashed #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>
                    <tr><td colspan="2" style="height:10px;"></td></tr>
                    <tr>
                        <td style="padding:4px 3px;">SERUM PREGNANCY TEST</td>
                        <td style="padding:4px 3px;border-bottom:1px dashed #ddd;" :style="rs('PREG')">{{ r('PREG') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- RIGHT: Screening Tests -->
            <div>
                <div style="font-weight:900;font-size:10px;margin-bottom:6px;">SCREENING TESTS ONLY</div>
                <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                    <tbody>
                        <tr v-for="row in [
                            {code:'HBSAG', name:'HBsAg'},
                            {code:'VDRL',  name:'SYPHILIS TEST (VDRL)'},
                            {code:'HIV1',  name:'HIV 1 Ab'},
                            {code:'HIV2',  name:'HIV 2 Ab'},
                        ]" :key="row.code">
                            <td style="padding:4px 3px;width:60%;">{{ row.name }}</td>
                            <td style="padding:4px 3px;border-bottom:1px dashed #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
