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
    [{key:'tuberculosis',label:'1. Tuberculosis'},{key:'heart_disease',label:'4. Heart Disease/AMI'},{key:'kidney',label:'7. Kidney Disease'},{key:'fainting',label:'9. Fainting/Seizure'},{key:'malaria',label:'13. Malaria/Typhoid'}],
    [{key:'asthma',label:'2. Asthma'},{key:'cva_stroke',label:'5. CVA/Stroke'},{key:'liver',label:'8. Liver Disease'},{key:'headaches',label:'10. Headaches/Migraine'},{key:'hernia',label:'14. Hernia'}],
    [{key:'hypertension',label:'3. Hypertension'},{key:'diabetes',label:'6. Diabetes'},{key:'std',label:'12. Sexually Transmitted Disease'},{key:'mental',label:'11. Mental Disorder'},{key:'hemorrhoids',label:'15. Hemorrhoids'}],
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
<div style="width:210mm;min-height:297mm;height:297mm;padding:6mm 7mm 8mm;box-sizing:border-box;position:relative;overflow:hidden;display:flex;flex-direction:column;">

    <!-- HEADER -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
        <div style="display:flex;align-items:center;gap:6px;">
            <img :src="CLINIC_LOGO" style="width:44px;height:44px;object-fit:contain;flex-shrink:0;"/>
            <div>
                <div style="font-weight:900;font-size:12px;line-height:1.2;">SAINT PETER DIAGNOSTICS AND LABORATORY</div>
                <div style="font-size:8px;font-style:italic;">Medical and Dental Clinic</div>
                <div style="font-size:8px;">Surigao-Davao Coastal Road, Brgy. Ladgaron, Claver, Surigao del Norte 8410</div>
            </div>
        </div>
        <div style="text-align:right;font-size:8px;">
            <div>Contact No.: SMART: 09516832212</div>
            <div>E-mail: spdl.claver2007@gmail.com</div>
        </div>
    </div>

    <!-- TITLE -->
    <div style="border:2px solid #111;text-align:center;padding:4px 0;margin-bottom:5px;">
        <span style="font-size:14px;font-weight:900;letter-spacing:2px;">MEDICAL EXAMINATION REPORT</span>
    </div>

    <!-- PATIENT INFO -->
    <table style="width:100%;border-collapse:collapse;font-size:9px;margin-bottom:4px;">
        <tr>
            <td colspan="2" style="padding:2px 0;">
                <span style="color:#555;">Name: </span>
                <strong style="font-size:11px;">{{ patient.full_name?.toUpperCase() }}</strong>
            </td>
            <td style="padding:2px 0;text-align:right;">
                <span style="color:#555;">Control No.: </span><strong>{{ visit.case_number }}</strong>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding:2px 0;">
                <span style="color:#555;">Age/Sex: </span><strong>{{ patient.age_sex }}</strong>
                &nbsp;&nbsp;<span style="color:#555;">Birthdate: </span><strong>{{ patient.birthdate }}</strong>
                &nbsp;&nbsp;<span style="color:#555;">Civil Status: </span><strong style="text-transform:uppercase;">{{ patient.civil_status }}</strong>
            </td>
            <td style="padding:2px 0;text-align:right;">
                <span style="color:#555;">Exam Type: </span><strong>{{ visitTypeLabel[visit.visit_type] }}</strong>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding:2px 0;">
                <span style="color:#555;">Address: </span><strong style="text-transform:uppercase;">{{ patient.address }}</strong>
                &nbsp;&nbsp;<span style="color:#555;">Designation: </span><strong>{{ consultation?.position_applied || '—' }}</strong>
            </td>
            <td style="padding:2px 0;text-align:right;">
                <span style="color:#555;">Company: </span><strong>{{ visit.employer_company }}</strong>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding:2px 0;text-align:right;">
                <span style="color:#555;">Exam Date: </span><strong>{{ visit.visit_date }}</strong>
            </td>
        </tr>
    </table>

    <div style="border-top:1.5px solid #111;margin-bottom:4px;"></div>
    <div style="font-weight:900;font-size:10.5px;margin-bottom:3px;">I. MEDICAL HISTORY</div>

    <!-- A -->
    <div style="font-size:9px;margin-bottom:4px;padding:3px 4px;border:1px solid #e0e0e0;background:#fafafa;">
        <strong>A. PRESENT SYMPTOMS/COMPLAINTS:</strong>
        <span style="margin-left:3px;">{{ vitals?.present_symptoms || '—' }}</span>
    </div>

    <!-- B -->
    <div style="margin-bottom:4px;">
        <div style="font-weight:700;font-size:9px;margin-bottom:2px;border-bottom:1px solid #ccc;padding-bottom:1px;">B. PAST ILLNESSES/HOSPITALIZATIONS</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0 6px;">
            <div v-for="(col, ci) in illnessCols" :key="ci">
                <div v-for="ill in col" :key="ill.key"
                    style="display:flex;align-items:center;gap:3px;margin-bottom:3.5px;font-size:8.5px;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:10px;height:10px;border:1px solid #333;font-size:7px;flex-shrink:0;"
                        :style="hasIllness(ill.key)?'background:#000;color:#fff;':''">
                        {{ hasIllness(ill.key)?'✓':'' }}
                    </span>
                    {{ ill.label }}
                </div>
            </div>
        </div>
        <div style="font-size:8.5px;margin-top:2px;padding:2px 4px;border:1px solid #e0e0e0;background:#fafafa;"><strong>Remarks: </strong>{{ vitals?.past_illnesses_remarks || '' }}</div>
    </div>

    <!-- C D E -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 8px;margin-bottom:4px;font-size:8.5px;">
        <div style="padding:3px 4px;border:1px solid #e0e0e0;background:#fafafa;"><strong>C. FAMILY MEDICAL HISTORY</strong> {{ vitals?.family_history || '—' }}</div>
        <div style="padding:3px 4px;border:1px solid #e0e0e0;background:#fafafa;"><strong>D. ACCIDENTS/INJURIES</strong> {{ vitals?.accidents_injuries || 'UNREMARKABLE' }}</div>
        <div style="grid-column:1/-1;padding:3px 4px;border:1px solid #e0e0e0;background:#fafafa;"><strong>E. SURGICAL HISTORY</strong> {{ vitals?.surgical_history_detail || 'UNREMARKABLE' }}</div>
    </div>

    <!-- F -->
    <div style="margin-bottom:4px;font-size:8.5px;display:flex;align-items:center;gap:5px;flex-wrap:wrap;padding:3px 4px;border:1px solid #e0e0e0;background:#fafafa;">
        <strong>F. ALLERGIES</strong>
        <span v-for="opt in [{v:'none',l:'None'},{v:'food',l:'Food'},{v:'drug',l:'Drug'},{v:'others',l:'Others'}]" :key="opt.v"
            style="display:inline-flex;align-items:center;gap:2px;">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:10px;height:10px;border:1px solid #333;font-size:7px;"
                :style="vitals?.allergies_flags?.includes(opt.v)?'background:#000;color:#fff;':''">
                {{ vitals?.allergies_flags?.includes(opt.v)?'✓':'' }}
            </span>{{ opt.l }}
        </span>
    </div>

    <!-- G H -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 8px;margin-bottom:4px;font-size:8.5px;">
        <div style="padding:3px 4px;border:1px solid #e0e0e0;background:#fafafa;">
            <strong>G. MENSTRUAL HISTORY</strong><br/>
            Menstrual Cycle:
            <span v-for="opt in [{v:'regular',l:'Regular'},{v:'irregular',l:'Irregular'},{v:'menopause',l:'Menopause'},{v:'postmenopausal',l:'Postmenopausal'}]" :key="opt.v"
                style="display:inline-flex;align-items:center;gap:2px;margin-right:3px;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:10px;height:10px;border:1px solid #333;font-size:7px;"
                    :style="vitals?.menstrual_cycle===opt.v?'background:#000;color:#fff;':''">
                    {{ vitals?.menstrual_cycle===opt.v?'✓':'' }}
                </span>{{ opt.l }}
            </span>
            <br/>LMP and Duration: <strong>{{ vitals?.lmp || 'N/A' }}</strong>
        </div>
        <div style="padding:3px 4px;border:1px solid #e0e0e0;background:#fafafa;">
            <strong>H. OB HISTORY</strong><br/>
            <span style="display:inline-flex;align-items:center;gap:2px;margin-right:5px;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:10px;height:10px;border:1px solid #333;font-size:7px;"
                    :style="vitals?.ob_nulligravida?'background:#000;color:#fff;':''">
                    {{ vitals?.ob_nulligravida?'✓':'' }}
                </span>Nulligravida
            </span>
            Gravida: <strong>{{ vitals?.ob_gravida || 'N/A' }}</strong>
            &nbsp; Para: <strong>{{ vitals?.ob_para || 'N/A' }}</strong>
        </div>
    </div>

    <!-- I -->
    <div style="margin-bottom:5px;font-size:8.5px;padding:3px 4px;border:1px solid #e0e0e0;background:#fafafa;">
        <strong>I. PERSONAL/SOCIAL HISTORY</strong>
        <div style="display:flex;gap:16px;margin-top:2px;">
            <div style="display:flex;align-items:center;gap:4px;">
                <strong>TOBACCO USE:</strong>
                <span v-for="opt in [{v:'current',l:'Current'},{v:'former',l:'Former'},{v:'never',l:'Never'}]" :key="opt.v"
                    style="display:inline-flex;align-items:center;gap:2px;margin-right:3px;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:10px;height:10px;border:1px solid #333;font-size:7px;"
                        :style="vitals?.tobacco_use===opt.v?'background:#000;color:#fff;':''">
                        {{ vitals?.tobacco_use===opt.v?'✓':'' }}
                    </span>{{ opt.l }}
                </span>
            </div>
            <div style="display:flex;align-items:center;gap:4px;">
                <strong>ALCOHOL:</strong>
                <span v-for="opt in [{v:'current',l:'Current'},{v:'former',l:'Former'},{v:'never',l:'Never'}]" :key="opt.v"
                    style="display:inline-flex;align-items:center;gap:2px;margin-right:3px;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:10px;height:10px;border:1px solid #333;font-size:7px;"
                        :style="vitals?.alcohol_use===opt.v?'background:#000;color:#fff;':''">
                        {{ vitals?.alcohol_use===opt.v?'✓':'' }}
                    </span>{{ opt.l }}
                </span>
            </div>
        </div>
    </div>

    <!-- II -->
    <div style="border-top:1.5px solid #111;padding-top:3px;margin-bottom:4px;">
        <div style="font-weight:900;font-size:10.5px;">II. PHYSICAL EXAMINATION</div>
    </div>

    <!-- Main flex grow area -->
    <div style="display:grid;grid-template-columns:52% 48%;gap:0 6px;flex:1;">

        <!-- LEFT -->
        <div style="font-size:9px;">
            <div v-for="row in [
                {label:'A. Weight (kg.)',          val:vitals?.weight_kg},
                {label:'B. Height (m)',            val:vitals?.height_cm?(vitals.height_cm/100).toFixed(2):''},
                {label:'C. BMI (Body Mass Index)', val:vitals?.bmi},
                {label:'D. Pulse (beats/min)',     val:vitals?.pulse_rate},
            ]" :key="row.label"
                style="display:flex;justify-content:space-between;padding:5px 4px;border-bottom:1px solid #ddd;border-left:1px solid #ddd;border-right:1px solid #ddd;">
                <span>{{ row.label }}</span>
                <strong style="min-width:40px;text-align:right;">{{ row.val??'' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:5px 4px;border-bottom:1px solid #ddd;border-left:1px solid #ddd;border-right:1px solid #ddd;">
                <span>E. BP (Systolic/Diastolic)</span>
                <strong>{{ vitals?.blood_pressure_systolic||'' }}{{ vitals?.blood_pressure_systolic?' / ':'' }}{{ vitals?.blood_pressure_diastolic||'' }}{{ vitals?.blood_pressure_systolic?' mmHg':'' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;padding:5px 4px;border-bottom:1px solid #ddd;border-left:1px solid #ddd;border-right:1px solid #ddd;align-items:center;">
                <span>F. Conversational Hearing</span>
                <span style="display:flex;align-items:center;gap:6px;">
                    <span style="display:inline-flex;align-items:center;gap:2px;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:10px;height:10px;border:1px solid #333;font-size:7px;"
                            :style="vitals?.conversational_hearing==='Normal'?'background:#000;color:#fff;':''">
                            {{ vitals?.conversational_hearing==='Normal'?'✓':'' }}
                        </span> Normal
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:2px;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:10px;height:10px;border:1px solid #333;font-size:7px;"
                            :style="vitals?.conversational_hearing==='Defective'?'background:#000;color:#fff;':''">
                            {{ vitals?.conversational_hearing==='Defective'?'✓':'' }}
                        </span> Defective
                    </span>
                </span>
            </div>
            <!-- Visual Acuity -->
            <div style="border:1px solid #ddd;margin-top:0;">
                <div style="display:flex;justify-content:space-between;padding:4px 4px 2px;border-bottom:1px solid #eee;">
                    <span style="font-size:9px;font-weight:700;">G. Visual Acuity</span>
                    <span style="color:#555;font-size:8px;">Uncorrected &nbsp;&nbsp; Corrected</span>
                </div>
                <div style="padding:3px 4px 4px;line-height:1.9;font-size:9px;">
                    <div>1. Distant Vision &nbsp; OD <strong>{{ vitals?.visual_acuity_right||'' }}</strong></div>
                    <div style="padding-left:72px;">OS <strong>{{ vitals?.visual_acuity_left||'' }}</strong></div>
                    <div>2. Near Vision &nbsp;&nbsp;&nbsp;&nbsp; OD <strong>{{ vitals?.visual_acuity_near_right||'' }}</strong></div>
                    <div style="padding-left:72px;">OS <strong>{{ vitals?.visual_acuity_near_left||'' }}</strong></div>
                    <div>3. Color Vision &nbsp;&nbsp;&nbsp; <strong>{{ vitals?.color_vision_result||'' }}</strong></div>
                </div>
            </div>
        </div>

        <!-- RIGHT -->
        <div style="display:flex;flex-direction:column;">
            <!-- Photo + consent -->
            <div style="display:flex;gap:5px;margin-bottom:4px;">
                <div style="width:66px;height:82px;border:1px solid #999;flex-shrink:0;overflow:hidden;background:#f5f5f5;display:flex;align-items:center;justify-content:center;">
                    <img v-if="patient?.photo_path" :src="patient.photo_path"
                        style="width:100%;height:100%;object-fit:cover;display:block;"
                        crossorigin="anonymous"
                    />
                    <span v-else style="font-size:6px;color:#aaa;text-align:center;line-height:1.3;">2x2<br/>Photo</span>
                </div>
                <div style="flex:1;border:1px solid #999;padding:5px;font-size:6.5px;font-style:italic;color:#333;line-height:1.6;">
                    "I certify that all information I have given in my medical history is true and that any false statement will disqualify me from my employment application, benefits and claims. Furthermore, I authorize the medical examiner to release results of my medical examination to my employer/prospective employer."
                </div>
            </div>
            <!-- Signature -->
            <div style="text-align:center;margin-bottom:5px;">
                <div style="border-top:1px solid #333;display:inline-block;min-width:120px;padding-top:2px;font-weight:700;font-size:9px;">
                    {{ patient.full_name?.toUpperCase() }}
                </div>
                <div style="font-size:7.5px;color:#555;">Signature Over Printed Name</div>
                <div style="font-weight:700;font-size:8px;">NOT VALID WITHOUT SEAL</div>
            </div>
            <!-- PE Findings table — flex grow to fill remaining height -->
            <div style="font-size:8.5px;flex:1;border:1px solid #ddd;">
                <div style="display:grid;grid-template-columns:12px 1fr 1fr;border-bottom:1.5px solid #333;padding:3px 2px;font-weight:700;background:#f5f5f5;">
                    <div></div>
                    <div>Normal &nbsp; Examination</div>
                    <div>P.E. Findings</div>
                </div>
                <div v-for="sys in peSystems" :key="sys.key"
                    style="display:grid;grid-template-columns:12px 1fr 1fr;align-items:center;border-bottom:1px solid #eee;padding:3px 2px;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:10px;height:10px;border:1px solid #333;font-size:7px;"
                        :style="peNormal(sys.key)?'background:#000;color:#fff;':''">
                        {{ peNormal(sys.key)?'✓':'' }}
                    </span>
                    <span>{{ sys.label }}</span>
                    <span style="color:#c00;font-size:7.5px;">{{ peAbnormal(sys.key)?(vitals?.pe_findings_remarks||'Abnormal'):'' }}</span>
                </div>
                <div style="padding:4px 4px;font-size:8.5px;border-top:1px solid #eee;">
                    <strong>Other PE Findings:</strong> {{ vitals?.other_pe_findings || '' }}
                </div>
            </div>
        </div>
    </div>

    <!-- P1 FOOTER -->
    <div style="position:absolute;bottom:5mm;left:7mm;right:7mm;display:flex;justify-content:space-between;border-top:1px solid #333;padding-top:2px;font-size:8px;">
        <strong>SAINT PETER DIAGNOSTICS AND LABORATORY</strong>
        <span>{{ patient.full_name?.toUpperCase() }}</span>
        <span>Medical Examination Report 1/2</span>
    </div>
</div>

<!-- ═══════════ PAGE 2 — LAB RESULTS ═══════════ -->
<div style="width:210mm;height:297mm;padding:6mm 7mm 8mm;box-sizing:border-box;position:relative;overflow:hidden;">

    <!-- HEADER -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:3px;">
        <div style="display:flex;align-items:center;gap:6px;">
            <img :src="CLINIC_LOGO" style="width:40px;height:40px;object-fit:contain;flex-shrink:0;"/>
            <div>
                <div style="font-weight:900;font-size:12px;line-height:1.2;">SAINT PETER DIAGNOSTICS AND LABORATORY</div>
                <div style="font-size:8px;font-style:italic;">Medical and Dental Clinic</div>
                <div style="font-size:8px;">Surigao-Davao Coastal Road, Brgy. Ladgaron, Claver, Surigao del Norte 8410</div>
            </div>
        </div>
        <div style="text-align:right;font-size:8px;">
            <div>Contact No.: SMART: 09516832212</div>
            <div>E-mail: spdl.claver2007@gmail.com</div>
        </div>
    </div>

    <!-- Patient bar -->
    <div style="border-top:1.5px solid #111;border-bottom:1px solid #aaa;padding:2px 0;margin-bottom:4px;font-size:9px;">
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
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 14px;margin-bottom:5px; ">
            <!-- CBC -->
            <div>
                <div style="font-weight:900;font-size:10px;text-align:center;margin-bottom:2px;">COMPLETE BLOOD COUNT</div>
                <table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:1px 2px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:1px 2px;width:16%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:1px 2px;">REFERENCE RANGE</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'HGB',name:'Hemoglobin',    range:'M=135-175/F=115-155 g/L'},
                        {code:'HCT',name:'Hematocrit',    range:'M=40-52/F=35-48%'},
                        {code:'RBC',name:'RBC Count',     range:'M=4.5-5.2/F=3.9-5.6 x10 g/L'},
                        {code:'PLT',name:'Platelet Count',range:'150-400 x10 g/L'},
                        {code:'WBC',name:'WBC Count',     range:'4.0-11.0 x10 g/L'},
                        {code:'SEG',name:'Segmenters',    range:'50-70'},
                        {code:'LYM',name:'Lymphocytes',   range:'26-46%'},
                        {code:'MID',name:'Mid',           range:'2-10%'},
                    ]" :key="row.code" style="border-bottom:1px solid #eee;">
                        <td style="padding:3px 2px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:3px 2px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:3px 2px;color:#555;font-size:7.5px;">{{ row.range }}</td>
                    </tr>
                </table>
            </div>
            <!-- BLOOD CHEMISTRY -->
            <div>
                <div style="font-weight:900;font-size:10px;text-align:center;margin-bottom:2px;">BLOOD CHEMISTRY</div>
                <table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:1px 2px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:1px 2px;width:16%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:1px 2px;">REFERENCE RANGE</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'FBS',    name:'Fasting Blood Sugar', range:'75-115 mg/dL'},
                        {code:'BUN',    name:'Blood Urea Nitrogen', range:'<33.1 mg/dL'},
                        {code:'CREAT',  name:'Creatinine',          range:'M=0.6-1.4/F=0.6-1.2mg/dl'},
                        {code:'UA_CHEM',name:'Uric Acid',           range:'M=3.5-7.5/F=2.6-6.0mg/dl'},
                        {code:'SGPT',   name:'SGPT',                range:'M=<41/f=<31 U/L'},
                        {code:'SGOT',   name:'SGOT',                range:'M=<37/f=<31 U/L'},
                        {code:'CHOL',   name:'Total Cholesterol',   range:'<200 mg/dL'},
                        {code:'TRIG',   name:'Triglycerides',       range:'<200 mg/dL'},
                        {code:'HDL',    name:'HDL',                 range:'>35 mg/dl'},
                        {code:'LDL',    name:'LDL',                 range:'<110 mg/dl'},
                        {code:'VLDL',   name:'VLDL',                range:'<5-40 mg/dL'},
                    ]" :key="row.code" style="border-bottom:1px solid #eee;">
                        <td style="padding:2.5px 2px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:2.5px 2px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:2.5px 2px;color:#555;font-size:7.5px;">{{ row.range }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- UA + STOOL + OTHERS -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 14px;margin-bottom:5px; margin-top: 10px; border-top:1.5px solid #111;">
            <div>
                <div style="font-weight:900;font-size:10px;text-align:center;margin-bottom:2px;">URINALYSIS</div>
                <table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:1px 2px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:1px 2px;width:20%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:1px 2px;">REFERENCE RANGE</th>
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
                        {code:'UA_AMO',  name:'Amorphous Substance',range:'Few'},
                        {code:'UA_BAC',  name:'Bacteria',          range:'None'},
                        {code:'UA_CRY',  name:'Crystals',          range:'None'},
                        {code:'UA_OTH',  name:'Others',            range:''},
                    ]" :key="row.code" style="border-bottom:1px solid #eee;">
                        <td style="padding:2.5px 2px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:2.5px 2px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:2.5px 2px;color:#555;font-size:7.5px;">{{ row.range }}</td>
                    </tr>
                </table>
            </div>
            <div>
                <div style="font-weight:900;font-size:10px;text-align:center;margin-bottom:2px;">STOOL EXAM</div>
                <table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #333;margin-bottom:6px;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:1px 2px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:1px 2px;width:28%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:1px 2px;">REFERENCE RANGE</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'ST_COL',name:'Color',          range:'Brown'},
                        {code:'ST_CON',name:'Consistency',    range:'Formed'},
                        {code:'ST_PUS',name:'Pus Cells',      range:'0-2/hpf'},
                        {code:'ST_RBC',name:'Red Blood Cells',range:'None'},
                        {code:'ST_OVA',name:'Ova/Parasites',  range:'None seen'},
                    ]" :key="row.code" style="border-bottom:1px solid #eee;">
                        <td style="padding:3px 2px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:3px 2px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:3px 2px;color:#555;font-size:7.5px;">{{ row.range }}</td>
                    </tr>
                </table>

                <div style="font-weight:900;font-size:10px;text-align:center;margin-bottom:2px;">SEROLOGY</div>
                <table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:1px 2px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:1px 2px;">RESULT</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'HBSAG',name:'Hepatitis B sAg'},
                        {code:'VDRL', name:'VDRL'},
                        {code:'PREG', name:'Pregnancy Test'},
                        {code:'BTYPE',name:'Blood Type'},
                        {code:'PSA',  name:'Prostatic Specific Antigen (PSA)'},
                    ]" :key="row.code" style="border-bottom:1px solid #eee;">
                        <td style="padding:2.5px 2px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:2.5px 2px;" :style="rs(row.code)">{{ r(row.code) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Lab sigs PE -->
        <div style="display:flex;justify-content:space-between;font-size:9px;border-top:1px solid #aaa;padding-top:4px;margin-bottom:10px;">
            <div style="text-align:center;">
                <div style="color:#555;margin-bottom:4px;">Examined By:</div>
                <img v-if="labRequest?.examined_by_signature"
                    :src="labRequest.examined_by_signature"
                    style="max-height:32px;max-width:120px;object-fit:contain;display:block;margin:0 auto 2px;"
                    crossorigin="anonymous"/>
                <div v-else style="min-height:32px;"></div>
                <div style="border-top:1px solid #333;padding-top:2px;">
                    <div style="font-weight:700;">{{ labRequest?.examined_by_name||'VAN VINCE G. DIVES, RMT' }}</div>
                    <div>License no. {{ labRequest?.examined_by_license||'0128116' }}</div>
                    <div>Medical Technologist</div>
                </div>
            </div>
            <div style="text-align:center;">
                <div style="color:#555;margin-bottom:4px;">Noted By:</div>
                <img v-if="labRequest?.noted_by_signature"
                    :src="labRequest.noted_by_signature"
                    style="max-height:32px;max-width:120px;object-fit:contain;display:block;margin:0 auto 2px;"
                    crossorigin="anonymous"/>
                <div v-else style="min-height:32px;"></div>
                <div style="border-top:1px solid #333;padding-top:2px;">
                    <div style="font-weight:700;">{{ labRequest?.noted_by_name||'PONCIANO S. LIMCANGCO, MD, FPSP' }}</div>
                    <div>License No.: {{ labRequest?.noted_by_license||'0058646' }}</div>
                    <div>Pathologist</div>
                </div>
            </div>
        </div>

        <!-- CHEST X-RAY + ECG (data from radiology module + consultation ECG fields) -->
        <div style="border-top:1.5px solid #111;padding-top:4px;margin-bottom:5px;">
            <div style="font-weight:900;font-size:10px;margin-bottom:4px;">CHEST XRAY + ECG</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 14px;">

                <!-- Chest X-Ray — data from ImagingRequest (radiology module) -->
                <div>
                    <div style="font-weight:900;font-size:9px;margin-bottom:2px;">
                        CHEST XRAY RESULT
                        <span style="font-size:8px;font-weight:400;margin-left:3px;">({{ imaging?.is_provisional ? 'Provisional Reading' : 'Official Reading' }})</span>
                        <span v-if="imaging?.request_number" style="font-weight:400;margin-left:4px;font-size:8px;">
                            Req. No: <strong>{{ imaging.request_number }}</strong>
                        </span>
                    </div>
                    <template v-if="imaging">
                        <div style="font-size:8.5px;margin-bottom:3px;">
                            <span style="color:#555;">Impression: </span>
                            <strong>{{ imaging.impression || '—' }}</strong>
                        </div>
                        <div style="font-size:8.5px;min-height:30px;white-space:pre-line;border:1px solid #e0e0e0;padding:3px 4px;background:#fafafa;">{{ imaging.radiographic_findings || '' }}</div>
                        <div style="font-size:7.5px;color:#444;margin-top:3px;font-style:italic;">
                            NOTE: X-ray films submitted for official reading will be available one week after submission.
                        </div>
                        <!-- CXR Noted By with e-signature -->
                        <div style="margin-top:8px;font-size:8.5px;display:flex;align-items:flex-end;gap:6px;">
                            <span style="color:#555;white-space:nowrap;">Noted By:</span>
                            <div style="text-align:center;flex:1;">
                                <img v-if="imaging.radiologist_signature"
                                    :src="imaging.radiologist_signature"
                                    style="max-height:26px;max-width:100px;object-fit:contain;display:block;margin:0 auto 2px;"
                                    crossorigin="anonymous"/>
                                <div style="font-weight:700;">{{ imaging.radiologist_name || '' }}</div>
                                <div style="border-top:1px solid #333;padding-top:1px;font-size:7.5px;color:#555;">
                                    {{ imaging.radiologist_license ? 'Lic. No.: ' + imaging.radiologist_license : 'Radiologist' }}
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div style="font-size:8px;color:#aaa;font-style:italic;padding:8px 0;">No X-Ray on file for this visit.</div>
                        <div style="margin-top:30px;text-align:center;">
                            <div style="border-top:1px solid #333;display:inline-block;min-width:140px;padding-top:2px;font-size:7.5px;color:#555;">Radiologist / Rad Tech</div>
                        </div>
                    </template>
                </div>

                <!-- ECG — data stored in consultation (no separate module) -->
                <div>
                    <div style="font-weight:900;font-size:9px;margin-bottom:2px;">ELECTROCARDIOGRAPHY (ECG)</div>
                    <template v-if="consultation?.ecg_impression || consultation?.ecg_findings">
                        <div style="font-size:8.5px;margin-bottom:3px;">
                            <span style="color:#555;">Impression: </span>
                            <strong>{{ consultation.ecg_impression || '—' }}</strong>
                        </div>
                        <div style="font-size:8.5px;min-height:30px;white-space:pre-line;border:1px solid #e0e0e0;padding:3px 4px;background:#fafafa;">{{ consultation.ecg_findings || '' }}</div>
                        <div style="font-size:7.5px;color:#444;margin-top:3px;font-style:italic;">
                            NOTE: ECG is required for persons with hypertension, heart disease, and related illnesses.
                        </div>
                        <div style="margin-top:8px;font-size:8.5px;display:flex;align-items:flex-end;gap:6px;">
                            <span style="color:#555;">Noted By:</span>
                            <div style="text-align:center;flex:1;">
                                <img v-if="consultation.ecg_noted_by_signature"
                                    :src="consultation.ecg_noted_by_signature"
                                    style="max-height:26px;max-width:100px;object-fit:contain;display:block;margin:0 auto 2px;"
                                    crossorigin="anonymous"/>
                                <div style="font-weight:700;">{{ consultation.ecg_noted_by_name || '' }}</div>
                                <div style="border-top:1px solid #333;padding-top:1px;font-size:7.5px;color:#555;">
                                    {{ consultation.ecg_noted_by_license ? 'Lic. No.: ' + consultation.ecg_noted_by_license : '' }}
                                </div>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <div style="font-size:8px;color:#aaa;font-style:italic;padding:8px 0;">No ECG data on file.</div>
                        <div style="font-size:7.5px;color:#444;margin-top:3px;font-style:italic;">
                            NOTE: ECG is required for persons with hypertension, heart disease, and related illnesses.
                        </div>
                        <div style="margin-top:30px;border-top:1px solid #333;text-align:center;padding-top:2px;font-size:7.5px;color:#555;">Noted By</div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <!-- ── ANNUAL PE / OPD ── -->
    <template v-else>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 14px;margin-bottom:5px;">
            <div>
                <div style="font-weight:900;font-size:10px;text-align:center;margin-bottom:2px;">COMPLETE BLOOD COUNT</div>
                <table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:1px 2px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:1px 2px;width:16%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:1px 2px;">REFERENCE RANGE</th>
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
                    ]" :key="row.code" style="border-bottom:1px solid #eee;">
                        <td style="padding:3px 2px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:3px 2px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:3px 2px;color:#555;font-size:7.5px;">{{ row.range }}</td>
                    </tr>
                </table>
            </div>
            <div>
                <div style="font-weight:900;font-size:10px;text-align:center;margin-bottom:2px;">URINALYSIS</div>
                <table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:1px 2px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:right;padding:1px 2px;width:20%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:1px 2px;">REFERENCE RANGE</th>
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
                    ]" :key="row.code" style="border-bottom:1px solid #eee;">
                        <td style="padding:2.5px 2px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:2.5px 2px;text-align:right;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:2.5px 2px;color:#555;font-size:7.5px;">{{ row.range }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <!-- STOOL + Lab sigs -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 14px;margin-bottom:5px;">
            <div>
                <div style="font-weight:900;font-size:10px;text-align:center;margin-bottom:2px;">STOOL EXAM</div>
                <table style="width:100%;border-collapse:collapse;font-size:8.5px;border:1px solid #333;">
                    <tr style="border-bottom:1px solid #333;background:#f5f5f5;">
                        <th style="text-align:left;padding:1px 2px;border-right:1px solid #333;">EXAMINATION</th>
                        <th style="text-align:left;padding:1px 2px;width:28%;border-right:1px solid #333;">RESULT</th>
                        <th style="text-align:left;padding:1px 2px;">REFERENCE RANGE</th>
                    </tr>
                    <tr v-for="row in [
                        {code:'ST_COL',name:'Color',          range:'Brown'},
                        {code:'ST_CON',name:'Consistency',    range:'Formed'},
                        {code:'ST_PUS',name:'Pus Cells',      range:'0-2/hpf'},
                        {code:'ST_RBC',name:'Red Blood Cells',range:'None'},
                        {code:'ST_OVA',name:'Ova/Parasites',  range:'None seen'},
                    ]" :key="row.code" style="border-bottom:1px solid #eee;">
                        <td style="padding:3px 2px;border-right:1px solid #ddd;">{{ row.name }}</td>
                        <td style="padding:3px 2px;border-right:1px solid #ddd;" :style="rs(row.code)">{{ r(row.code) }}</td>
                        <td style="padding:3px 2px;color:#555;font-size:7.5px;">{{ row.range }}</td>
                    </tr>
                </table>
            </div>
            <div style="font-size:9px;display:flex;justify-content:space-between;align-items:flex-end;">
                <div style="text-align:center;">
                    <div style="color:#555;margin-bottom:16px;">Examined By:</div>
                    <div style="border-top:1px solid #333;padding-top:2px;">
                        <div style="font-weight:700;">{{ labRequest?.examined_by_name || 'VAN VINCE G. DIVES, RMT' }}</div>
                        <div>License no. {{ labRequest?.examined_by_license || '0128116' }}</div>
                        <div>Medical Technologist</div>
                    </div>
                </div>
                <div style="text-align:center;">
                    <div style="margin-bottom:2px;">Noted By:</div>
                    <div style="min-height:16px;"></div>
                    <div style="border-top:1px solid #333;padding-top:2px;">
                        <div style="font-weight:700;">{{ labRequest?.noted_by_name || 'PONCIANO S. LIMCANGCO, MD, FPSP' }}</div>
                        <div>License No.: {{ labRequest?.noted_by_license || '0058646' }}</div>
                        <div>Pathologist</div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- DIAGNOSIS + CLASSIFICATION -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 10px;border-top:1.5px solid #111;padding-top:4px;margin-bottom:5px;">
        <div>
            <div style="font-weight:900;font-size:9px;text-align:center;border-bottom:1.5px solid #111;padding-bottom:2px;margin-bottom:3px;">DIAGNOSIS/TREATMENT/PLAN</div>
            <div v-if="consultation?.essentially_normal" style="font-weight:900;font-size:9px;">***ESSENTIALLY NORMAL FINDINGS***</div>
            <div v-if="consultation?.pe_findings" style="font-size:9px;white-space:pre-line;margin-top:2px;">{{ consultation.pe_findings }}</div>
            <div v-if="consultation?.soap_assessment && !consultation?.essentially_normal" style="font-size:9px;white-space:pre-line;margin-top:2px;">{{ consultation.soap_assessment }}</div>
            <!-- Examining Physician signature — right side, below Remarks -->
            <div style="margin-top:8px;text-align:center;">
                <div style="min-height:38px;display:flex;align-items:flex-end;justify-content:start;margin-bottom:2px;">
                    <img v-if="consultation?.doctor_signature"
                        :src="consultation.doctor_signature"
                        style="max-height:36px;max-width:150px;object-fit:contain;"
                        alt="Signature"/>
                </div>
                <div style="border-top:1px solid #333;display:inline-block;min-width:190px;padding-top:2px;">
                    <div style="font-size:9px;">
                        Examining Physician: <strong style="font-size:10px;">&nbsp;{{ (consultation?.doctor_name||'ROLAND E. MIRA')?.toUpperCase() }}, MD</strong>
                    </div>
                    <div style="font-size:8px;color:#555;">License No.: {{ consultation?.doctor_prc||'0089780' }}/PTR {{ consultation?.doctor_ptr||'1474446' }}</div>
                </div>
            </div>
        </div>
        <div>
            <div style="font-weight:900;font-size:9px;text-align:center;border-bottom:1.5px solid #111;padding-bottom:2px;margin-bottom:3px;">CLASSIFICATION</div>
            <div v-for="cls in ['A','B','C','D','E']" :key="cls"
                style="display:flex;align-items:flex-start;gap:3px;margin-bottom:3.5px;font-size:8.5px;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:11px;height:11px;border:1.5px solid #333;font-size:8px;flex-shrink:0;margin-top:0.3px;"
                    :style="consultation?.pe_classification===cls?'background:#000;color:#fff;':''">
                    {{ consultation?.pe_classification===cls?'✓':'' }}
                </span>
                <span :style="consultation?.pe_classification===cls?'font-weight:700;':''">
                    <strong>CLASS {{ cls }}</strong> {{ classDesc[cls] }}
                </span>
            </div>
            <div v-if="consultation?.pe_classification" style="margin-top:4px;font-size:9px;display:flex;align-items:center;gap:5px;">
                <span style="color:#555;">Recommendation</span>
                <strong style="font-size:13px;letter-spacing:0.5px;">{{ classRec[consultation.pe_classification] }}</strong>
            </div>
            <div style="margin-top:2px;font-size:8.5px;color:#555;">Remarks: {{ consultation?.pe_recommendation||'' }}</div>

            <!-- Examining Physician signature — right side, below Remarks
            <div style="margin-top:8px;text-align:center;">
                <div style="min-height:38px;display:flex;align-items:flex-end;justify-content:center;margin-bottom:2px;">
                    <img v-if="consultation?.doctor_signature"
                        :src="consultation.doctor_signature"
                        style="max-height:36px;max-width:150px;object-fit:contain;"
                        alt="Signature"/>
                </div>
                <div style="border-top:1px solid #333;display:inline-block;min-width:190px;padding-top:2px;">
                    <div style="font-size:9px;">
                        Examining Physician: <strong style="font-size:10px;">&nbsp;{{ (consultation?.doctor_name||'ROLAND E. MIRA')?.toUpperCase() }}, MD</strong>
                    </div>
                    <div style="font-size:8px;color:#555;">License No.: {{ consultation?.doctor_prc||'0089780' }}/PTR {{ consultation?.doctor_ptr||'1474446' }}</div>
                </div>
            </div> -->
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
