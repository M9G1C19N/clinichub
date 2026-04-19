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

        <LabSlipHeader title="BLOOD CHEMISTRY" :patient="patient" :visit="visit" :lab-request="labRequest"/>

        <!-- RESULTS — 2 columns, flex:1 to fill space -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 20px;margin-bottom:6px;flex:1;">

            <!-- LEFT -->
            <table style="width:100%;border-collapse:collapse;font-size:11px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:4px 4px;border-bottom:1.5px solid #333;font-size:10.5px;">EXAMINATION</th>
                        <th style="text-align:left;padding:4px 4px;border-bottom:1.5px solid #333;font-size:10.5px;">RESULT</th>
                        <th style="text-align:left;padding:4px 4px;border-bottom:1.5px solid #333;font-size:10.5px;">NORMAL VALUES</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in [
                        {code:'FBS',    name:'FBS',        range:'75-115 mg/dl'},
                        {code:'BUN',    name:'BUN',        range:'<33.1 mg/dl'},
                        {code:'CREAT',  name:'Creatinine', range:'M=0.6-1.4/F=0.6-1.2 mg/dl'},
                        {code:'UA_CHEM',name:'Uric Acid',  range:'M=3.5-7.5/F=2.6-6.0 mg/dl'},
                        {code:'SGPT',   name:'SGPT',       range:'M=<41/F=<31 U/L'},
                        {code:'SGOT',   name:'SGOT',       range:'M=<37/F=<31 U/L'},
                        {code:'RBS',    name:'RBS',        range:'60-200 mg/dl'},
                    ]" :key="row.code">
                        <td style="padding:5px 4px;border-bottom:1px solid #e0e0e0;">{{ row.name }}</td>
                        <td style="padding:5px 4px;border-bottom:1px solid #e0e0e0;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:5px 4px;border-bottom:1px solid #e0e0e0;font-size:9.5px;color:#555;">{{ row.range }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="padding:5px 4px;font-size:10.5px;color:#555;">Note: {{ labRequest?.remarks ?? '' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- RIGHT -->
            <table style="width:100%;border-collapse:collapse;font-size:11px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:4px 4px;border-bottom:1.5px solid #333;font-size:10.5px;">EXAMINATION</th>
                        <th style="text-align:left;padding:4px 4px;border-bottom:1.5px solid #333;font-size:10.5px;">RESULT</th>
                        <th style="text-align:left;padding:4px 4px;border-bottom:1.5px solid #333;font-size:10.5px;">NORMAL VALUES</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in [
                        {code:'CHOL', name:'Total Cholesterol',    range:'<200 mg/dl'},
                        {code:'TRIG', name:'Triglycerides',        range:'<200 mg/dL'},
                        {code:'HDL',  name:'HDL',                  range:'>35 mg/dl'},
                        {code:'LDL',  name:'LDL',                  range:'<110 mg/dl'},
                        {code:'VLDL', name:'VLDL',                 range:'5-40 mg/dL'},
                        {code:'KPOT', name:'Potassium (K)',         range:'3.5-5.2 mmol/L'},
                        {code:'NA',   name:'Sodium (Na)',           range:'135-145 mmol/L'},
                        {code:'CL',   name:'Chloride (Cl)',         range:'98-107 mmol/L'},
                        {code:'ICA',  name:'Ionized Calcium (iCa)', range:'1.10-1.35 mmol/L'},
                    ]" :key="row.code">
                        <td style="padding:5px 4px;border-bottom:1px solid #e0e0e0;">{{ row.name }}</td>
                        <td style="padding:5px 4px;border-bottom:1px solid #e0e0e0;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:5px 4px;border-bottom:1px solid #e0e0e0;font-size:9.5px;color:#555;">{{ row.range }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <LabSlipFooter :lab-request="labRequest"/>
    </div>
</template>
