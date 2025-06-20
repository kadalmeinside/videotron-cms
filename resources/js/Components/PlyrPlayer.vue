<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Plyr from 'plyr';
import 'plyr/dist/plyr.css';

const props = defineProps({
    media: {
        type: Object,
        required: true,
    }
});

const emit = defineEmits(['playing', 'ended']);

const playerEl = ref(null);
let player = null;

onMounted(() => {
    if (!playerEl.value) return;

    // Siapkan opsi player
    const options = {
        autoplay: true,
        muted: false,
        controls: false,
        loop: { active: false }, // Kita kontrol loop dari parent
        clickToPlay: false,
    };

    // Buat instance player baru
    player = new Plyr(playerEl.value, options);

    // Kirim event ke parent
    player.on('playing', () => emit('playing'));
    player.on('ended', () => emit('ended'));
});

// Pastikan untuk menghancurkan instance player saat komponen dilepas
onUnmounted(() => {
    if (player) {
        player.destroy();
    }
});
</script>

<template>
    <!-- 
      Struktur template ini sangat sederhana.
      Plyr akan secara cerdas merender <iframe> untuk YouTube
      atau menggunakan <video> ini untuk file lokal.
    -->
    <video v-if="media.source_type === 'local'" ref="playerEl" playsinline>
        <source :src="media.source_value" type="video/mp4" />
    </video>

    <div v-if="media.source_type === 'youtube'" 
         ref="playerEl"
         data-plyr-provider="youtube"
         :data-plyr-embed-id="media.source_value">
    </div>
</template>

<style>
/* Style ini memastikan player mengisi kontainer */
.plyr {
    width: 100%;
    height: 100%;
}
</style>
