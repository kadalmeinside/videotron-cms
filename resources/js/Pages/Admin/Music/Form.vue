<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';

const page = usePage();
const music = computed(() => page.props.music || null);
const isAnalyzing = ref(false);

const form = useForm({
    title: music.value?.title || '',
    singer: music.value?.singer || '',
    album: music.value?.album || '',
    year: music.value?.year || '',
    genre: music.value?.genre || '',
    audio_file: null,
    _method: music.value ? 'PUT' : 'POST',
});

// --- FUNGSI BARU UNTUK ANALISIS FILE ---
const handleFileChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    form.audio_file = file;
    isAnalyzing.value = true;

    const formData = new FormData();
    formData.append('audio_file', file);

    try {
        const response = await axios.post(route('admin.music.analyze'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        // Isi form dengan data dari server
        form.title = response.data.title || '';
        form.singer = response.data.singer || '';
        form.album = response.data.album || '';
        form.year = response.data.year || '';
        form.genre = response.data.genre || '';

    } catch (error) {
        console.error("Gagal menganalisis file:", error);
        alert('Gagal membaca metadata dari file. Silakan isi manual.');
    } finally {
        isAnalyzing.value = false;
    }
};

const submit = () => {
    const routeName = music.value ? 'admin.music.update' : 'admin.music.store';
    const routeParams = music.value ? music.value.id : {};
    
    form.post(route(routeName, routeParams), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="music ? 'Edit Musik' : 'Tambah Musik Baru'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ music ? 'Edit Musik' : 'Tambah Musik Baru' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div v-if="!music">
                                <InputLabel for="audio_file" value="File Audio (MP3, WAV)" />
                                <input id="audio_file" type="file" @change="handleFileChange" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                                <div v-if="isAnalyzing" class="mt-2 text-sm text-gray-500">Menganalisis file...</div>
                                <InputError class="mt-2" :message="form.errors.audio_file" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="title" value="Judul Lagu" />
                                    <TextInput id="title" type="text" class="mt-1 block w-full" v-model="form.title" required autofocus />
                                    <InputError class="mt-2" :message="form.errors.title" />
                                </div>
                                <div>
                                    <InputLabel for="singer" value="Penyanyi" />
                                    <TextInput id="singer" type="text" class="mt-1 block w-full" v-model="form.singer" required />
                                    <InputError class="mt-2" :message="form.errors.singer" />
                                </div>
                                <div>
                                    <InputLabel for="album" value="Album (Opsional)" />
                                    <TextInput id="album" type="text" class="mt-1 block w-full" v-model="form.album" />
                                    <InputError class="mt-2" :message="form.errors.album" />
                                </div>
                                <div>
                                    <InputLabel for="year" value="Tahun (Opsional)" />
                                    <TextInput id="year" type="text" class="mt-1 block w-full" v-model="form.year" />
                                    <InputError class="mt-2" :message="form.errors.year" />
                                </div>
                            </div>
                            
                            <div>
                                <InputLabel for="genre" value="Genre (Opsional)" />
                                <TextInput id="genre" type="text" class="mt-1 block w-full" v-model="form.genre" />
                                <InputError class="mt-2" :message="form.errors.genre" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing || isAnalyzing">{{ music ? 'Simpan Perubahan' : 'Tambah Musik' }}</PrimaryButton>
                                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Tersimpan.</p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
