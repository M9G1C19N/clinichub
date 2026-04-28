<script setup>
defineProps({ labRequest: Object })
</script>

<template>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;border-top:1.5px solid #aaa;padding-top:8px;margin-top:8px;">
        <!-- Examined By -->
        <div style="font-size:11px;">
            <div style="color:#555;margin-bottom:6px;">Examined By:</div>
            <div style="display:flex;align-items:flex-end;margin-bottom:3px;">
                <img v-if="labRequest?.examined_by_signature"
                    :src="labRequest.examined_by_signature"
                    :style="{ height: Math.round(42 * (labRequest.examined_by_sig_scale ?? 1)) + 'px', maxWidth: '160px', objectFit: 'contain' }"
                    alt="Signature"/>
            </div>
            <div style="border-top:1.5px solid #333;padding-top:3px;">
                <div style="font-weight:900;font-size:12px;">{{ labRequest?.examined_by_name ?? 'JAN RENZ N. PLACEROS, RMT' }}</div>
                <div style="font-size:10.5px;">License No.: {{ labRequest?.examined_by_license ?? '0136547' }}</div>
                <div style="font-size:10.5px;">Medical Technologist</div>
            </div>
        </div>
        <!-- Noted By -->
        <div style="font-size:11px;text-align:center;">
            <div style="color:#555;margin-bottom:6px;">Noted By:</div>
            <div style="display:flex;align-items:flex-end;justify-content:center;margin-bottom:3px;">
                <img v-if="labRequest?.noted_by_signature"
                    :src="labRequest.noted_by_signature"
                    :style="{ height: Math.round(42 * (labRequest.noted_by_sig_scale ?? 1)) + 'px', maxWidth: '160px', objectFit: 'contain' }"
                    alt="Signature"/>
            </div>
            <div style="border-top:1.5px solid #333;padding-top:3px;">
                <div style="font-weight:900;font-size:12px;">{{ labRequest?.noted_by_name ?? 'PONCIANO S. LIMCANGCO, MD, FPSP' }}</div>
                <div style="font-weight:700;font-size:10.5px;">LIC. NO. {{ labRequest?.noted_by_license ?? '0058646' }}</div>
                <div style="font-size:10.5px;">Pathologist</div>
            </div>
        </div>
    </div>
</template>
