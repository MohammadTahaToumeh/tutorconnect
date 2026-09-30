<template>
    <div class="app module-page">
        <Topbar />

        <main v-if="selectedStudyProgramId" class="module-main">
            <section class="module-hero">
                <RouterLink
                    class="back-btn"
                    to="/study-programs"
                >
                    ← Zurück zu den Studiengängen
                </RouterLink>

                <div class="module-hero-content">
                    <p class="module-kicker">TutorConnect · Fächer</p>
                    <h1>{{ selectedStudyProgram?.name || 'Studiengang' }}</h1>
                    <p>Wähle ein Fach und finde passende Tutoren.</p>
                </div>
            </section>

            <section class="module-section">
                <div class="module-title-row">
                    <div>
                        <p class="module-small">Verfügbare Fächer</p>
                        <h2>Wähle ein Fach</h2>
                    </div>
                    <span>{{ filteredSubjects.length }} Fächer</span>
                </div>
                <div class="search-box">
    <input
        v-model="search"
        type="text"
        placeholder="Fach suchen..."
    />
</div>
                <div v-if="loading" class="state-card">
                    Fächer werden geladen …
                </div>

                <div v-else-if="errorMessage" class="state-card state-error">
                    {{ errorMessage }}
                </div>

                <div v-else-if="filteredSubjects.length" class="module-grid">
                    <button
                        v-for="(subject, index) in filteredSubjects"
                        :key="subject.id"
                        type="button"
                        class="module-card"
                        @click="goToTutors(subject)"
                    >
                        <div class="module-card-top">
                            <span class="module-icon">{{ subject.icon }}</span>
                            <span class="module-number">Fach {{ index + 1 }}</span>
                        </div>

                        <h3>{{ subject.name }}</h3>

                        <p v-if="subject.description" class="module-description">
                            {{ subject.description }}
                        </p>
                        <p v-else class="module-description">
                            Tutoren und Lernangebote für dieses Fach ansehen.
                        </p>

                        <div class="module-meta">
                            <span>{{ selectedStudyProgram?.name }}</span>
                            <span class="module-link">Tutoren finden →</span>
                        </div>
                    </button>
                </div>

                <div v-else class="state-card">
                    Für diesen Studiengang wurden noch keine Fächer eingetragen.
                </div>
            </section>
        </main>

        <main v-else class="missing-program">
            <div class="missing-card">
                <p class="module-small">Noch kein Studiengang gewählt</p>
                <h1>Wähle zuerst deinen Studiengang</h1>
                <p>Danach zeigen wir dir nur die dazugehörigen Fächer.</p>
                <RouterLink class="choose-program-btn" to="/study-programs">
                    Studiengang auswählen
                </RouterLink>
            </div>
        </main>
    </div>
</template>

<script setup>
import Topbar from '../Components/Topbar.vue';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const subjects = ref([]);
const studyPrograms = ref([]);
const loading = ref(true);
const errorMessage = ref('');
const search = ref('');
const selectedStudyProgramId = computed(() => route.query.study_program_id ?? null);
const icons = ['🔢', '💻', '🧠', '🗄️', '🌐', '⚙️', '📡', '📊', '📘', '🧮'];

onMounted(async () => {
    try {
        const [subjectsResponse, programsResponse] = await Promise.all([
            axios.get('/api/subjects'),
            axios.get('/api/study-programs'),
        ]);

        studyPrograms.value = programsResponse.data;
        subjects.value = subjectsResponse.data.map((subject, index) => ({
            id: subject.id,
            study_program_id: subject.study_program_id,
            name: subject.name,
            description: subject.description,
            icon: icons[index % icons.length],
        }));
    } catch (error) {
        console.error('Fehler beim Laden:', error);
        errorMessage.value = 'Die Daten konnten nicht geladen werden.';
    } finally {
        loading.value = false;
    }
});

const selectedStudyProgram = computed(() =>
    studyPrograms.value.find(program => String(program.id) === String(selectedStudyProgramId.value))
);

const filteredSubjects = computed(() => {
    if (!selectedStudyProgramId.value) return [];

    return subjects.value.filter(subject =>
        String(subject.study_program_id) === String(selectedStudyProgramId.value) &&
        subject.name.toLowerCase().includes(search.value.toLowerCase())
    );
});


function goToTutors(subject) {
    router.push({
        path: '/tutors',
        query: {
            subject_id: subject.id,
            study_program_id: selectedStudyProgramId.value,
        },
    });
}
</script>

<style scoped>
.module-page {
    min-height: 100vh;
    background: #eef7f2;
}

.module-main {
    min-height: calc(100vh - 56px);
}

.module-hero {
    position: relative;
    overflow: hidden;
    padding: 50px 7vw 94px;
    color: #fff;
    background: linear-gradient(135deg, #0d6b52, #1a9e75);
}

.module-hero::after {
    content: '';
    position: absolute;
    width: 430px;
    height: 430px;
    right: -110px;
    top: -150px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.14);
}

.back-btn {
    position: relative;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    color: #fff;
    text-decoration: none;
    font-weight: 800;
    background: rgba(255, 255, 255, 0.18);
    padding: 9px 15px;
    border-radius: 999px;
    transition: background 0.2s ease, transform 0.2s ease;
}

.back-btn:hover {
    background: rgba(255, 255, 255, 0.26);
    transform: translateY(-2px);
}

.module-hero-content {
    position: relative;
    z-index: 2;
    max-width: 950px;
    margin-top: 30px;
}

.module-kicker,
.module-small {
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    font-size: 0.78rem;
    font-weight: 900;
}

.module-kicker {
    opacity: 0.78;
}

.module-hero h1 {
    max-width: 1000px;
    margin: 12px 0 14px;
    font-size: clamp(2.2rem, 5vw, 4.6rem);
    line-height: 1;
    overflow-wrap: anywhere;
}

.module-hero-content > p:last-child {
    margin: 0;
    font-size: clamp(1rem, 2vw, 1.35rem);
    opacity: 0.9;
}

.module-section {
    position: relative;
    z-index: 3;
    max-width: 1360px;
    margin: -54px auto 0;
    padding: 0 24px 72px;
}

.module-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
    padding: 22px 26px;
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 22px 50px rgba(0, 58, 43, 0.13);
}

.module-small {
    color: #0d6b52;
}

.module-title-row h2 {
    margin: 6px 0 0;
    font-size: clamp(1.7rem, 3vw, 2.2rem);
    color: #153d32;
}

.module-title-row > span {
    flex: none;
    padding: 11px 17px;
    border-radius: 999px;
    color: #0d6b52;
    background: #e8f5f0;
    font-weight: 900;
}

.module-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.module-card {
    width: 100%;
    min-height: 176px;
    padding: 20px;
    border: 1px solid rgba(0, 107, 79, 0.13);
    border-radius: 19px;
    color: inherit;
    text-align: left;
    font: inherit;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 16px 40px rgba(0, 67, 49, 0.09);
    transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
}

.module-card:hover {
    transform: translateY(-7px);
    border-color: rgba(0, 107, 79, 0.35);
    box-shadow: 0 26px 58px rgba(0, 67, 49, 0.17);
}

.module-card-top,
.module-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.module-icon {
    display: grid;
    width: 40px;
    height: 40px;
    place-items: center;
    border-radius: 12px;
    background: #e8f5f0;
    font-size: 1.15rem;
}

.module-number {
    padding: 7px 11px;
    border-radius: 999px;
    color: #0d6b52;
    background: #f2f8f5;
    font-size: 0.77rem;
    font-weight: 900;
}

.module-card h3 {
    margin: 17px 0 8px;
    color: #003f30;
    font-size: clamp(1.08rem, 1.6vw, 1.32rem);
    line-height: 1.25;
}

.module-description {
    min-height: 38px;
    margin: 0 0 18px;
    color: #4c6d64;
    line-height: 1.45;
}

.module-meta {
    align-items: flex-end;
    color: #5a776f;
    font-size: 0.82rem;
    font-weight: 800;
}

.module-meta > span:first-child {
    max-width: 65%;
}

.module-link {
    flex: none;
    color: #0d6b52;
}

.state-card,
.missing-card {
    padding: 34px;
    border-radius: 19px;
    background: #fff;
    box-shadow: 0 15px 36px rgba(0, 67, 49, 0.1);
    color: #345f54;
}

.state-error {
    color: #b42318;
}

.missing-program {
    min-height: calc(100vh - 56px);
    display: grid;
    place-items: center;
    padding: 32px;
    background: linear-gradient(135deg, #eaf7f1, #f7fbf9);
}

.missing-card {
    width: min(620px, 100%);
    text-align: center;
}

.missing-card h1 {
    margin: 10px 0;
    color: #123f33;
}

.choose-program-btn {
    display: inline-flex;
    margin-top: 16px;
    padding: 12px 18px;
    border-radius: 10px;
    color: #fff;
    background: #0d6b52;
    text-decoration: none;
    font-weight: 800;
}

@media (max-width: 1050px) {
    .module-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 800px) {
    .module-hero {
        padding: 46px 22px 105px;
    }

    .module-section {
        padding-inline: 16px;
    }

    .module-title-row {
        align-items: flex-start;
        flex-direction: column;
        padding: 23px;
    }

    .module-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 520px) {
    .module-card {
        padding: 21px;
    }

    .module-meta {
        align-items: flex-start;
        flex-direction: column;
    }

    .module-meta > span:first-child {
        max-width: 100%;
    }
    
}
.search-box{
    margin:20px 0;
}

.search-box input{
    width:100%;
    padding:14px 18px;
    border-radius:12px;
    border:1px solid #d7d7d7;
    font-size:16px;
}

.search-box input:focus{
    outline:none;
    border-color:#0d6b52;
}
</style>
