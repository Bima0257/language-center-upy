<script setup>
import { IconBook2, IconHeadphones, IconCircleCheck } from '@tabler/icons-vue';
import ListItem from '@/Components/Dashboard/ListItem.vue';
import { h } from 'vue';

const assignments = [
    {
        icon: h(IconBook2), iconBg: 'bg-pastel-blue',
        title: 'Reading Task 1: Teks Astronomi',
        subtitle: 'Academic Reading • Deadline: Tomorrow',
    },
    {
        icon: h(IconHeadphones), iconBg: 'bg-pastel-purple',
        title: 'Listening: Lecture on Marine Biology',
        subtitle: 'Academic Listening • Completed',
        completed: true,
    },
];
</script>

<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-headline-md font-bold text-primary">Tugas Saya (5)</h3>
            <button class="text-primary hover:underline text-label-md font-medium">Lihat Semua</button>
        </div>
        <div class="space-y-3">
            <ListItem v-for="(item, i) in assignments" :key="i"
                      :icon="item.icon" :icon-bg="item.iconBg"
                      :title="item.title" :subtitle="item.subtitle"
                      :completed="item.completed">
                <template v-if="item.completed" #completed-icon>
                    <IconCircleCheck :size="24" stroke="1.5" />
                </template>
            </ListItem>
        </div>
    </div>
</template>
