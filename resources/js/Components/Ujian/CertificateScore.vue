<script setup>
import { computed } from 'vue';

const props = defineProps({
    certificateNo: { type: String, required: true },
    issueDate: { type: String, required: true },
    testTakerName: { type: String, required: true },
    studentId: { type: String, required: true },
    listeningScore: { type: Number, required: true },
    readingScore: { type: Number, required: true },
    totalScore: { type: Number, default: null },
    cefrLevel: { type: String, required: true },
    cefrTitle: { type: String, required: true },
    cefrDescription: { type: String, required: true },
    testDate: { type: String, required: true },
    validUntil: { type: String, required: true },
    headName: { type: String, required: true },
    headNis: { type: String, required: true },
    logoSrc: { type: String, default: '/assets/certificate/upy-seal.png' },
    stampSrc: { type: String, default: '/assets/certificate/stamp-placeholder.svg' },
    signatureSrc: { type: String, default: '/assets/certificate/signature-placeholder.svg' },
});

const resolvedTotalScore = computed(() => props.totalScore ?? props.listeningScore + props.readingScore);
</script>

<template>
    <article class="certificate-frame" aria-label="English Proficiency Test official score certificate">
        <div class="certificate">
            <div class="cert-topbar" aria-hidden="true"></div>

            <header class="cert-header">
                <div class="cert-brand">
                    <img
                        class="cert-logo"
                        :src="logoSrc"
                        alt="Universitas PGRI Yogyakarta seal"
                    />
                    <span class="cert-brand-rule" aria-hidden="true"></span>
                    <div class="cert-brand-text">
                        <p class="cert-brand-name">UPY Language Center</p>
                        <p class="cert-brand-university">Universitas PGRI Yogyakarta</p>
                    </div>
                </div>

                <dl class="cert-meta">
                    <dt class="cert-label">Certificate No.</dt>
                    <dd class="cert-value" :title="certificateNo">{{ certificateNo }}</dd>
                </dl>
            </header>

            <div class="cert-header-line" aria-hidden="true"></div>

            <div class="cert-title-row">
                <div class="cert-title-block">
                    <h1 class="cert-title">English Proficiency Test</h1>
                    <p class="cert-subtitle">Official Score Certificate</p>
                </div>

                <dl class="cert-issue">
                    <dt class="cert-label">Issue Date</dt>
                    <dd class="cert-value">{{ issueDate }}</dd>
                </dl>
            </div>

            <section class="cert-taker" aria-label="Test taker">
                <p class="cert-eyebrow">TEST TAKER</p>
                <h2 class="cert-taker-name" :title="testTakerName">{{ testTakerName }}</h2>
                <p class="cert-student-id">Student ID: <strong>{{ studentId }}</strong></p>
            </section>

            <section class="cert-score-panel" aria-label="Test scores">
                <dl class="cert-total">
                    <dt class="cert-total-label">TOTAL SCORE</dt>
                    <dd class="cert-total-value">{{ resolvedTotalScore }}</dd>
                </dl>

                <div class="cert-metrics">
                    <dl class="cert-metric">
                        <dt class="cert-metric-label">Listening</dt>
                        <dd class="cert-metric-value">{{ listeningScore }}</dd>
                    </dl>
                    <span class="cert-metric-rule" aria-hidden="true"></span>
                    <dl class="cert-metric">
                        <dt class="cert-metric-label">Reading</dt>
                        <dd class="cert-metric-value">{{ readingScore }}</dd>
                    </dl>
                </div>

                <div class="cert-cefr">
                    <dl class="cert-cefr-level">
                        <dt class="cert-cefr-label">CEFR Level</dt>
                        <dd class="cert-cefr-value">{{ cefrLevel }}</dd>
                    </dl>
                    <span class="cert-cefr-rule" aria-hidden="true"></span>
                    <div class="cert-cefr-detail">
                        <p class="cert-cefr-title">{{ cefrTitle }}</p>
                        <p class="cert-cefr-description">{{ cefrDescription }}</p>
                    </div>
                </div>
            </section>

            <div class="cert-footer">
                <div class="cert-footer-info">
                    <div class="cert-test-dates">
                        <dl class="cert-date-item">
                            <dt class="cert-label">Date of Test</dt>
                            <dd class="cert-date-value">{{ testDate }}</dd>
                        </dl>
                        <span class="cert-date-rule" aria-hidden="true"></span>
                        <dl class="cert-date-item">
                            <dt class="cert-label">Valid Until</dt>
                            <dd class="cert-date-value">{{ validUntil }}</dd>
                        </dl>
                    </div>

                    <div class="cert-disclaimer-rule" aria-hidden="true"></div>
                    <p class="cert-disclaimer">
                        This certificate confirms that the test taker named above completed the English Proficiency Test administered by the Language Center, Universitas PGRI Yogyakarta.
                    </p>
                </div>

                <div class="cert-signatory">
                    <p class="cert-signatory-title">Head of Language Center</p>
                    <div class="cert-signature-stage">
                        <img
                            class="cert-stamp"
                            :src="stampSrc"
                            alt="Official stamp of the Language Center"
                        />
                        <img
                            class="cert-signature"
                            :src="signatureSrc"
                            :alt="`Signature of ${headName}`"
                        />
                    </div>
                    <p class="cert-head-name" :title="headName">{{ headName }}</p>
                    <p class="cert-head-nis">NIS. {{ headNis }}</p>
                </div>
            </div>

            <div class="cert-bottombar">
                <span class="cert-bottom-accent" aria-hidden="true"></span>
            </div>
        </div>
    </article>
</template>

<style scoped>
.certificate-frame {
    container-type: inline-size;
    width: 100%;
    font-family: 'Urbanist', system-ui, -apple-system, 'Segoe UI', sans-serif;
}

.certificate {
    --cert-navy: #0b2a6b;
    --cert-ink: #0f172a;
    --cert-muted: #4b5b78;
    --cert-line: #c9d2e3;
    --cert-score-bg: #eef2f9;
    --cert-cefr-bg: #e2e9f5;
    --cert-accent: #3f6fc0;
    --cert-white: #ffffff;
    --cert-u: calc(100vw / 1536);
    --cert-hairline: max(1px, calc(var(--cert-u) * 1));

    display: flex;
    flex-direction: column;
    width: 100%;
    aspect-ratio: 3 / 2;
    overflow: hidden;
    background-color: var(--cert-white);
    color: var(--cert-ink);
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

@supports (container-type: inline-size) {
    .certificate {
        --cert-u: calc(100cqw / 1536);
    }
}

.cert-topbar {
    flex: none;
    height: calc(var(--cert-u) * 14);
    background-color: var(--cert-navy);
}

.cert-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: calc(var(--cert-u) * 32);
    padding: calc(var(--cert-u) * 28) calc(var(--cert-u) * 96) calc(var(--cert-u) * 17);
}

.cert-brand {
    display: flex;
    align-items: center;
    gap: calc(var(--cert-u) * 24);
    min-width: 0;
}

.cert-logo {
    flex: none;
    width: calc(var(--cert-u) * 96);
    height: calc(var(--cert-u) * 96);
    object-fit: contain;
}

.cert-brand-rule {
    flex: none;
    width: var(--cert-hairline);
    height: calc(var(--cert-u) * 84);
    background-color: var(--cert-line);
}

.cert-brand-text {
    display: flex;
    flex-direction: column;
    gap: calc(var(--cert-u) * 6);
    min-width: 0;
}

.cert-brand-name {
    margin: 0;
    font-size: calc(var(--cert-u) * 30);
    line-height: 1.15;
    font-weight: 800;
    letter-spacing: 0.01em;
    color: var(--cert-navy);
    text-transform: uppercase;
    white-space: nowrap;
}

.cert-brand-university {
    margin: 0;
    font-size: calc(var(--cert-u) * 22);
    line-height: 1.2;
    font-weight: 400;
    color: var(--cert-ink);
    white-space: nowrap;
}

.cert-meta {
    display: flex;
    flex: none;
    flex-direction: column;
    gap: calc(var(--cert-u) * 4);
    max-width: calc(var(--cert-u) * 420);
    margin: 0;
    text-align: right;
}

.cert-label {
    margin: 0;
    font-size: calc(var(--cert-u) * 22);
    line-height: 1.2;
    font-weight: 400;
    color: var(--cert-muted);
}

.cert-value {
    max-width: 100%;
    margin: 0;
    overflow: hidden;
    font-size: calc(var(--cert-u) * 24);
    line-height: 1.2;
    font-weight: 700;
    color: var(--cert-ink);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cert-header-line {
    flex: none;
    height: var(--cert-hairline);
    margin: 0 calc(var(--cert-u) * 96);
    background-color: var(--cert-line);
}

.cert-title-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: calc(var(--cert-u) * 40);
    padding: calc(var(--cert-u) * 42) calc(var(--cert-u) * 96) 0;
}

.cert-title-block {
    min-width: 0;
}

.cert-title {
    margin: 0;
    font-size: calc(var(--cert-u) * 58);
    line-height: 1.05;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--cert-ink);
    white-space: nowrap;
}

.cert-subtitle {
    margin: calc(var(--cert-u) * 8) 0 0;
    font-size: calc(var(--cert-u) * 32);
    line-height: 1.2;
    font-weight: 400;
    letter-spacing: 0.02em;
    color: var(--cert-muted);
    white-space: nowrap;
}

.cert-issue {
    display: flex;
    flex: none;
    flex-direction: column;
    gap: calc(var(--cert-u) * 6);
    margin: 0;
    padding-left: calc(var(--cert-u) * 28);
    border-left: var(--cert-hairline) solid var(--cert-line);
}

.cert-taker {
    padding: calc(var(--cert-u) * 48) calc(var(--cert-u) * 96) 0;
}

.cert-eyebrow {
    margin: 0;
    font-size: calc(var(--cert-u) * 22);
    line-height: 1.2;
    font-weight: 500;
    letter-spacing: 0.08em;
    color: var(--cert-muted);
}

.cert-taker-name {
    margin: calc(var(--cert-u) * 6) 0 0;
    overflow: hidden;
    font-size: calc(var(--cert-u) * 38);
    line-height: 1.15;
    font-weight: 800;
    letter-spacing: 0.005em;
    color: var(--cert-ink);
    text-overflow: ellipsis;
    text-transform: uppercase;
    white-space: nowrap;
}

.cert-student-id {
    margin: calc(var(--cert-u) * 4) 0 0;
    font-size: calc(var(--cert-u) * 24);
    line-height: 1.3;
    font-weight: 400;
    color: var(--cert-ink);
}

.cert-student-id strong {
    font-weight: 700;
}

.cert-score-panel {
    display: flex;
    align-items: stretch;
    min-height: calc(var(--cert-u) * 178);
    margin: calc(var(--cert-u) * 40) calc(var(--cert-u) * 96) 0;
    overflow: hidden;
    border-radius: calc(var(--cert-u) * 28);
    background-color: var(--cert-score-bg);
}

.cert-total {
    display: flex;
    flex: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: calc(var(--cert-u) * 6);
    width: calc(var(--cert-u) * 330);
    margin: 0;
    padding: 0 calc(var(--cert-u) * 16);
    border-radius: calc(var(--cert-u) * 28) 0 0 calc(var(--cert-u) * 28);
    background-color: var(--cert-navy);
    clip-path: polygon(0 0, 100% 0, 84% 100%, 0 100%);
    color: var(--cert-white);
    text-align: center;
}

.cert-total-label {
    margin: 0;
    font-size: calc(var(--cert-u) * 30);
    line-height: 1.1;
    font-weight: 700;
    letter-spacing: 0.06em;
}

.cert-total-value {
    margin: 0;
    font-size: calc(var(--cert-u) * 80);
    line-height: 1;
    font-weight: 800;
    letter-spacing: -0.01em;
}

.cert-metrics {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: space-evenly;
    gap: calc(var(--cert-u) * 16);
    min-width: 0;
    padding: 0 calc(var(--cert-u) * 24);
}

.cert-metric {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    gap: calc(var(--cert-u) * 4);
    min-width: 0;
    margin: 0;
}

.cert-metric-label {
    margin: 0;
    font-size: calc(var(--cert-u) * 24);
    line-height: 1.2;
    font-weight: 400;
    color: var(--cert-ink);
}

.cert-metric-value {
    margin: 0;
    font-size: calc(var(--cert-u) * 52);
    line-height: 1;
    font-weight: 800;
    color: var(--cert-ink);
}

.cert-metric-rule {
    flex: none;
    width: var(--cert-hairline);
    height: calc(var(--cert-u) * 120);
    background-color: var(--cert-line);
}

.cert-cefr {
    display: flex;
    flex: none;
    align-items: center;
    gap: calc(var(--cert-u) * 22);
    width: calc(var(--cert-u) * 490);
    margin: calc(var(--cert-u) * 28) 0;
    padding: calc(var(--cert-u) * 8) calc(var(--cert-u) * 30);
    border-radius: calc(var(--cert-u) * 24);
    background-color: var(--cert-cefr-bg);
}

.cert-cefr-level {
    display: flex;
    flex: none;
    flex-direction: column;
    gap: calc(var(--cert-u) * 2);
    width: calc(var(--cert-u) * 145);
    margin: 0;
}

.cert-cefr-label {
    margin: 0;
    font-size: calc(var(--cert-u) * 24);
    line-height: 1.2;
    font-weight: 400;
    color: var(--cert-ink);
}

.cert-cefr-value {
    margin: 0;
    font-size: calc(var(--cert-u) * 60);
    line-height: 1;
    font-weight: 800;
    color: var(--cert-ink);
}

.cert-cefr-rule {
    flex: none;
    align-self: stretch;
    width: var(--cert-hairline);
    background-color: var(--cert-line);
}

.cert-cefr-detail {
    min-width: 0;
}

.cert-cefr-title {
    margin: 0;
    font-size: calc(var(--cert-u) * 28);
    line-height: 1.2;
    font-weight: 700;
    color: var(--cert-ink);
}

.cert-cefr-description {
    margin: calc(var(--cert-u) * 6) 0 0;
    font-size: calc(var(--cert-u) * 24);
    line-height: 1.35;
    font-weight: 400;
    color: var(--cert-muted);
}

.cert-footer {
    display: flex;
    flex: none;
    align-items: stretch;
    gap: calc(var(--cert-u) * 48);
    margin: calc(var(--cert-u) * 44) calc(var(--cert-u) * 96) 0;
}

.cert-footer-info {
    flex: 1;
    min-width: 0;
}

.cert-test-dates {
    display: flex;
    align-items: flex-start;
    gap: calc(var(--cert-u) * 48);
}

.cert-date-item {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: calc(var(--cert-u) * 4);
    margin: 0;
}

.cert-date-value {
    margin: 0;
    font-size: calc(var(--cert-u) * 26);
    line-height: 1.2;
    font-weight: 700;
    color: var(--cert-ink);
    white-space: nowrap;
}

.cert-date-rule {
    flex: none;
    align-self: stretch;
    width: var(--cert-hairline);
    background-color: var(--cert-line);
}

.cert-disclaimer-rule {
    height: var(--cert-hairline);
    margin-top: calc(var(--cert-u) * 30);
    background-color: var(--cert-line);
}

.cert-disclaimer {
    max-width: calc(var(--cert-u) * 800);
    margin: calc(var(--cert-u) * 16) 0 0;
    font-size: calc(var(--cert-u) * 19);
    line-height: 1.45;
    font-style: italic;
    font-weight: 400;
    color: var(--cert-muted);
}

.cert-signatory {
    flex: none;
    width: calc(var(--cert-u) * 480);
    padding-left: calc(var(--cert-u) * 48);
    border-left: var(--cert-hairline) solid var(--cert-line);
    text-align: center;
}

.cert-signatory-title {
    margin: 0;
    font-size: calc(var(--cert-u) * 24);
    line-height: 1.3;
    font-weight: 400;
    color: var(--cert-ink);
}

.cert-signature-stage {
    position: relative;
    height: calc(var(--cert-u) * 128);
}

.cert-stamp {
    position: absolute;
    bottom: calc(var(--cert-u) * 8);
    left: calc(var(--cert-u) * 20);
    width: calc(var(--cert-u) * 100);
    height: calc(var(--cert-u) * 100);
    object-fit: contain;
}

.cert-signature {
    position: absolute;
    bottom: calc(var(--cert-u) * 30);
    left: calc(var(--cert-u) * 100);
    width: calc(var(--cert-u) * 260);
    height: calc(var(--cert-u) * 90);
    object-fit: contain;
}

.cert-head-name {
    margin: 0;
    overflow: hidden;
    font-size: calc(var(--cert-u) * 26);
    line-height: 1.2;
    font-weight: 700;
    color: var(--cert-ink);
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cert-head-nis {
    margin: calc(var(--cert-u) * 4) 0 0;
    font-size: calc(var(--cert-u) * 24);
    line-height: 1.2;
    font-weight: 400;
    color: var(--cert-ink);
}

.cert-bottombar {
    position: relative;
    flex: none;
    height: calc(var(--cert-u) * 24);
    margin-top: auto;
    background-color: var(--cert-navy);
}

.cert-bottom-accent {
    position: absolute;
    right: 0;
    bottom: 0;
    width: calc(var(--cert-u) * 210);
    height: 100%;
    background-color: var(--cert-accent);
    clip-path: polygon(calc(var(--cert-u) * 24) 0, 100% 0, 100% 100%, 0 100%);
}

@media print {
    .certificate {
        page: certificate;
        break-inside: avoid;
    }
}

@page certificate {
    size: A4 landscape;
    margin: 0;
}
</style>
