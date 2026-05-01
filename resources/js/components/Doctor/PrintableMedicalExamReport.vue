<script setup>
import { computed } from 'vue'
import { CLINIC_LOGO } from '@/config/clinic.js'

const props = defineProps({
    visit:        Object,
    patient:      Object,
    vitals:       Object,
    consultation: Object,
    labResults:   Object,
    labRequest:   Object,
    imaging:      Object,
    drugTest:     Object,
    printPage:    { type: String, default: 'both' }, // 'both' | '1' | '2'
})

const r    = (code) => props.labResults?.[code]?.value ?? ''
const isAb = (code) => props.labResults?.[code]?.is_abnormal ?? false
const rs   = (code) => isAb(code) ? 'font-weight:900;color:#c00;' : ''

const isPE = computed(() =>
    props.visit?.visit_type === 'pre_employment'
)

const visitTypeLabel = {
    pre_employment: 'PRE-EMPLOYMENT',
    annual_pe:      'ANNUAL PE',
    exit_pe:        'EXIT PE',
    opd:            'OPD',
    follow_up:      'FOLLOW-UP',
}

const illnessCols = [
    [
        {key:'tuberculosis', label:'1. Tuberculosis'},
        {key:'asthma',       label:'2. Asthma'},
        {key:'hypertension', label:'3. Hypertension'},
        {key:'heart_disease',label:'4. Heart Disease/AMI'},
        {key:'cva_stroke',   label:'5. CVA/Stroke'},
        {key:'diabetes',     label:'6. Diabetes'},
    ],
    [
        {key:'kidney',       label:'7. Kidney Disease'},
        {key:'liver',        label:'8. Liver Disease'},
        {key:'fainting',     label:'9. Fainting/Seizure'},
        {key:'headaches',    label:'10. Headaches/Migraine'},
        {key:'mental',       label:'11. Mental Disorder'},
        {key:'std',          label:'12. Sexually Transmitted Disease'},
    ],
    [
        {key:'malaria',      label:'13. Malaria/Typhoid'},
        {key:'hernia',       label:'14. Hernia'},
        {key:'hemorrhoids',  label:'15. Hemorrhoids'},
    ],
]

const peSystems = [
    {key:'eyes',label:'1. Eyes'},
    {key:'nose_sinuses',label:'2. Nose/Sinuses'},
    {key:'neck_thyroid',label:'4. Neck/Thyroid'},
    {key:'mouth_throat',label:'3. Mouth/Throat'},
    {key:'chest_breast',label:'5. Chest/Breast'},
    {key:'lungs',label:'6. Lungs'},
    {key:'heart',label:'7. Heart'},
    {key:'abdomen',label:'8. Abdomen'},
    {key:'back',label:'9. Back'},
    {key:'anus',label:'10. Anus'},
    {key:'genitals',label:'11. Genitals'},
    {key:'extremities',label:'12. Extremities'},
    {key:'skin',label:'13. Skin'},
]

const hasIllness = (key) =>
    Array.isArray(props.vitals?.past_illnesses_flags)
        ? props.vitals.past_illnesses_flags.includes(key) : false

const peNormal   = (key) => props.vitals?.pe_findings_normal?.[key] === true
const peAbnormal = (key) => props.vitals?.pe_findings_normal?.[key] === false

const classDesc = {
    A: 'Physically fit for all types of work. No defects noted.',
    B: 'Physically fit for all types of work. Has minor and curable ailment that offers NO handicap to the job applied.',
    C: 'With findings that are generally unacceptable. Employment at risk and at the discretion of managment.',
    D: 'Not fit for employment.',
    E: 'Lacking in requirements or need further work-up.',
}
const classRec = {
    A: 'FIT TO WORK',
    B: 'FIT TO WORK WITH FINDINGS',
    C: 'CONDITIONALLY FIT',
    D: 'NOT FIT FOR EMPLOYMENT',
    E: 'FURTHER EVALUATION NEEDED',
}
</script>

<template>
<div style="font-family:Arial,sans-serif;font-size:9.5px;color:#111;background:white;line-height:1.35;">

<!-- ═══════════ PAGE 1 — MEDICAL HISTORY ═══════════ -->
<div v-if="printPage !== '2'" style="width:210mm;height:297mm;padding:5mm 6mm 7mm;box-sizing:border-box;position:relative;overflow:hidden;display:flex;flex-direction:column;">

    <!-- HEADER -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:3px;">
        <div style="display:flex;align-items:center;gap:6px;">
            <img :src="CLINIC_LOGO" style="width:50px;height:50px;object-fit:contain;flex-shrink:0;"/>
            <div>
                <div style="font-weight:900;font-size:13.5px;line-height:1.2;">SAINT PETER DIAGNOSTICS AND LABORATORY</div>
                <div style="font-size:9.5px;font-style:italic;">Medical and Dental Clinic</div>
                <div style="font-size:9.5px;">Surigao-Davao Coastal Road, Brgy. Ladgaron, Claver, Surigao del Norte 8410</div>
            </div>
        </div>
        <div style="text-align:right;font-size:9px;">
            <div>Contact No.: SMART: 09516832212</div>
            <div>E-mail: spdl.claver2007@gmail.com</div>
        </div>
    </div>

    <!-- TITLE -->
    <div style="border:2.5px solid #B8860B;text-align:center;padding:5px 0;margin-bottom:4px;">
        <span style="font-size:16px;font-weight:900;letter-spacing:2px;">MEDICAL EXAMINATION REPORT</span>
    </div>

    <!-- PATIENT INFO -->
    <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:3px;">
        <tr>
            <td style="padding:1.5px 0;width:55%;">
                <span style="color:#444;">Name: </span><strong style="font-size:12px;">{{ patient.full_name?.toUpperCase() }}</strong>
            </td>
            <td style="padding:1.5px 0;text-align:right;">
                <span style="color:#444;">Control No.: </span><strong>{{ visit.case_number }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:1.5px 0;">
                <span style="color:#444;">Age/Sex: </span><strong>{{ patient.age_sex }}</strong>
                &nbsp;&nbsp;<span style="color:#444;">Birthdate: </span><strong>{{ patient.birthdate }}</strong>
                &nbsp;&nbsp;<span style="color:#444;">Civil Status: </span><strong style="text-transform:uppercase;">{{ patient.civil_status }}</strong>
            </td>
            <td style="padding:1.5px 0;text-align:right;">
                <span style="color:#444;">Exam Type: </span><strong>{{ visitTypeLabel[visit.visit_type] }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:1.5px 0;">
                <span style="color:#444;">Address: </span><strong style="text-transform:uppercase;">{{ patient.address }}</strong>
            </td>
            <td style="padding:1.5px 0;text-align:right;">
                <span style="color:#444;">Company: </span><strong>{{ visit.employer_company }}</strong>
            </td>
        </tr>
        <tr>
            <td style="padding:1.5px 0;">
                <span style="color:#444;">Designation: </span><strong>{{ consultation?.position_applied || '' }}</strong>
            </td>
            <td style="padding:1.5px 0;text-align:right;">
                <span style="color:#444;">Exam Date: </span><strong>{{ visit.visit_date }}</strong>
            </td>
        </tr>
    </table>

    <div style="border-top:2px solid #111;margin-bottom:3px;"></div>
    <div style="font-weight:900;font-size:13px;margin-bottom:4px;">I. MEDICAL HISTORY</div>

    <!-- A -->
    <div style="font-size:12px;margin-bottom:4px;padding:4px 7px;border:1px solid #bbb;display:flex;align-items:baseline;gap:4px;">
        <strong style="white-space:nowrap;">A. PRESENT SYMPTOMS/COMPLAINTS:</strong>
        <span style="flex:1;border-bottom:1px solid #444;min-height:14px;display:inline-block;padding-bottom:1px;">{{ vitals?.present_symptoms || '' }}</span>
    </div>

    <!-- B — 3 illness cols only (no remarks column) -->
    <div style="margin-bottom:4px;">
        <div style="font-weight:700;font-size:12px;margin-bottom:2px;border-bottom:1.5px solid #222;padding-bottom:1px;">B. PAST ILLNESSES/HOSPITALIZATIONS</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;border:1px solid #bbb;">
            <div style="border-right:1px solid #bbb;padding:5px 6px;">
                <div v-for="ill in illnessCols[0]" :key="ill.key"
                    style="display:flex;align-items:center;gap:4px;margin-bottom:4px;font-size:11px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                        <rect x="1" y="1" width="10" height="10" :fill="hasIllness(ill.key)?'#111':'none'" :stroke="hasIllness(ill.key)?'#111':'#555'" stroke-width="1.5"/>
                        <text v-if="hasIllness(ill.key)" x="6" y="9.5" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                    </svg>{{ ill.label }}
                </div>
            </div>
            <div style="border-right:1px solid #bbb;padding:5px 6px;">
                <div v-for="ill in illnessCols[1]" :key="ill.key"
                    style="display:flex;align-items:center;gap:4px;margin-bottom:4px;font-size:11px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                        <rect x="1" y="1" width="10" height="10" :fill="hasIllness(ill.key)?'#111':'none'" :stroke="hasIllness(ill.key)?'#111':'#555'" stroke-width="1.5"/>
                        <text v-if="hasIllness(ill.key)" x="6" y="9.5" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                    </svg>{{ ill.label }}
                </div>
            </div>
            <div style="padding:5px 6px;">
                <div v-for="ill in illnessCols[2]" :key="ill.key"
                    style="display:flex;align-items:center;gap:4px;margin-bottom:4px;font-size:11px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                        <rect x="1" y="1" width="10" height="10" :fill="hasIllness(ill.key)?'#111':'none'" :stroke="hasIllness(ill.key)?'#111':'#555'" stroke-width="1.5"/>
                        <text v-if="hasIllness(ill.key)" x="6" y="9.5" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                    </svg>{{ ill.label }}
                </div>
                <div style="display:flex;align-items:center;gap:4px;font-size:11px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" style="display:inline-block;vertical-align:middle;flex-shrink:0;"><rect x="1" y="1" width="10" height="10" fill="none" stroke="#555" stroke-width="1.5"/></svg>
                    <span>Others: <span style="display:inline-block;width:24px;border-bottom:1px solid #555;"></span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- C, D, E -->
    <div style="display:flex;flex-direction:column;gap:3px;margin-bottom:4px;font-size:12px;">
        <div style="padding:4px 7px;border:1px solid #bbb;display:flex;align-items:baseline;gap:4px;">
            <strong style="white-space:nowrap;">C. FAMILY MEDICAL HISTORY</strong>
            <span style="flex:1;border-bottom:1px solid #444;min-height:14px;display:inline-block;padding-bottom:1px;">{{ vitals?.family_history || '' }}</span>
        </div>
        <div style="padding:4px 7px;border:1px solid #bbb;display:flex;align-items:baseline;gap:4px;">
            <strong style="white-space:nowrap;">D. ACCIDENTS/INJURIES</strong>
            <span style="flex:1;border-bottom:1px solid #444;min-height:14px;display:inline-block;padding-bottom:1px;">{{ vitals?.accidents_injuries || '' }}</span>
        </div>
        <div style="padding:4px 7px;border:1px solid #bbb;display:flex;align-items:baseline;gap:4px;">
            <strong style="white-space:nowrap;">E. SURGICAL HISTORY</strong>
            <span style="flex:1;border-bottom:1px solid #444;min-height:14px;display:inline-block;padding-bottom:1px;">{{ vitals?.surgical_history_detail || '' }}</span>
        </div>
    </div>

    <!-- F through I (left) + Remarks (right) — single tall container -->
    <div style="display:grid;grid-template-columns:3fr 2fr;gap:0;margin-bottom:4px;border:1px solid #bbb;">
        <!-- LEFT: F, G, H, I stacked -->
        <div style="border-right:1px solid #bbb;font-size:12px;">
            <!-- F -->
            <div style="padding:4px 7px;border-bottom:1px solid #bbb;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <strong>F. ALLERGIES</strong>
                <span v-for="opt in [{v:'none',l:'None'},{v:'food',l:'Food'},{v:'drug',l:'Drug'},{v:'others',l:'Others'}]" :key="opt.v"
                    style="display:inline-flex;align-items:center;gap:4px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                        <rect x="1" y="1" width="11" height="11" :fill="vitals?.allergies_flags?.includes(opt.v)?'#111':'none'" :stroke="vitals?.allergies_flags?.includes(opt.v)?'#111':'#555'" stroke-width="1.5"/>
                        <text v-if="vitals?.allergies_flags?.includes(opt.v)" x="6.5" y="10" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                    </svg>{{ opt.l }}
                </span>
                <span style="display:inline-flex;align-items:baseline;gap:3px;flex:1;">
                    <span style="font-size:11px;color:#555;">Specify:</span>
                    <span style="flex:1;border-bottom:1px solid #555;min-width:60px;display:inline-block;font-size:11px;padding-bottom:1px;">{{ vitals?.allergies_others || '' }}</span>
                </span>
            </div>
            <!-- G — female only -->
            <div style="padding:4px 7px;border-bottom:1px solid #bbb;">
                <strong>G. MENSTRUAL HISTORY</strong><br/>
                Menstrual Cycle:
                <span v-for="opt in [{v:'regular',l:'Regular'},{v:'irregular',l:'Irregular'},{v:'menopause',l:'Menopause'},{v:'postmenopausal',l:'Postmenopausal'}]" :key="opt.v"
                    style="display:inline-flex;align-items:center;gap:3px;margin-right:5px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                        <rect x="1" y="1" width="11" height="11" :fill="vitals?.menstrual_cycle===opt.v?'#111':'none'" :stroke="vitals?.menstrual_cycle===opt.v?'#111':'#555'" stroke-width="1.5"/>
                        <text v-if="vitals?.menstrual_cycle===opt.v" x="6.5" y="10" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                    </svg>{{ opt.l }}
                </span>
                <br/>LMP and Duration: <strong>{{ vitals?.lmp || 'N/A' }}</strong>
            </div>
            <!-- H — female only -->
            <div style="padding:4px 7px;border-bottom:1px solid #bbb;">
                <strong>H. OB HISTORY</strong><br/>
                <span style="display:inline-flex;align-items:center;gap:4px;margin-right:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                        <rect x="1" y="1" width="11" height="11" :fill="vitals?.ob_nulligravida?'#111':'none'" :stroke="vitals?.ob_nulligravida?'#111':'#555'" stroke-width="1.5"/>
                        <text v-if="vitals?.ob_nulligravida" x="6.5" y="10" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                    </svg>Nulligravida
                </span>
                Gravida: <strong>{{ vitals?.ob_gravida || 'N/A' }}</strong>
                &nbsp; Para: <strong>{{ vitals?.ob_para || 'N/A' }}</strong>
            </div>
            <!-- I -->
            <div style="padding:4px 7px;">
                <strong>I. PERSONAL/SOCIAL HISTORY</strong>
                <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                    <strong style="display:inline-block;min-width:85px;flex-shrink:0;">TOBACCO USE:</strong>
                    <span v-for="opt in [{v:'current',l:'Current'},{v:'former',l:'Former'},{v:'never',l:'Never'}]" :key="opt.v"
                        style="display:inline-flex;align-items:center;gap:4px;margin-right:5px;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                            <rect x="1" y="1" width="11" height="11" :fill="vitals?.tobacco_use===opt.v?'#111':'none'" :stroke="vitals?.tobacco_use===opt.v?'#111':'#555'" stroke-width="1.5"/>
                            <text v-if="vitals?.tobacco_use===opt.v" x="6.5" y="10" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                        </svg>{{ opt.l }}
                    </span>
                    <span style="flex:1;border-bottom:1px solid #444;min-width:40px;display:inline-block;font-size:11px;padding-bottom:1px;">{{ vitals?.tobacco_use_details || '' }}</span>
                </div>
                <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                    <strong style="display:inline-block;min-width:85px;flex-shrink:0;">ALCOHOL:</strong>
                    <span v-for="opt in [{v:'current',l:'Current'},{v:'former',l:'Former'},{v:'never',l:'Never'}]" :key="opt.v"
                        style="display:inline-flex;align-items:center;gap:4px;margin-right:5px;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                            <rect x="1" y="1" width="11" height="11" :fill="vitals?.alcohol_use===opt.v?'#111':'none'" :stroke="vitals?.alcohol_use===opt.v?'#111':'#555'" stroke-width="1.5"/>
                            <text v-if="vitals?.alcohol_use===opt.v" x="6.5" y="10" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                        </svg>{{ opt.l }}
                    </span>
                    <span style="flex:1;border-bottom:1px solid #444;min-width:40px;display:inline-block;font-size:11px;padding-bottom:1px;">{{ vitals?.alcohol_use_details || '' }}</span>
                </div>
            </div>
        </div>
        <!-- RIGHT: Nurse Remarks — spans full F-I height -->
        <div style="padding:6px 8px;display:flex;flex-direction:column;">
            <strong style="font-size:12px;">Remarks:</strong>
            <div style="flex:1;font-size:12px;line-height:1.7;white-space:pre-wrap;padding-top:5px;">{{ vitals?.pe_remarks || '' }}</div>
        </div>
    </div>

    <!-- 2-col flex area fills remaining height -->
    <div style="display:grid;grid-template-columns:300px 1fr;gap:0 5px;flex:1;min-height:0;overflow:hidden;">

        <!-- LEFT — Vitals & Visual Acuity (fixed 300px) -->
        <!-- II. PHYSICAL EXAMINATION header is inside left column so right column gains the freed height -->
        <div style="font-size:12px;">
            <div style="border-top:2px solid #111;padding-top:2px;margin-bottom:3px;">
                <div style="font-weight:900;font-size:13px;">II. PHYSICAL EXAMINATION</div>
            </div>
            <div v-for="row in [
                {label:'A. Weight (kg.)',          val:vitals?.weight_kg},
                {label:'B. Height (m)',            val:vitals?.height_cm?(vitals.height_cm/100).toFixed(2):''},
                {label:'C. BMI (Body Mass Index)', val:vitals?.bmi},
                {label:'D. Pulse (beats/min)',     val:vitals?.pulse_rate},
            ]" :key="row.label"
                style="display:flex;justify-content:space-between;align-items:center;padding:5px 7px;border-bottom:1px solid #bbb;border-left:1px solid #bbb;border-right:1px solid #bbb;">
                <span>{{ row.label }}</span>
                <strong style="min-width:55px;text-align:right;">{{ row.val??'' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 7px;border-bottom:1px solid #bbb;border-left:1px solid #bbb;border-right:1px solid #bbb;">
                <span>E. BP (Systolic/Diastolic)</span>
                <strong>{{ vitals?.blood_pressure_systolic||'' }}{{ vitals?.blood_pressure_systolic?' / ':'' }}{{ vitals?.blood_pressure_diastolic||'' }}{{ vitals?.blood_pressure_systolic?' mmHg':'' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 7px;border-bottom:1px solid #bbb;border-left:1px solid #bbb;border-right:1px solid #bbb;">
                <span>F. Conversational Hearing</span>
                <span style="display:flex;align-items:center;gap:8px;">
                    <span v-for="opt in [{v:'Normal',l:'Normal'},{v:'Defective',l:'Defective'}]" :key="opt.v"
                        style="display:inline-flex;align-items:center;gap:4px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                            <rect x="1" y="1" width="11" height="11" :fill="vitals?.conversational_hearing===opt.v?'#111':'none'" :stroke="vitals?.conversational_hearing===opt.v?'#111':'#555'" stroke-width="1.5"/>
                            <text v-if="vitals?.conversational_hearing===opt.v" x="6.5" y="10" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                        </svg>{{ opt.l }}
                    </span>
                </span>
            </div>
            <!-- G. Visual Acuity -->
            <div style="border:1px solid #bbb;">
                <!-- Header uses same grid as data rows so columns align exactly -->
                <div style="display:grid;grid-template-columns:1fr 28px 52px 52px;align-items:center;padding:3px 7px;border-bottom:1px solid #ccc;">
                    <span style="font-size:12px;font-weight:700;">G. Visual Acuity</span>
                    <span></span>
                    <span style="font-size:10px;color:#555;text-align:center;">Uncorrected</span>
                    <span style="font-size:10px;color:#555;text-align:center;border-left:1.5px solid #aaa;padding-left:4px;">Corrected</span>
                </div>
                <div style="padding:4px 7px;font-size:12px;">
                    <div style="display:grid;grid-template-columns:1fr 28px 52px 52px;align-items:end;margin-bottom:2px;">
                        <span>1. Distant Vision</span>
                        <span style="text-align:right;padding-right:4px;">OD</span>
                        <strong style="text-align:center;border-bottom:1px solid #666;display:block;padding:0 4px;">{{ vitals?.visual_acuity_right||'' }}</strong>
                        <strong style="text-align:center;border-bottom:1px solid #666;border-left:1.5px solid #aaa;display:block;padding:0 4px;">{{ vitals?.visual_acuity_right_corrected||'' }}</strong>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 28px 52px 52px;align-items:end;margin-bottom:4px;">
                        <span></span>
                        <span style="text-align:right;padding-right:4px;">OS</span>
                        <strong style="text-align:center;border-bottom:1px solid #666;display:block;padding:0 4px;">{{ vitals?.visual_acuity_left||'' }}</strong>
                        <strong style="text-align:center;border-bottom:1px solid #666;border-left:1.5px solid #aaa;display:block;padding:0 4px;">{{ vitals?.visual_acuity_left_corrected||'' }}</strong>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 28px 52px 52px;align-items:end;margin-bottom:2px;">
                        <span>2. Near Vision</span>
                        <span style="text-align:right;padding-right:4px;">OD</span>
                        <strong style="text-align:center;border-bottom:1px solid #666;display:block;padding:0 4px;">{{ vitals?.visual_acuity_near_right||'' }}</strong>
                        <strong style="text-align:center;border-bottom:1px solid #666;border-left:1.5px solid #aaa;display:block;padding:0 4px;">{{ vitals?.visual_acuity_near_right_corrected||'' }}</strong>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 28px 52px 52px;align-items:end;margin-bottom:4px;">
                        <span></span>
                        <span style="text-align:right;padding-right:4px;">OS</span>
                        <strong style="text-align:center;border-bottom:1px solid #666;display:block;padding:0 4px;">{{ vitals?.visual_acuity_near_left||'' }}</strong>
                        <strong style="text-align:center;border-bottom:1px solid #666;border-left:1.5px solid #aaa;display:block;padding:0 4px;">{{ vitals?.visual_acuity_near_left_corrected||'' }}</strong>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 28px 52px 52px;align-items:end;margin-bottom:2px;">
                        <span>3. Ishihara Test</span>
                        <span></span>
                        <strong style="text-align:center;border-bottom:1px solid #666;display:block;padding:0 4px;grid-column:3/5;">{{ vitals?.ishihara_result||'' }}</strong>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 28px 52px 52px;align-items:end;">
                        <span>4. Color Vision Result</span>
                        <span></span>
                        <strong style="text-align:center;border-bottom:1px solid #666;display:block;padding:0 4px;grid-column:3/5;">{{ vitals?.color_vision_result||'' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT — Photo, Consent, Signature, PE Table (expands to fill remaining width) -->
        <div style="display:flex;flex-direction:column;min-height:0;overflow:hidden;border-top:2px solid #111;padding-top:2px;">
            <!-- Photo + consent text -->
            <div style="display:flex;gap:6px;margin-bottom:2px;">
                <div style="width:110px;height:110px;border:1.5px solid #888;flex-shrink:0;overflow:hidden;background:#ddd;display:flex;align-items:center;justify-content:center;">
                    <img v-if="patient?.photo_path" :src="patient.photo_path"
                        style="width:100%;height:100%;object-fit:cover;display:block;"
                        crossorigin="anonymous"/>
                    <span v-else style="font-size:9px;color:#888;text-align:center;line-height:1.6;">2x2<br/>Photo</span>
                </div>
                <div style="flex:1;border:1px solid #888;padding:8px;font-size:10px;font-style:italic;color:#333;line-height:1.75;display:flex;align-items:center;">
                    "I certify that all information I have given in my medical history is true and that any false statement will disqualify me from my employment application, benefits and claims. Furthermore, I authorize the medical examiner to release results of my medical examination to my employer/prospective employer."
                </div>
            </div>
            <!-- Signature block — 2 cols matching photo/consent layout above -->
            <div style="display:flex;gap:6px;margin-bottom:2px;">
                <!-- LEFT: under photo — blank signing space + name line -->
                <div style="width:110px;flex-shrink:0;text-align:center;">
                    <div style="height:7mm;"></div>
                    <div style="border-top:1.5px solid #222;padding-top:2px;font-weight:700;font-size:11px;word-break:break-word;overflow-wrap:anywhere;line-height:1.3;">
                        {{ patient.full_name?.toUpperCase() }}
                    </div>
                    <div style="font-size:10px;color:#555;">Signature Over Printed Name</div>
                </div>
                <!-- RIGHT: under consent text -->
                <div style="flex:1;display:flex;align-items:center;justify-content:center;">
                    <div style="font-weight:900;font-size:14px;text-align:center;letter-spacing:0.5px;">NOT VALID WITHOUT SEAL</div>
                </div>
            </div>
            <!-- PE Findings table — 2 columns -->
            <div style="flex-shrink:0;display:flex;flex-direction:column;border:1px solid #bbb;overflow:hidden;">
                <!-- 2-col body -->
                <div style="display:grid;grid-template-columns:1fr 1fr;flex-shrink:0;overflow:hidden;">
                    <!-- Left column: items 1–7 -->
                    <div style="border-right:1px solid #bbb;display:flex;flex-direction:column;">
                        <div style="display:grid;grid-template-columns:14px 1fr 1fr;border-bottom:1.5px solid #222;padding:3px 4px;font-weight:700;font-size:10px;flex-shrink:0;">
                            <div></div><div>Normal &nbsp; Exam</div><div>P.E. Findings</div>
                        </div>
                        <div v-for="sys in peSystems.slice(0,7)" :key="sys.key"
                            style="display:grid;grid-template-columns:14px 1fr 1fr;align-items:center;border-bottom:1px solid #ddd;padding:2.5px 4px;flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                                <rect x="1" y="1" width="10" height="10" :fill="peNormal(sys.key)?'#111':'none'" :stroke="peNormal(sys.key)?'#111':'#555'" stroke-width="1.5"/>
                                <text v-if="peNormal(sys.key)" x="6" y="9.5" text-anchor="middle" font-size="8" fill="white" font-weight="bold" font-family="Arial">✓</text>
                            </svg>
                            <span style="font-size:11px;">{{ sys.label }}</span>
                            <span style="color:#c00;font-size:10px;font-weight:700;">{{ peAbnormal(sys.key)?(vitals?.pe_findings_details?.[sys.key]||'Abnormal'):'' }}</span>
                        </div>
                    </div>
                    <!-- Right column: items 8–13 -->
                    <div style="display:flex;flex-direction:column;">
                        <div style="display:grid;grid-template-columns:14px 1fr 1fr;border-bottom:1.5px solid #222;padding:3px 4px;font-weight:700;font-size:10px;flex-shrink:0;">
                            <div></div><div>Normal &nbsp; Exam</div><div>P.E. Findings</div>
                        </div>
                        <div v-for="sys in peSystems.slice(7)" :key="sys.key"
                            style="display:grid;grid-template-columns:14px 1fr 1fr;align-items:center;border-bottom:1px solid #ddd;padding:2.5px 4px;flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" style="display:inline-block;vertical-align:middle;flex-shrink:0;">
                                <rect x="1" y="1" width="10" height="10" :fill="peNormal(sys.key)?'#111':'none'" :stroke="peNormal(sys.key)?'#111':'#555'" stroke-width="1.5"/>
                                <text v-if="peNormal(sys.key)" x="6" y="9.5" text-anchor="middle" font-size="8" fill="white" font-weight="bold" font-family="Arial">✓</text>
                            </svg>
                            <span style="font-size:11px;">{{ sys.label }}</span>
                            <span style="color:#c00;font-size:10px;font-weight:700;">{{ peAbnormal(sys.key)?(vitals?.pe_findings_details?.[sys.key]||'Abnormal'):'' }}</span>
                        </div>
                    </div>
                </div>
                <!-- Other PE Findings — full width at bottom -->
                <div style="padding:4px 6px;font-size:11px;border-top:1px solid #ddd;flex-shrink:0;">
                    <strong>Other PE Findings:</strong> {{ vitals?.pe_findings_remarks || '' }}
                </div>
            </div>
        </div>
    </div>

    <!-- P1 FOOTER -->
    <div style="position:absolute;bottom:5mm;left:6mm;right:6mm;display:flex;justify-content:space-between;border-top:1px solid #333;padding-top:2px;font-size:9px;">
        <strong>SAINT PETER DIAGNOSTICS AND LABORATORY</strong>
        <span>{{ patient.full_name?.toUpperCase() }}</span>
        <span>Medical Examination Report 1/2</span>
    </div>
</div>

<!-- ═══════════ PAGE 2 — LAB RESULTS ═══════════ -->
<div v-if="printPage !== '1'" style="width:210mm;height:297mm;padding:6mm 7mm 8mm;box-sizing:border-box;position:relative;overflow:hidden;display:flex;flex-direction:column;">

    <!-- HEADER -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:3px;">
        <div style="display:flex;align-items:center;gap:6px;">
            <img :src="CLINIC_LOGO" style="width:40px;height:40px;object-fit:contain;flex-shrink:0;"/>
            <div>
                <div style="font-weight:900;font-size:13px;line-height:1.2;">SAINT PETER DIAGNOSTICS AND LABORATORY</div>
                <div style="font-size:9px;font-style:italic;">Medical and Dental Clinic</div>
                <div style="font-size:9px;">Surigao-Davao Coastal Road, Brgy. Ladgaron, Claver, Surigao del Norte 8410</div>
            </div>
        </div>
        <div style="text-align:right;font-size:9px;">
            <div>Contact No.: SMART: 09516832212</div>
            <div>E-mail: spdl.claver2007@gmail.com</div>
        </div>
    </div>

    <!-- Patient bar -->
    <div style="border-top:1.5px solid #111;border-bottom:1px solid #aaa;padding:3px 0;margin-bottom:4px;font-size:10px;">
        <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <span><span style="color:#555;">Name: </span><strong>{{ patient.full_name?.toUpperCase() }}</strong></span>
            <span><span style="color:#555;">Control No.: </span><strong>24 - {{ visit.case_number }}</strong></span>
            <span><strong>{{ patient.age_sex }}</strong></span>
            <span><span style="color:#555;">Birthdate: </span><strong>{{ patient.birthdate }}</strong></span>
            <span><span style="color:#555;">Civil Status: </span><strong style="text-transform:capitalize;">{{ patient.civil_status }}</strong></span>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:1px;">
            <span><span style="color:#555;">Exam Type: </span><strong>{{ visitTypeLabel[visit.visit_type] }}</strong></span>
            <span><span style="color:#555;">Company: </span><strong>{{ visit.employer_company }}</strong></span>
            <span v-if="consultation?.position_applied"><span style="color:#555;">DESIGNATION </span><strong>{{ consultation.position_applied }}</strong></span>
            <span style="margin-left:auto;"><span style="color:#555;">Exam Date: </span><strong>{{ visit.visit_date }}</strong></span>
        </div>
    </div>

    <!-- LAB RESULT TITLE -->
    <div style="text-align:center;font-size:14px;font-weight:900;letter-spacing:2px;border-bottom:1.5px solid #111;padding-bottom:2px;margin-bottom:5px;">
        LABORATORY RESULT
    </div>

    <!-- ── PRE-EMPLOYMENT ── -->
    <template v-if="isPE">
        <!-- CBC | Blood Chemistry | Urinalysis — 3 columns -->
        <div style="display:grid;grid-template-columns:1fr 1.1fr 1fr;gap:0 8px;margin-bottom:4px;">
            <!-- CBC -->
            <div>
                <div style="font-weight:900;font-size:12px;text-align:center;margin-bottom:3px;">COMPLETE BLOOD COUNT</div>
                <table style="width:100%;border-collapse:collapse;font-size:10.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:3px 4px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:3px 4px;width:18%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:3px 4px;">REFERENCE RANGE</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'HGB',name:'Hemoglobin',    range:'M=135-175/F=115-155 g/L'},
                        {code:'HCT',name:'Hematocrit',    range:'M=40-52/F=35-48%'},
                        {code:'RBC',name:'RBC Count',     range:'M=4.5-5.2/F=3.9-5.6 x10 g/L'},
                        {code:'PLT',name:'Platelet Count',range:'150-400 x10 g/L'},
                        {code:'WBC',name:'WBC Count',     range:'4.0-11.0 x10 g/L'},
                        {code:'SEG',name:'Segmenters',    range:'50-70%'},
                        {code:'LYM',name:'Lymphocytes',   range:'26-46%'},
                        {code:'MID',name:'Mid',           range:'2-10%'},
                    ]" :key="row.code" style="border-bottom:1px solid #333;">
                        <td style="padding:4px 3px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:4px 3px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:4px 3px;color:#555;font-size:9.5px;">{{ row.range }}</td>
                    </tr>
                </table>
            </div>
            <!-- Blood Chemistry -->
            <div>
                <div style="font-weight:900;font-size:12px;text-align:center;margin-bottom:3px;">BLOOD CHEMISTRY</div>
                <table style="width:100%;border-collapse:collapse;font-size:10.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:3px 4px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:3px 4px;width:18%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:3px 4px;">REFERENCE RANGE</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'FBS',    name:'Fasting Blood Sugar', range:'75-115 mg/dL'},
                        {code:'BUN',    name:'Blood Urea Nitrogen', range:'<33.1 mg/dL'},
                        {code:'CREAT',  name:'Creatinine',          range:'M=0.6-1.4/F=0.6-1.2 mg/dL'},
                        {code:'UA_CHEM',name:'Uric Acid',           range:'M=3.5-7.5/F=2.6-6.0 mg/dL'},
                        {code:'SGPT',   name:'SGPT',                range:'M=<41/F=<31 U/L'},
                        {code:'SGOT',   name:'SGOT',                range:'M=<37/F=<31 U/L'},
                        {code:'CHOL',   name:'Total Cholesterol',   range:'<200 mg/dL'},
                        {code:'TRIG',   name:'Triglycerides',       range:'<200 mg/dL'},
                        {code:'HDL',    name:'HDL',                 range:'>35 mg/dL'},
                        {code:'LDL',    name:'LDL',                 range:'<110 mg/dL'},
                        {code:'VLDL',   name:'VLDL',                range:'<5-40 mg/dL'},
                    ]" :key="row.code" style="border-bottom:1px solid #333;">
                        <td style="padding:4px 3px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:4px 3px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:4px 3px;color:#555;font-size:9.5px;">{{ row.range }}</td>
                    </tr>
                </table>
            </div>
            <!-- Urinalysis -->
            <div>
                <div style="font-weight:900;font-size:12px;text-align:center;margin-bottom:3px;">URINALYSIS</div>
                <table style="width:100%;border-collapse:collapse;font-size:10.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:3px 4px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:3px 4px;width:22%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:3px 4px;">REF. RANGE</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'UA_COLOR',name:'Urine Color',       range:'Yellow'},
                        {code:'UA_TRANS',name:'Transparency',      range:'Clear'},
                        {code:'UA_PH',   name:'pH',                range:'4.5-8.0'},
                        {code:'UA_SG',   name:'Specific Gravity',  range:'1.010-1.025'},
                        {code:'UA_SUGAR',name:'Sugar',             range:'Negative'},
                        {code:'UA_PRO',  name:'Protein',           range:'Negative'},
                        {code:'UA_PUS',  name:'Pus Cells',         range:'0-5/hpf'},
                        {code:'UA_RBC',  name:'RBC',               range:'0-3/hpf'},
                        {code:'UA_EPI',  name:'Epithelial Cells',  range:'Few'},
                        {code:'UA_MUC',  name:'Mucus Threads',     range:'Few'},
                        {code:'UA_AMO',  name:'Amorphous',         range:'Few'},
                        {code:'UA_BAC',  name:'Bacteria',          range:'None'},
                        {code:'UA_CRY',  name:'Crystals',          range:'None'},
                        {code:'UA_OTH',  name:'Others',            range:''},
                    ]" :key="row.code" style="border-bottom:1px solid #333;">
                        <td style="padding:4px 3px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:4px 3px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:4px 3px;color:#555;font-size:9.5px;">{{ row.range }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Stool | Others+LabSigs | CXR+ECG — 3 columns (merged row) -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1.5fr;gap:0 8px;border-top:1.5px solid #111;padding-top:4px;margin-bottom:4px;align-items:stretch;">
            <!-- Stool Exam -->
            <div style="display:flex;flex-direction:column;">
                <div style="font-weight:900;font-size:12px;text-align:center;margin-bottom:3px;">STOOL EXAM</div>
                <table style="width:100%;border-collapse:collapse;font-size:10.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:3px 4px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:3px 4px;width:28%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:3px 4px;">REFERENCE RANGE</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'ST_COL',name:'Color',          range:'Brown'},
                        {code:'ST_CON',name:'Consistency',    range:'Formed'},
                        {code:'ST_PUS',name:'Pus Cells',      range:'0-2/hpf'},
                        {code:'ST_RBC',name:'Red Blood Cells',range:'None'},
                        {code:'ST_OVA',name:'Ova/Parasites',  range:'None seen'},
                    ]" :key="row.code" style="border-bottom:1px solid #333;">
                        <td style="padding:4px 3px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:4px 3px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:4px 3px;color:#555;font-size:9.5px;">{{ row.range }}</td>
                    </tr>
                </table>
                <!-- Med Tech signature pushed to bottom of Stool column -->
                <div style="margin-top:auto;padding-top:4px;border-top:1px solid #aaa;text-align:center;">
                    <div style="height:28px;display:flex;align-items:flex-end;justify-content:center;overflow:visible;margin-bottom:1px;">
                        <img v-if="labRequest?.examined_by_signature"
                            :src="labRequest.examined_by_signature"
                            :style="{maxHeight:Math.round(24*(labRequest.examined_by_sig_scale||1))+'px',maxWidth:'110px'}"
                            style="object-fit:contain;display:block;"
                            crossorigin="anonymous"/>
                    </div>
                    <div style="border-top:1.5px solid #333;padding-top:2px;">
                        <div style="font-weight:700;font-size:10px;">{{ labRequest?.examined_by_name||'—' }}</div>
                        <div style="font-size:9px;">License no. {{ labRequest?.examined_by_license||'' }}</div>
                        <div style="font-size:9px;">Medical Technologist</div>
                    </div>
                </div>
            </div>
            <!-- Others table -->
            <div style="display:flex;flex-direction:column;">
                <div style="font-weight:900;font-size:12px;text-align:center;margin-bottom:3px;">OTHERS</div>
                <table style="width:100%;border-collapse:collapse;font-size:10.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:3px 4px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:3px 4px;">RESULT</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'HBSAG',name:'Hepatitis B sAg'},
                        {code:'VDRL', name:'VDRL'},
                        {code:'PREG', name:'Pregnancy Test'},
                        {code:'BTYPE',name:'Blood Type'},
                        {code:'PSA',  name:'Prostatic Specific Antigen (PSA)'},
                    ]" :key="row.code" style="border-bottom:1px solid #333;">
                        <td style="padding:4px 3px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:4px 3px;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>
                </table>
                <!-- Pathologist signature pushed to bottom of Others column -->
                <div style="margin-top:auto;padding-top:4px;border-top:1px solid #aaa;text-align:center;">
                    <div style="height:28px;display:flex;align-items:flex-end;justify-content:center;overflow:visible;margin-bottom:1px;">
                        <img v-if="labRequest?.noted_by_signature"
                            :src="labRequest.noted_by_signature"
                            :style="{maxHeight:Math.round(24*(labRequest.noted_by_sig_scale||1))+'px',maxWidth:'110px'}"
                            style="object-fit:contain;display:block;"
                            crossorigin="anonymous"/>
                    </div>
                    <div style="border-top:1.5px solid #333;padding-top:2px;">
                        <div style="font-weight:700;font-size:10px;">{{ labRequest?.noted_by_name||'—' }}</div>
                        <div style="font-size:9px;">License No.: {{ labRequest?.noted_by_license||'' }}</div>
                        <div style="font-size:9px;">Pathologist</div>
                    </div>
                </div>
            </div>
            <!-- CXR + ECG — equal fixed sizes on right side -->
            <div style="border-left:2px solid #111;padding-left:8px;display:flex;flex-direction:column;">
                <!-- Chest X-Ray — top half, equal size -->
                <div style="flex:1;display:flex;flex-direction:column;padding-bottom:5px;overflow:hidden;">
                    <div style="font-weight:900;font-size:11px;margin-bottom:3px;">
                        CHEST XRAY RESULT
                        <span style="font-size:8px;font-weight:400;margin-left:3px;">({{ imaging?.is_provisional ? 'Provisional Reading' : 'Official Reading' }})</span>
                        <span v-if="imaging?.request_number" style="font-weight:400;margin-left:4px;font-size:8px;">
                            Req. No: <strong>{{ imaging.request_number }}</strong>
                        </span>
                    </div>
                    <template v-if="imaging">
                        <div style="font-size:11px;margin-bottom:3px;">
                            <span style="color:#555;">Impression: </span>
                            <strong>{{ imaging.impression || '—' }}</strong>
                        </div>
                        <div style="font-size:10.5px;flex:1;min-height:20px;white-space:pre-line;border:1px solid #e0e0e0;padding:3px 4px;background:#fafafa;overflow:hidden;">{{ imaging.radiographic_findings || '' }}</div>
                        <div style="font-size:8.5px;color:#444;margin-top:3px;font-style:italic;">
                            NOTE: X-ray films submitted for official reading will be available one week after submission.
                        </div>
                        <div style="margin-top:5px;font-size:10.5px;display:flex;align-items:flex-end;gap:6px;">
                            <span style="color:#555;white-space:nowrap;">Noted By:</span>
                            <div style="text-align:center;flex:1;">
                                <div>
                                    <div style="height:20px;display:flex;align-items:flex-end;justify-content:center;overflow:visible;margin-bottom:1px;">
                                        <img v-if="imaging.radiologist_signature"
                                            :src="imaging.radiologist_signature"
                                            :style="{maxHeight:Math.round(18*(imaging.radiologist_sig_scale||1))+'px',maxWidth:'90px'}"
                                            style="object-fit:contain;display:block;"
                                            crossorigin="anonymous"/>
                                    </div>
                                    <div style="border-top:1px solid #333;padding-top:2px;">
                                        <div style="font-weight:700;font-size:10px;">{{ imaging.radiologist_name || '' }}</div>
                                        <div style="font-size:8.5px;color:#555;">
                                            {{ imaging.radiologist_license ? 'Lic. No.: ' + imaging.radiologist_license : 'Radiologist' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div style="font-size:8px;color:#aaa;font-style:italic;padding:4px 0;">No X-Ray on file for this visit.</div>
                        <div style="margin-top:auto;text-align:center;">
                            <div style="border-top:1px solid #333;display:inline-block;min-width:120px;padding-top:2px;font-size:7.5px;color:#555;">Radiologist / Rad Tech</div>
                        </div>
                    </template>
                </div>
                <!-- ECG — bottom half, equal size -->
                <div style="flex:1;display:flex;flex-direction:column;border-top:2px solid #111;padding-top:5px;overflow:hidden;">
                    <div style="font-weight:900;font-size:11px;margin-bottom:3px;">ELECTROCARDIOGRAPHY (ECG)</div>
                    <template v-if="consultation?.ecg_impression || consultation?.ecg_findings">
                        <div style="font-size:11px;margin-bottom:3px;">
                            <span style="color:#555;">Impression: </span>
                            <strong>{{ consultation.ecg_impression || '—' }}</strong>
                        </div>
                        <div style="font-size:10.5px;flex:1;min-height:20px;white-space:pre-line;border:1px solid #e0e0e0;padding:3px 4px;background:#fafafa;overflow:hidden;">{{ consultation.ecg_findings || '' }}</div>
                        <div style="font-size:8.5px;color:#444;margin-top:3px;font-style:italic;">
                            NOTE: ECG is required for persons with hypertension, heart disease, and related illnesses.
                        </div>
                        <div style="margin-top:5px;font-size:10.5px;display:flex;align-items:flex-end;gap:6px;">
                            <span style="color:#555;">Noted By:</span>
                            <div style="text-align:center;flex:1;">
                                <div>
                                    <div style="height:20px;display:flex;align-items:flex-end;justify-content:center;overflow:visible;margin-bottom:1px;">
                                        <img v-if="consultation.ecg_noted_by_signature"
                                            :src="consultation.ecg_noted_by_signature"
                                            :style="{maxHeight:Math.round(18*(consultation.ecg_noted_by_sig_scale||1))+'px',maxWidth:'90px'}"
                                            style="object-fit:contain;display:block;"
                                            crossorigin="anonymous"/>
                                    </div>
                                    <div style="border-top:1px solid #333;padding-top:2px;">
                                        <div style="font-weight:700;font-size:10px;">{{ consultation.ecg_noted_by_name || '' }}</div>
                                        <div style="font-size:8.5px;color:#555;">
                                            {{ consultation.ecg_noted_by_license ? 'Lic. No.: ' + consultation.ecg_noted_by_license : '' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div style="font-size:8px;color:#aaa;font-style:italic;padding:4px 0;">No ECG data on file.</div>
                        <div style="font-size:8.5px;color:#444;margin-top:3px;font-style:italic;">
                            NOTE: ECG is required for persons with hypertension, heart disease, and related illnesses.
                        </div>
                        <div style="margin-top:auto;border-top:1px solid #333;text-align:center;padding-top:2px;font-size:7.5px;color:#555;">Noted By</div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <!-- ── ANNUAL PE / OPD ── -->
    <template v-else>
        <!-- Single 2-col layout: Left = CBC + Stool stacked | Right = Urinalysis + signatures -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 14px;margin-bottom:5px;align-items:start;">
            <!-- LEFT: CBC on top, Stool below -->
            <div style="display:flex;flex-direction:column;gap:5px;">
                <!-- CBC -->
                <div>
                    <div style="font-weight:900;font-size:12px;text-align:center;margin-bottom:3px;">COMPLETE BLOOD COUNT</div>
                    <table style="width:100%;border-collapse:collapse;font-size:10.5px;border:1px solid #333;">
                        <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                            <th style="text-align:left;padding:3px 4px;border-right:1px solid #333;">EXAMINATION</th>
                            <th style="text-align:left;padding:2px 3px;width:16%;border-right:1px solid #333;">RESULT</th>
                            <th style="text-align:left;padding:2px 3px;">REFERENCE RANGE</th>
                        </tr>
                        <tr v-for="row in [
                            {code:'HGB',name:'Hemoglobin',    range:'M=135-175/F=115-155 g/L'},
                            {code:'HCT',name:'Hematocrit',    range:'M=40-52/F=35-48%'},
                            {code:'RBC',name:'RBC Count',     range:'M=4.5-5.2/F=3.9-5.6 x10 g/L'},
                            {code:'PLT',name:'Platelet Count',range:'150-400 x10 g/L'},
                            {code:'WBC',name:'WBC Count',     range:'4.0-11.0 x10 g/L'},
                            {code:'SEG',name:'Segmenters',    range:'50-70%'},
                            {code:'LYM',name:'Lymphocytes',   range:'26-46%'},
                            {code:'MID',name:'Mid',           range:'2-10%'},
                        ]" :key="row.code" style="border-bottom:1px solid #333;">
                            <td style="padding:4px 3px;border-right:1px solid #ddd;">{{ row.name }}</td>
                            <td style="padding:4px 3px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                            <td style="padding:4px 3px;color:#555;font-size:9.5px;">{{ row.range }}</td>
                        </tr>
                    </table>
                </div>
                <!-- Stool Exam — below CBC -->
                <div>
                    <div style="font-weight:900;font-size:12px;text-align:center;margin-bottom:3px;">STOOL EXAM</div>
                    <table style="width:100%;border-collapse:collapse;font-size:10.5px;border:1px solid #333;">
                        <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                            <th style="text-align:left;padding:3px 4px;border-right:1px solid #333;">EXAMINATION</th>
                            <th style="text-align:left;padding:2px 3px;width:28%;border-right:1px solid #333;">RESULT</th>
                            <th style="text-align:left;padding:2px 3px;">REFERENCE RANGE</th>
                        </tr>
                        <tr v-for="row in [
                            {code:'ST_COL',name:'Color',          range:'Brown'},
                            {code:'ST_CON',name:'Consistency',    range:'Formed'},
                            {code:'ST_PUS',name:'Pus Cells',      range:'0-2/hpf'},
                            {code:'ST_RBC',name:'Red Blood Cells',range:'None'},
                            {code:'ST_OVA',name:'Ova/Parasites',  range:'None seen'},
                        ]" :key="row.code" style="border-bottom:1px solid #333;">
                            <td style="padding:4px 3px;border-right:1px solid #ddd;">{{ row.name }}</td>
                            <td style="padding:4px 3px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                            <td style="padding:4px 3px;color:#555;font-size:9.5px;">{{ row.range }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- RIGHT: Urinalysis + signatures below -->
            <div style="display:flex;flex-direction:column;">
                <!-- Urinalysis -->
                <div>
                    <div style="font-weight:900;font-size:12px;text-align:center;margin-bottom:3px;">URINALYSIS</div>
                    <table style="width:100%;border-collapse:collapse;font-size:10.5px;border:1px solid #333;">
                        <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                            <th style="text-align:left;padding:3px 4px;border-right:1px solid #333;">EXAMINATION</th>
                            <th style="text-align:right;padding:2px 3px;width:20%;border-right:1px solid #333;">RESULT</th>
                            <th style="text-align:left;padding:2px 3px;">REFERENCE RANGE</th>
                        </tr>
                        <tr v-for="row in [
                            {code:'UA_COLOR',name:'Urine Color',        range:'Yellow'},
                            {code:'UA_TRANS',name:'Transparency',       range:'Clear'},
                            {code:'UA_PH',   name:'pH',                 range:'4.5-8.0'},
                            {code:'UA_SG',   name:'Specific Gravity',   range:'1.010-1.025'},
                            {code:'UA_SUGAR',name:'Sugar',              range:'Negative'},
                            {code:'UA_PRO',  name:'Protein',            range:'Negative'},
                            {code:'UA_PUS',  name:'Pus Cells',          range:'0-5/hpf'},
                            {code:'UA_RBC',  name:'RBC',                range:'0-3/hpf'},
                            {code:'UA_EPI',  name:'Epithelial Cells',   range:'Few'},
                            {code:'UA_MUC',  name:'Mucus Threads',      range:'Few'},
                            {code:'UA_AMO',  name:'Amorphous Substance',range:'Few'},
                            {code:'UA_BAC',  name:'Bacteria',           range:'None'},
                            {code:'UA_CRY',  name:'Crystals',           range:'None'},
                            {code:'UA_OTH',  name:'Others',             range:''},
                        ]" :key="row.code" style="border-bottom:1px solid #333;">
                            <td style="padding:4px 3px;border-right:1px solid #ddd;">{{ row.name }}</td>
                            <td style="padding:3px 3px;text-align:right;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                            <td style="padding:4px 3px;color:#555;font-size:9.5px;">{{ row.range }}</td>
                        </tr>
                    </table>
                </div>
                <!-- Signatures below Urinalysis with space for physical signatures -->
                <div style="margin-top:8px;padding-top:6px;border-top:1px solid #aaa;font-size:10px;display:flex;justify-content:space-between;align-items:flex-end;">
                    <div style="text-align:center;">
                        <div style="font-size:9.5px;color:#555;margin-bottom:2px;">Examined By:</div>
                        <div style="height:28px;display:flex;align-items:flex-end;justify-content:center;overflow:visible;margin-bottom:1px;">
                            <img v-if="labRequest?.examined_by_signature"
                                :src="labRequest.examined_by_signature"
                                :style="{maxHeight:Math.round(24*(labRequest.examined_by_sig_scale||1))+'px',maxWidth:'110px'}"
                                style="object-fit:contain;display:block;"
                                crossorigin="anonymous"/>
                        </div>
                        <div style="border-top:1px solid #333;padding-top:2px;">
                            <div style="font-weight:700;font-size:10px;">{{ labRequest?.examined_by_name || '—' }}</div>
                            <div style="font-size:9px;">License no. {{ labRequest?.examined_by_license || '' }}</div>
                            <div style="font-size:9px;">Medical Technologist</div>
                        </div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:9.5px;color:#555;margin-bottom:2px;">Noted By:</div>
                        <div style="height:28px;display:flex;align-items:flex-end;justify-content:center;overflow:visible;margin-bottom:1px;">
                            <img v-if="labRequest?.noted_by_signature"
                                :src="labRequest.noted_by_signature"
                                :style="{maxHeight:Math.round(24*(labRequest.noted_by_sig_scale||1))+'px',maxWidth:'110px'}"
                                style="object-fit:contain;display:block;"
                                crossorigin="anonymous"/>
                        </div>
                        <div style="border-top:1px solid #333;padding-top:2px;">
                            <div style="font-weight:700;font-size:10px;">{{ labRequest?.noted_by_name || '—' }}</div>
                            <div style="font-size:9px;">License No.: {{ labRequest?.noted_by_license || '' }}</div>
                            <div style="font-size:9px;">Pathologist</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- DIAGNOSIS + CLASSIFICATION + PHYSICIAN SIG — expands to fill remaining space -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 10px;border-top:1.5px solid #111;padding-top:4px;flex:1;min-height:0;margin-bottom:3px;">
        <!-- Left: Diagnosis — expanded to fill height -->
        <div style="display:flex;flex-direction:column;">
            <div style="font-weight:900;font-size:12px;text-align:center;border-bottom:1.5px solid #111;padding-bottom:2px;margin-bottom:3px;">DIAGNOSIS/TREATMENT/PLAN</div>
            <div style="border:1.5px solid #444;padding:5px 7px;flex:1;overflow:hidden;">
                <div v-if="consultation?.essentially_normal" style="font-weight:900;font-size:12px;">***ESSENTIALLY NORMAL FINDINGS***</div>
                <div v-if="consultation?.pe_findings" style="font-size:12px;white-space:pre-line;line-height:1.5;">{{ consultation.pe_findings }}</div>
                <div v-if="consultation?.soap_assessment && !consultation?.essentially_normal" style="font-size:12px;white-space:pre-line;line-height:1.5;">{{ consultation.soap_assessment }}</div>
            </div>
        </div>
        <!-- Right: Classification + Examining Physician sig pushed to bottom, centered -->
        <div style="display:flex;flex-direction:column;">
            <div style="font-weight:900;font-size:12px;text-align:center;border-bottom:1.5px solid #111;padding-bottom:2px;margin-bottom:3px;">CLASSIFICATION</div>
            <div v-for="cls in ['A','B','C','D','E']" :key="cls"
                style="display:flex;align-items:flex-start;gap:5px;margin-bottom:5px;font-size:10.5px;line-height:1.4;">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" style="display:inline-block;vertical-align:middle;flex-shrink:0;margin-top:2px;">
                    <rect x="1" y="1" width="10" height="10" :fill="consultation?.pe_classification===cls?'#111':'none'" :stroke="consultation?.pe_classification===cls?'#111':'#555'" stroke-width="1.5"/>
                    <text v-if="consultation?.pe_classification===cls" x="6" y="9.5" text-anchor="middle" font-size="9" fill="white" font-weight="bold" font-family="Arial">✓</text>
                </svg>
                <span>
                    <strong style="font-size:11px;font-weight:900;">CLASS {{ cls }}</strong>
                    <span style="font-size:10.5px;"> {{ classDesc[cls] }}</span>
                </span>
            </div>
            <div v-if="consultation?.pe_classification" style="margin-top:4px;font-size:11px;display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                <span style="color:#555;">Recommendation:</span>
                <strong style="font-size:14px;letter-spacing:0.5px;">{{ classRec[consultation.pe_classification] }}</strong>
            </div>
            <div style="margin-top:2px;font-size:9.5px;color:#444;max-height:28px;overflow:hidden;">Remarks: {{ consultation?.pe_recommendation||'' }}</div>
            <!-- Examining Physician — right side, centered, pushed to bottom -->
            <div style="margin-top:auto;border-top:1.5px solid #111;padding-top:4px;display:flex;justify-content:center;">
                <div style="text-align:center;min-width:200px;max-width:260px;">
                    <div style="height:36px;display:flex;align-items:flex-end;justify-content:center;overflow:visible;margin-bottom:0;">
                        <img v-if="consultation?.doctor_signature"
                            :src="consultation.doctor_signature"
                            :style="{maxHeight:Math.round(32*(consultation.doctor_sig_scale||1))+'px',maxWidth:'200px'}"
                            style="object-fit:contain;display:block;"
                            alt="Signature" crossorigin="anonymous"/>
                    </div>
                    <div style="border-top:1.5px solid #333;padding-top:3px;">
                        <div style="font-size:11px;font-weight:700;">{{ (consultation?.doctor_name||'—')?.toUpperCase() }}, MD</div>
                        <div style="font-size:10px;">Examining Physician</div>
                        <div style="font-size:9.5px;color:#555;">License No.: {{ consultation?.doctor_prc||'' }} / PTR {{ consultation?.doctor_ptr||'' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- P2 FOOTER -->
    <div style="position:absolute;bottom:5mm;left:7mm;right:7mm;display:flex;justify-content:space-between;border-top:1px solid #333;padding-top:2px;font-size:8px;">
        <strong>SAINT PETER DIAGNOSTICS AND LABORATORY</strong>
        <span>{{ patient.full_name?.toUpperCase() }}</span>
        <span>Medical Examination Report 2/2</span>
    </div>
</div>

</div>
</template>

