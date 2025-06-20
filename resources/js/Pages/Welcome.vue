<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/solid';

const page = usePage();

const props = defineProps({
    canLogin: Boolean,
    userIp: String,
});

const user = computed(() => page.props.auth.user);

// Konten slide yang sudah disesuaikan dengan tema aplikasi
const slides = ref([
    {
        title: 'REACH YOUR AUDIENCE',
        subtitle: 'Digital Out-of-Home',
        image: 'https://images.unsplash.com/photo-1582091652153-eb8f55ff7cd9?q=80&w=764&auto=format&fit=crop',
    },
    {
        title: 'POWERFUL AD MANAGEMENT',
        subtitle: 'Control Everything',
        image: 'https://images.unsplash.com/photo-1706516560059-b03772add416?q=80&w=1470&auto=format&fit=crop',
    },
    {
        title: 'DATA-DRIVEN INSIGHTS',
        subtitle: 'Analyze & Optimize',
        image: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=2070&auto=format&fit=crop',
    }
]);

const currentSlide = ref(0);
let slideInterval = null;

const nextSlide = () => {
    currentSlide.value = (currentSlide.value + 1) % slides.value.length;
};
const prevSlide = () => {
    currentSlide.value = (currentSlide.value - 1 + slides.value.length) % slides.value.length;
};

onMounted(() => {
    // Ganti slide setiap 7 detik
    slideInterval = setInterval(nextSlide, 7000);
});

onUnmounted(() => {
    clearInterval(slideInterval);
});

</script>

<template>
    <Head title="Selamat Datang" />
    <div class="bg-zinc-900 text-white selection:bg-red-600 selection:text-white">
        <header class="absolute top-0 left-0 right-0 z-20">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <a href="/" class="flex items-center space-x-2">
                    <span class="font-teko text-2xl md:text-3xl font-bold tracking-wider drop-shadow-lg">ADS VIDEO CMS</span>
                </a>
                <!-- <Link v-if="user" :href="route('dashboard')" class="text-white font-semibold hover:text-red-500 transition">Dashboard</Link>
                <Link v-else :href="route('login')" class="text-white font-semibold hover:text-red-500 transition">Login</Link> -->
            </nav>
        </header>

        <main>
            <section class="relative h-screen w-full overflow-hidden">
                <div>
                    <div v-for="(slide, index) in slides" :key="index" 
                         class="absolute inset-0 h-full w-full transition-opacity duration-1000 ease-in-out" 
                         :class="index === currentSlide ? 'opacity-100' : 'opacity-0'">
                        
                        <img :src="slide.image" :alt="slide.title" class="h-full w-full object-cover">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/40 to-transparent"></div>
                        
                        <div class="absolute inset-0 flex items-center">
                            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-white">
                                <div class="w-full mr-6 ml-9 md:w-3/4 lg:w-2/3">
                                    <h2 class="font-teko text-3xl md:text-4xl font-semibold uppercase text-red-500 tracking-wider">{{ slide.subtitle }}</h2>
                                    <h1 class="font-teko text-4xl md:text-7xl lg:text-8xl font-bold uppercase leading-none tracking-tight -mt-2 md:-mt-4">{{ slide.title }}</h1>
                                    <p class="mt-4 max-w-xl text-lg text-white/80">
                                        Platform terpusat untuk mengelola, menjadwalkan, dan memonitor semua aset iklan digital Anda dengan mudah dan efisien.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button @click="prevSlide" class="absolute left-4 top-1/2 -translate-y-1/2 z-10 p-3 bg-white/10 hover:bg-white/30 rounded-full text-white backdrop-blur-sm transition-colors">
                    <ChevronLeftIcon class="h-6 w-6"/>
                </button>
                <button @click="nextSlide" class="absolute right-4 top-1/2 -translate-y-1/2 z-10 p-3 bg-white/10 hover:bg-white/30 rounded-full text-white backdrop-blur-sm transition-colors">
                    <ChevronRightIcon class="h-6 w-6"/>
                </button>
            </section>
        </main>

        <footer class="absolute bottom-0 left-0 right-0 z-20 bg-gradient-to-t from-black/50 to-transparent">
             <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-400">
                <p>&copy; {{ new Date().getFullYear() }} ADS VIDEO CMS. All rights reserved.</p>
                <p class="mt-2 sm:mt-0">
                    (Your IP: {{ userIp }})
                </p>
             </div>
        </footer>
    </div>
</template>

<style scoped>
/* Impor font dari Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Teko:wght@400;600;700&family=Inter:wght@400;600&display=swap');


:root {
    --font-sans: 'Inter', sans-serif;
    --font-display: 'Teko', sans-serif;
}

body {
    font-family: var(--font-sans);
}
.font-teko {
    font-family: var(--font-display);
}
</style>
