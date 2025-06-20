<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import draggable from 'vuedraggable'
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ArrowsUpDownIcon, XMarkIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    allMedia: Array,
    playlist: Object,
});

const isEditMode = computed(() => !!props.playlist);

const selectedMedia = ref(props.playlist?.media || []);

const form = useForm({
    name: props.playlist?.name || '',
    description: props.playlist?.description || '',
});

const availableMedia = computed(() => {
    const selectedIds = selectedMedia.value.map(m => m.id);
    return props.allMedia.filter(m => !selectedIds.includes(m.id));
});

const addMediaToPlaylist = (media) => {
    selectedMedia.value.push(media);
};

const removeMediaFromPlaylist = (index) => {
    selectedMedia.value.splice(index, 1);
};

const submit = () => {
    const mediaPayload = selectedMedia.value.map((media, index) => ({
        id: media.id,
        play_order: index + 1,
    }));
    
    const dataToSend = {
        ...form.data(),
        media: mediaPayload,
    };
    
    const routeName = isEditMode.value ? 'admin.playlists.update' : 'admin.playlists.store';
    const routeParams = isEditMode.value ? props.playlist.id : undefined;

    router.post(route(routeName, routeParams), {
        ...dataToSend,
        _method: isEditMode.value ? 'PUT' : 'POST',
    });
};

import { router } from '@inertiajs/vue3'

</script>

<template>
    <Head :title="isEditMode ? 'Edit Playlist' : 'Buat Playlist'" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ isEditMode ? 'Edit Playlist' : 'Buat Playlist Baru' }}</h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-sm space-y-6">
                            <div>
                                <InputLabel for="name" value="Nama Playlist" required />
                                <TextInput id="name" v-model="form.name" class="w-full mt-1" />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="description" value="Deskripsi" />
                                <textarea id="description" v-model="form.description" rows="3" class="w-full mt-1 border-gray-300 rounded-md shadow-sm"></textarea>
                            </div>
                            <hr/>
                            <h3 class="font-semibold">Media Terpilih ({{ selectedMedia.length }})</h3>
                            <InputError :message="form.errors.media" class="mb-2" />
                            
                            <draggable 
                                v-model="selectedMedia" 
                                item-key="id"
                                tag="ul"
                                handle=".handle"
                                class="space-y-2"
                            >
                                <template #item="{element, index}">
                                    <li class="flex items-center gap-3 p-2 bg-gray-100 rounded-md cursor-grab">
                                        <ArrowsUpDownIcon class="h-5 w-5 text-gray-400 handle flex-shrink-0" />
                                        <span class="font-bold text-gray-600">{{ index + 1 }}.</span>
                                        <p class="text-sm text-gray-800 flex-grow truncate">{{ element.title }}</p>
                                        <button @click="removeMediaFromPlaylist(index)" type="button" class="text-red-500 hover:text-red-700">
                                            <XMarkIcon class="h-5 w-5" />
                                        </button>
                                    </li>
                                </template>
                            </draggable>

                            <p v-if="!selectedMedia.length" class="text-sm text-gray-500">Pilih & seret media dari daftar di sebelah kanan untuk mengurutkan.</p>
                        </div>

                        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-sm">
                            <h3 class="font-semibold mb-4">Daftar Media (Klik untuk menambah)</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-h-[60vh] overflow-y-auto pr-2">
                                <div v-if="!availableMedia.length" class="col-span-full text-center py-8 text-gray-500">Semua media sudah dipilih.</div>
                                <div v-for="media in availableMedia" :key="media.id" @click="addMediaToPlaylist(media)"
                                    class="border rounded-lg p-2 cursor-pointer transition-all hover:border-blue-500 hover:ring-1 hover:ring-blue-200">
                                     <img v-if="media.source_type === 'local'" :src="media.preview_url" class="w-full h-20 object-cover rounded bg-gray-200" :alt="media.title"/>
                                     <div v-else class="w-full h-20 bg-gray-800 text-white flex items-center justify-center rounded">
                                        <span class="text-lg font-bold">{{ media.source_type.charAt(0).toUpperCase() }}</span>
                                    </div>
                                    <p class="text-xs mt-2 truncate h-8">{{ media.title }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-x-4 bg-white p-4 rounded-lg shadow-sm">
                         <Link :href="route('admin.playlists.index')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Batal</Link>
                         <PrimaryButton :disabled="form.processing">{{ isEditMode ? 'Update Playlist' : 'Simpan Playlist' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
