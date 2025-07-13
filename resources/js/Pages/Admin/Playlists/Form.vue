<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ArrowsUpDownIcon, XMarkIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    allMusic: Array, // Menerima `allMusic` dari controller
    playlist: Object,
});

const isEditMode = computed(() => !!props.playlist);

// State sekarang berisi musik yang terpilih
const selectedMusic = ref(props.playlist?.musics || []);

const form = useForm({
    name: props.playlist?.name || '',
    description: props.playlist?.description || '',
    // Payload utama kita adalah 'music', bukan 'media'
    music: [], 
});

// Daftar musik yang tersedia untuk dipilih
const availableMusic = computed(() => {
    const selectedIds = selectedMusic.value.map(m => m.id);
    return props.allMusic.filter(m => !selectedIds.includes(m.id));
});

const addMusicToPlaylist = (music) => {
    selectedMusic.value.push(music);
};

const removeMusicFromPlaylist = (index) => {
    selectedMusic.value.splice(index, 1);
};

const submit = () => {
    // Siapkan payload musik dengan id dan urutan
    form.music = selectedMusic.value.map((music, index) => ({
        id: music.id,
        play_order: index + 1,
    }));
    
    if (isEditMode.value) {
        form.put(route('admin.playlists.update', props.playlist.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route('admin.playlists.store'), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="isEditMode ? 'Edit Playlist Musik' : 'Buat Playlist Musik'" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ isEditMode ? 'Edit Playlist Musik' : 'Buat Playlist Musik Baru' }}</h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        <div class="lg:col-span-1 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm space-y-6">
                            <div>
                                <InputLabel for="name" value="Nama Playlist" required />
                                <TextInput id="name" v-model="form.name" class="w-full mt-1" />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="description" value="Deskripsi" />
                                <textarea id="description" v-model="form.description" rows="3" class="w-full mt-1 border-gray-300 rounded-md shadow-sm dark:bg-gray-900 dark:border-gray-700"></textarea>
                            </div>
                            <hr class="dark:border-gray-700"/>
                            <h3 class="font-semibold">Musik Terpilih ({{ selectedMusic.length }})</h3>
                            <InputError :message="form.errors.music" class="mb-2" />
                            
                            <draggable 
                                v-model="selectedMusic" 
                                item-key="id"
                                tag="ul"
                                handle=".handle"
                                class="space-y-2"
                            >
                                <template #item="{element, index}">
                                    <li class="flex items-center gap-3 p-2 bg-gray-100 dark:bg-gray-700 rounded-md cursor-grab">
                                        <ArrowsUpDownIcon class="h-5 w-5 text-gray-400 handle flex-shrink-0" />
                                        <span class="font-bold text-gray-600 dark:text-gray-300">{{ index + 1 }}.</span>
                                        <div class="flex-grow truncate">
                                            <p class="text-sm text-gray-800 dark:text-gray-100 font-semibold">{{ element.title }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ element.singer }}</p>
                                        </div>
                                        <button @click="removeMusicFromPlaylist(index)" type="button" class="text-red-500 hover:text-red-700">
                                            <XMarkIcon class="h-5 w-5" />
                                        </button>
                                    </li>
                                </template>
                            </draggable>

                            <p v-if="!selectedMusic.length" class="text-sm text-gray-500">Klik musik dari daftar di sebelah kanan untuk menambah.</p>
                        </div>

                        <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                            <h3 class="font-semibold mb-4">Daftar Musik Tersedia (Klik untuk menambah)</h3>
                            <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[60vh] overflow-y-auto pr-2">
                                <li v-if="!availableMusic.length" class="col-span-full text-center py-8 text-gray-500">Semua musik sudah dipilih.</li>
                                <li v-for="music in availableMusic" :key="music.id" @click="addMusicToPlaylist(music)"
                                    class="border rounded-lg p-3 cursor-pointer transition-all hover:border-blue-500 hover:ring-1 hover:ring-blue-200 dark:border-gray-700">
                                     <p class="font-semibold text-sm truncate text-gray-800 dark:text-gray-200">{{ music.title }}</p>
                                     <p class="text-xs text-gray-500 dark:text-gray-400">{{ music.singer }}</p>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-x-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
                         <Link :href="route('admin.playlists.index')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:text-gray-200 dark:bg-gray-900 dark:border-gray-600 dark:hover:bg-gray-700">Batal</Link>
                         <PrimaryButton :disabled="form.processing">{{ isEditMode ? 'Update Playlist' : 'Simpan Playlist' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
