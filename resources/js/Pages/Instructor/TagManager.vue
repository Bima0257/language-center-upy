<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconPlus, IconTrash, IconEdit, IconX, IconCheck } from '@tabler/icons-vue';
import { ref } from 'vue';

defineProps({
    tags: { type: Array, default: () => [] },
});

const showCreate = ref(false);
const editingId = ref(null);

const createForm = useForm({ name: '', type: 'skill' });
const editForm = useForm({ name: '', type: '' });

function submitCreate() {
    createForm.post(route('content-library.tags.store'), {
        preserveScroll: true,
        onSuccess: () => { showCreate.value = false; createForm.reset(); },
    });
}

function startEdit(tag) {
    editingId.value = tag.id;
    editForm.name = tag.name;
    editForm.type = tag.type;
}

function saveEdit(id) {
    editForm.put(route('content-library.tags.update', id), {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}

function deleteTag(id) {
    if (!confirm('Hapus tag ini?')) return;
    router.delete(route('content-library.tags.destroy', id), { preserveScroll: true });
}

const groupedTags = (tags) => ({
    skill: tags.filter(t => t.type === 'skill'),
    topic: tags.filter(t => t.type === 'topic'),
    level: tags.filter(t => t.type === 'level'),
});
</script>

<template>
    <Head title="Manajemen Tag" />
    <DashboardLayout title="Manajemen Tag">
        <div class="flex justify-between mb-6">
            <p class="text-text-body text-body-md">{{ tags.length }} tag</p>
            <button @click="showCreate = !showCreate"
                    class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                <IconPlus :size="18" /> Tambah Tag
            </button>
        </div>

        <div v-if="showCreate" class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 mb-6">
            <form @submit.prevent="submitCreate" class="flex gap-3 items-end">
                <div class="flex-1">
                    <label class="text-label-md font-medium text-primary block mb-1.5">Nama Tag</label>
                    <input type="text" v-model="createForm.name" required placeholder="Nama tag..."
                           class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                </div>
                <div class="w-36">
                    <label class="text-label-md font-medium text-primary block mb-1.5">Tipe</label>
                    <select v-model="createForm.type"
                            class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                        <option value="skill">Keahlian</option>
                        <option value="topic">Topik</option>
                        <option value="level">Tingkat</option>
                    </select>
                </div>
                <button type="submit" :disabled="createForm.processing"
                        class="bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                    {{ createForm.processing ? '...' : 'Simpan' }}
                </button>
                <button type="button" @click="showCreate = false"
                        class="p-3 text-text-muted hover:text-primary">
                    <IconX :size="20" />
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-for="(group, type) in groupedTags(tags)" :key="type" class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30">
                <h3 class="text-title-lg font-semibold text-primary capitalize mb-4">{{ type }}</h3>
                <div v-if="group.length === 0" class="text-text-muted text-body-md text-center py-4">Belum ada tag.</div>
                <div v-for="tag in group" :key="tag.id" class="flex items-center justify-between py-2 border-b border-outline-variant/20 last:border-0">
                    <div v-if="editingId !== tag.id" class="flex items-center gap-2 flex-1">
                        <span class="inline-block px-3 py-1 rounded-full text-label-md font-medium"
                              :class="tag.type === 'skill' ? 'bg-pastel-purple/50 text-primary' : tag.type === 'topic' ? 'bg-pastel-blue/50 text-primary' : 'bg-pastel-peach/50 text-primary'">
                            {{ tag.name }}
                        </span>
                    </div>
                    <div v-else class="flex gap-2 flex-1">
                        <input type="text" v-model="editForm.name"
                               class="flex-1 px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-xl text-body-md" />
                        <button @click="saveEdit(tag.id)" class="text-green-600"><IconCheck :size="18" /></button>
                        <button @click="editingId = null" class="text-text-muted"><IconX :size="18" /></button>
                    </div>
                    <div v-if="editingId !== tag.id" class="flex gap-1 ml-2">
                        <button @click="startEdit(tag)" class="p-1.5 text-text-muted hover:text-secondary"><IconEdit :size="16" /></button>
                        <button @click="deleteTag(tag.id)" class="p-1.5 text-text-muted hover:text-error-red"><IconTrash :size="16" /></button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
