<script setup>
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import { IS_PE_TYPE } from '@/config/visitTypes.js'

defineProps({
    visit:               Object,
    patient:             Object,
    imagingRequest:      Object,
    requestingPhysician: String,
})
</script>

<template>
    <div style="
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #111;
        background: white;
        padding: 20px 24px;
        max-width: 800px;
        margin: 0 auto;
    ">
        <!-- ── HEADER ─────────────────────────────── -->
        <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:16px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <img :src="CLINIC_LOGO" alt="SPDL Logo"
                    style="width:64px; height:64px; object-fit:contain;"/>
                <div>
                    <div style="font-weight:900; font-size:13px; color:#0F2044;">
                        {{ CLINIC_INFO.name.toUpperCase() }}
                    </div>
                    <div style="font-size:10px; color:#1B4F9B; font-weight:700; font-style:italic;">
                        {{ CLINIC_INFO.subtitle }}
                    </div>
                    <div style="font-size:9.5px; color:#444; margin-top:2px;">
                        National Highway, P-7, Brgy. Ladgaron, Claver, Surigao del Norte
                    </div>
                </div>
            </div>
            <div style="text-align:right; font-size:10px; color:#555;">
                <div style="font-weight:700;">{{ CLINIC_INFO.phoneSmart }} SMART</div>
            </div>
        </div>

        <div style="border-top:2px solid #0F2044; border-bottom:1px solid #ccc; padding:0; margin-bottom:16px;"/>

        <!-- ── PATIENT INFO ───────────────────────── -->
        <div style="display:grid; grid-template-columns:120px 1fr 120px 1fr; gap:6px 12px; margin-bottom:16px; font-size:10.5px;">
            <div style="color:#555; padding-top:3px;">Case No. :</div>
            <div style="border-bottom:1px solid #ccc; padding-bottom:2px; font-weight:700;">
                {{ visit.case_number ?? '—' }}
            </div>
            <div style="color:#555; padding-top:3px;">Chief Complaint :</div>
            <div style="border-bottom:1px solid #ccc; padding-bottom:2px; font-weight:700;">
                {{ IS_PE_TYPE(visit.visit_type) ? visit.visit_type.replace('_',' ').toUpperCase() : 'OPD CONSULTATION' }}
            </div>

            <div style="color:#555; padding-top:3px;">Date :</div>
            <div style="border-bottom:1px solid #ccc; padding-bottom:2px; font-weight:700;">
                {{ visit.visit_date }}
            </div>
            <div></div>
            <div></div>

            <div style="color:#555; padding-top:3px;">Last Name :</div>
            <div style="border-bottom:1px solid #ccc; padding-bottom:2px; font-weight:900; font-size:13px; letter-spacing:0.5px;">
                {{ patient.last_name?.toUpperCase() }}
            </div>
            <div></div>
            <div></div>

            <div style="color:#555; padding-top:3px;">First Name :</div>
            <div style="border-bottom:1px solid #ccc; padding-bottom:2px; font-weight:900; font-size:13px; letter-spacing:0.5px;">
                {{ patient.first_name?.toUpperCase() }}
                {{ patient.middle_name ? patient.middle_name.charAt(0).toUpperCase() + '.' : '' }}
            </div>
            <div style="color:#555; padding-top:3px;">Examination :</div>
            <div style="border-bottom:1px solid #ccc; padding-bottom:2px; font-weight:900; font-size:12px; letter-spacing:0.5px;">
                {{ imagingRequest?.imaging_type_label?.toUpperCase() ?? '—' }}
            </div>

            <div style="color:#555; padding-top:3px;">Age/Sex :</div>
            <div style="border-bottom:1px solid #ccc; padding-bottom:2px; font-weight:700;">
                {{ patient.age_sex }}
            </div>
            <div></div>
            <div></div>

            <div style="color:#555; padding-top:3px;">Address/<br/>Company :</div>
            <div style="border-bottom:1px solid #ccc; padding-bottom:2px; font-weight:700;">
                {{ [patient.address, visit.employer_company].filter(Boolean).join(' / ') }}
            </div>
            <div style="color:#555; padding-top:3px;">Req. Physician :</div>
            <div style="border-bottom:1px solid #ccc; padding-bottom:2px; font-weight:700;">
                {{ requestingPhysician }}
            </div>
        </div>

        <!-- ── REPORT TITLE ───────────────────────── -->
        <div style="text-align:center; font-weight:900; font-size:18px; letter-spacing:2px; color:#0F2044; margin: 16px 0 20px 0; border:2px solid #0F2044; padding:8px;">
            RADIOLOGICAL REPORT
        </div>

        <!-- ── FINDINGS ───────────────────────────── -->
        <div style="display:flex; flex-direction:column;">

            <div style="display:flex; gap:16px; min-height:250px; margin-bottom:8px;">
                <div style="width:130px; flex-shrink:0; font-weight:700; color:#333; font-size:11.5px; padding-top:2px;">
                    Radiographic<br/>Findings:
                </div>
                <div style="flex:1; line-height:2; font-size:11.5px;">
                    <div v-if="imagingRequest?.is_provisional"
                        style="font-weight:700; font-style:italic;">
                        **SEE ATTACHED CXR OFFICIAL READING**
                    </div>
                    <div v-else style="white-space:pre-line;">
                        {{ imagingRequest?.radiographic_findings ?? '' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ── IMPRESSION ─────────────────────────── -->
        <div style="margin-bottom:50px; border-top:1px solid #ccc; padding-top:12px; min-height:220px;">
            <div style="display:flex; gap:16px;">
                <div style="width:140px; flex-shrink:0; font-weight:900; font-size:12px; color:#111;">
                    IMPRESSION:
                </div>
                <div style="flex:1; font-weight:900; font-size:13px; letter-spacing:0.3px; color:#0F2044; text-transform:uppercase;">
                    {{ imagingRequest?.impression ?? '—' }}
                </div>
            </div>
        </div>

        <!-- ── SIGNATURES ─────────────────────────── -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:40px; margin-bottom:20px;">

            <!-- Rad Tech -->
            <div style="text-align:center;">
                <div style="min-height:50px; display:flex; align-items:flex-end; justify-content:center; margin-bottom:4px;">
                    <img v-if="imagingRequest?.rad_tech_signature"
                        :src="imagingRequest.rad_tech_signature"
                        style="max-height:48px; max-width:160px; object-fit:contain;"
                        alt="Signature"/>
                </div>
                <div style="border-top:1px solid #333; padding-top:6px;">
                    <div style="font-weight:900; font-size:11px; text-transform:uppercase; letter-spacing:0.3px;">
                        {{ imagingRequest?.rad_tech_name ?? '' }}
                    </div>
                    <div style="font-size:10px; color:#555; margin-top:2px;">
                        LIC. NO. {{ imagingRequest?.rad_tech_license ?? '' }}
                    </div>
                    <div style="font-size:10px; font-weight:600; margin-top:1px;">
                        Radiologic Technologist
                    </div>
                </div>
            </div>

            <!-- Radiologist -->
            <div style="text-align:center;">
                <div style="min-height:50px; display:flex; align-items:flex-end; justify-content:center; margin-bottom:4px;">
                    <img v-if="imagingRequest?.radiologist_signature"
                        :src="imagingRequest.radiologist_signature"
                        style="max-height:48px; max-width:160px; object-fit:contain;"
                        alt="Signature"/>
                </div>
                <div style="border-top:1px solid #333; padding-top:6px;">
                    <div style="font-weight:900; font-size:11px; text-transform:uppercase; letter-spacing:0.3px; text-decoration:underline;">
                        {{ imagingRequest?.radiologist_name ?? '' }}
                    </div>
                    <div style="font-size:10px; font-weight:600; margin-top:1px;">
                        Radiologist
                    </div>
                    <div style="font-size:10px; color:#555; margin-top:1px;">
                        License No. {{ imagingRequest?.radiologist_license ?? '' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ── FOOTER NOTE ────────────────────────── -->
        <div style="border-top:1px solid #0F2044; padding-top:8px; font-size:9px; color:#555; display:flex; justify-content:space-between; align-items:center;">
            <div style="flex:1;">
                NOTE: This report is based only in radiographic examination and should be correlated with clinical and laboratory findings.
                Radiographic image processed digitally. This report is electronically signed.
            </div>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:4px; font-size:9px; font-weight:700;">
            <span style="color:#8B5CF6;">RADIOGRAPHIC REPORT</span>
            <span style="color:#0F2044;">ST. PETER DIAGNOSTICS AND LABORATORY</span>
        </div>
    </div>
</template>
