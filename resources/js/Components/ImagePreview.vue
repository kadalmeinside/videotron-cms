<script setup>
import { computed } from 'vue';
import { XCircleIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    // URL gambar yang sudah ada (untuk mode edit)
    existingUrl: {
        type: String,
        default: null,
    },
    // Objek File yang baru dipilih
    file: {
        type: File,
        default: null,
    }
});

const emit = defineEmits(['clear']);

// Membuat URL sementara untuk preview file yang baru dipilih
const previewUrl = computed(() => {
    if (props.file) {
        return URL.createObjectURL(props.file);
    }
    return props.existingUrl;
});
</script>

<template>
    <div v-if="previewUrl" class="relative w-full h-32 border-2 border-dashed rounded-lg flex items-center justify-center bg-gray-100">
        <img :src="previewUrl" class="max-w-full max-h-full object-contain" alt="Image Preview" />
        <button 
            @click.prevent="$emit('clear')" 
            type="button" 
            class="absolute -top-2 -right-2 bg-white rounded-full text-gray-500 hover:text-red-600 transition"
            title="Hapus gambar"
        >
            <XCircleIcon class="h-6 w-6" />
        </button>
    </div>
</template>