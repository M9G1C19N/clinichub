<script setup>
import { CLINIC_LOGO } from '@/config/clinic.js'
defineProps({
    title:      String,
    patient:    Object,
    visit:      Object,
    labRequest: Object,
})
</script>

<template>
    <div>
        <!-- HEADER ROW -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <img :src="CLINIC_LOGO" style="width:56px;height:56px;object-fit:contain;flex-shrink:0;"/>
                <div>
                    <div style="font-weight:900;font-size:13px;line-height:1.2;">SAINT PETER DIAGNOSTICS AND LABORATORY</div>
                    <div style="font-size:10px;color:#333;">National Hi-way, Brgy. Ladgaron, Claver, Surigao del Norte</div>
                    <div style="font-size:10px;color:#333;">Tel. No. 0951-683-2212</div>
                </div>
            </div>
            <div style="font-size:22px;font-weight:900;letter-spacing:0.5px;text-align:right;line-height:1.15;max-width:200px;">
                {{ title }}
            </div>
        </div>

        <!-- PATIENT INFO -->
        <div style="border-top:2px solid #111;padding-top:5px;margin-bottom:6px;">
            <div style="display:flex;gap:20px;margin-bottom:4px;font-size:11px;">
                <div style="min-width:110px;">
                    <span style="color:#555;">ID</span>
                    <strong style="margin-left:6px;">{{ visit.case_number ?? visit.id }}</strong>
                </div>
                <div style="flex:1;">
                    <span style="color:#555;">Date</span>
                    <strong style="margin-left:6px;">
                        {{ labRequest?.result_date ?? visit.visit_date }}
                    </strong>
                </div>
                <div>
                    <span style="color:#555;">Time</span>
                    <strong style="margin-left:6px;">
                        {{ labRequest?.result_time ?? visit.visit_time ?? '' }}
                    </strong>
                </div>
            </div>
            <div style="display:flex;gap:20px;margin-bottom:4px;font-size:11px;">
                <div style="flex:1;">
                    <span style="color:#555;">Last/First Name</span>
                    <strong style="margin-left:6px;">
                        {{ patient.last_name }}, {{ patient.first_name }}
                        {{ patient.middle_name ? patient.middle_name.charAt(0) + '.' : '' }}
                    </strong>
                </div>
                <div>
                    <span style="color:#555;">Age/Sex</span>
                    <strong style="margin-left:6px;">{{ patient.age_sex }}</strong>
                </div>
            </div>
            <div style="font-size:11px;">
                <span style="color:#555;">Address/Company</span>
                <strong style="margin-left:6px;">{{ [patient.address, visit.employer_company].filter(Boolean).join(' / ') }}</strong>
            </div>
        </div>
    </div>
</template>
