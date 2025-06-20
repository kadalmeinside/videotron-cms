<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';
import PlyrPlayer from '@/Components/PlyrPlayer.vue'; 
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    videotronUuid: String,
});

// --- STATE ---
const status = ref('loading');
const playlist = ref(null);
const currentScheduleId = ref(null);
const currentVideotronId = ref(null);
const currentMediaIndex = ref(0);
const serverTime = ref('Menghubungkan...');
const countdown = ref(0);

// --- STATE OTENTIKASI BARU ---
const isAuthenticated = ref(false);
const loginError = ref('');
const loginForm = ref({
    password: '',
    processing: false,
});

// --- REFS & TIMERS ---
const topBannerEl = ref(null);
const bottomBannerEl = ref(null);
let scheduleRefreshTimer = null;
let countdownTimer = null;
let playbackControlTimer = null;

// --- COMPUTED PROPERTIES ---
const currentMedia = computed(() => {
    if (isAuthenticated.value && status.value === 'playing' && playlist.value?.media?.length > 0) {
        return playlist.value.media[currentMediaIndex.value];
    }
    return null;
});

const isLoading = computed(() => status.value === 'loading');

// --- FUNGSI-FUNGSI ---
const nextMedia = () => {
    if (!playlist.value || playlist.value.media.length === 0) return;
    clearTimeout(playbackControlTimer);
    currentMediaIndex.value = (currentMediaIndex.value + 1) % playlist.value.media.length;
};

const startCountdown = () => {
    clearInterval(countdownTimer);
    if (!currentMedia.value) { countdown.value = 0; return; }
    countdown.value = currentMedia.value.duration;
    countdownTimer = setInterval(() => {
        if (countdown.value > 0) countdown.value--;
        else clearInterval(countdownTimer);
    }, 1000);
};

const sendPlayLog = () => {
    if (!currentMedia.value || !playlist.value || !currentScheduleId.value || !currentVideotronId.value) return;
    axios.post('/api/log/play', {
        media_id: currentMedia.value.id,
        videotron_id: currentVideotronId.value,
        playlist_id: playlist.value.id,
        schedule_id: currentScheduleId.value,
    }).catch(error => console.error("Gagal mengirim play log:", error.response?.data));
};

const fetchSchedule = async () => {
    try {
        const response = await axios.get(`/api/player/${props.videotronUuid}/now`);
        serverTime.value = response.data.server_time;
        const isNewPlaylist = JSON.stringify(response.data.playlist) !== JSON.stringify(playlist.value);
        status.value = response.data.status;
        
        if (status.value === 'playing' && isNewPlaylist) {
            playlist.value = response.data.playlist;
            currentScheduleId.value = response.data.schedule_id;
            currentVideotronId.value = response.data.videotron_id;
            currentMediaIndex.value = 0;
        } else if (status.value !== 'playing') {
            playlist.value = null;
            currentScheduleId.value = null;
            currentVideotronId.value = null;
        }
    } catch (error) {
        status.value = 'error';
        serverTime.value = 'Gagal terhubung ke server.';
    }
};

const handlePlaybackStart = () => {
    startCountdown();
    sendPlayLog();
    clearTimeout(playbackControlTimer);
    if (currentMedia.value?.duration > 0) {
        playbackControlTimer = setTimeout(nextMedia, currentMedia.value.duration * 1000);
    }
};

// --- FUNGSI OTENTIKASI & KONEKSI BARU ---
const attemptLogin = async () => {
    loginForm.value.processing = true;
    loginError.value = '';
    try {
        const response = await axios.post('/api/player/login', {
            uuid: props.videotronUuid,
            password: loginForm.value.password,
        });
        const token = response.data.token;
        if (token) {
            localStorage.setItem(`videotron_token_${props.videotronUuid}`, token);
            isAuthenticated.value = true;
            connectToMonitoring(token);
            fetchSchedule(); // Ambil jadwal setelah berhasil login
            scheduleRefreshTimer = setInterval(fetchSchedule, 60000);
        }
    } catch (error) {
        console.error("Login Gagal:", error);
        loginError.value = 'Password atau ID Videotron salah.';
    } finally {
        loginForm.value.processing = false;
    }
};

const connectToMonitoring = (token) => {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT,
        wssPort: import.meta.env.VITE_REVERB_PORT,
        forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
        enabledTransports: ['ws', 'wss'],
        // Menggunakan authEndpoint standar karena kita mengirim token
        authEndpoint: '/broadcasting/auth',
        auth: {
            headers: {
                Authorization: `Bearer ${token}`, // <-- KIRIM TOKEN OTENTIKASI
            },
        },
    });

    // Bergabung ke channel presence 'monitoring'
    window.Echo.join(`monitoring`)
        .here((users) => { console.log('Player berhasil join. Pengguna lain yang online:', users.map(u => u.name)); })
        .joining((user) => { console.log('User lain bergabung:', user.name); })
        .leaving((user) => { console.log('User lain meninggalkan:', user.name); })
        .error((error) => { console.error('Gagal terhubung ke channel:', error); });
};

// --- LIFECYCLE & WATCHERS ---
onMounted(() => {
    const storedToken = localStorage.getItem(`videotron_token_${props.videotronUuid}`);
    if (storedToken) {
        console.log("Token ditemukan, mencoba menghubungkan...");
        isAuthenticated.value = true;
        connectToMonitoring(storedToken);
        fetchSchedule();
        scheduleRefreshTimer = setInterval(fetchSchedule, 60000);
    } else {
        console.log("Tidak ada token, menampilkan form login.");
        status.value = 'login_required';
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('monitoring');
    }
    if (scheduleRefreshTimer) clearInterval(scheduleRefreshTimer);
    if (countdownTimer) clearInterval(countdownTimer);
    if (playbackControlTimer) clearTimeout(playbackControlTimer);
});

watch(currentMedia, (newMedia) => {
    const defaultBg = 'transparent';
    if(topBannerEl.value) topBannerEl.value.style.backgroundImage = newMedia?.top_banner_url ? `url('${newMedia.top_banner_url}')` : defaultBg;
    if(bottomBannerEl.value) bottomBannerEl.value.style.backgroundImage = newMedia?.bottom_banner_url ? `url('${newMedia.bottom_banner_url}')` : defaultBg;
}, { immediate: true, deep: true });

</script>

<template>
    <Head title="Videotron Player" />
    <!-- Tampilan Utama (Setelah Login) -->
    <div v-if="isAuthenticated" class="bg-gray-900 text-white main-grid p-4">
        <div ref="topBannerEl" class="ads-banner rounded-lg shadow-lg" :class="{ 'animate-pulse bg-gray-800': isLoading && !currentMedia?.top_banner_url }"></div>
        <div class="video-container rounded-lg shadow-lg relative" :class="{ 'animate-pulse bg-black': isLoading }">
            <PlyrPlayer v-if="currentMedia" :key="currentMedia.id" :media="currentMedia" @playing="handlePlaybackStart" @ended="nextMedia" />
            <div v-if="status === 'playing' && countdown > 0" class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-lg font-mono py-1 px-3 rounded-md z-10">
                Sisa Waktu: {{ countdown }}s
            </div>
        </div>
        <div ref="bottomBannerEl" class="ads-banner rounded-lg shadow-lg" :class="{ 'animate-pulse bg-gray-800': isLoading && !currentMedia?.bottom_banner_url }"></div>
        <div v-if="currentMedia?.running_text && !isLoading" class="bg-red-600 rounded-md shadow-lg p-2 overflow-hidden whitespace-nowrap">
            <p class="marquee-content font-semibold text-lg">{{ currentMedia.running_text }}</p>
        </div>
        <div v-if="status !== 'playing' && !isLoading" class="fullscreen-overlay flex items-center justify-center text-center p-4">
            <div>
                <h1 class="text-4xl font-bold">TIDAK ADA JADWAL</h1>
                <p class="text-xl text-gray-400 mt-2">Videotron ID: {{ videotronUuid }}</p>
                <div class="mt-6 p-3 bg-black bg-opacity-50 rounded-lg">
                    <p class="text-lg text-yellow-300 font-mono">Waktu Server: {{ serverTime }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tampilan Form Login -->
    <div v-else class="h-screen w-screen bg-gray-800 flex items-center justify-center p-6">
        <div class="w-full max-w-sm bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-center text-gray-900">Aktivasi Player</h2>
            <p class="text-center text-gray-500 mt-2">Masukkan password untuk videotron ini.</p>
            <form @submit.prevent="attemptLogin" class="mt-8 space-y-6">
                 <div>
                    <InputLabel for="uuid" value="Videotron ID" />
                    <TextInput id="uuid" type="text" class="mt-1 block w-full bg-gray-100" :value="videotronUuid" disabled />
                 </div>
                 <div>
                    <InputLabel for="password" value="Password" />
                    <TextInput id="password" type="password" class="mt-1 block w-full" v-model="loginForm.password" required autofocus />
                    <InputError :message="loginError" class="mt-2" />
                 </div>
                 <div>
                    <PrimaryButton class="w-full justify-center" :class="{ 'opacity-25': loginForm.processing }" :disabled="loginForm.processing">
                        {{ loginForm.processing ? 'Menghubungkan...' : 'Aktivasi' }}
                    </PrimaryButton>
                 </div>
            </form>
        </div>
    </div>
</template>

<style>
/* Style tidak ada perubahan */
body, html { font-family: 'Inter', sans-serif; overflow: hidden; background-color: #111827; }
.main-grid { width: 100vw; height: 100vh; display: grid; grid-template-rows: 1fr auto 1fr auto; gap: 1rem; }
.video-container { display: flex; justify-content: center; align-items: center; min-height: 0; background-color: #000; overflow: hidden;}
.ads-banner { background-size: cover; background-position: center; transition: background-image 0.5s ease-in-out, background-color 0.3s; }
.marquee-content { animation: marquee 30s linear infinite; display: inline-block; padding-left: 100%; }
@keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-100%); } }
.animate-blink { animation: blinker 1.5s linear infinite; }
@keyframes blinker { 50% { opacity: 0.5; } }
.plyr { width: 100%; height: 100%; }
.fullscreen-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); z-index: 50; }
</style>
