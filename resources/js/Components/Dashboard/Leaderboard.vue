<script setup>
import { IconCoin, IconAward, IconChevronRight } from '@tabler/icons-vue';
import { h } from 'vue';

const players = [
    { rank: 1, name: 'Liz Cheng', role: 'Customer Service', points: 476, avatar: '#1' },
    { rank: 2, name: 'Jenny Wilson', role: 'Marketing', points: 461, avatar: '#2' },
    { rank: 3, name: 'Ralph Edwards', role: 'Finance', points: 429, avatar: '#3' },
];
</script>

<template>
    <div class="space-y-8">
        <div>
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-headline-md font-bold text-primary">Peringkat</h3>
                <IconChevronRight class="text-text-muted" :size="20" />
            </div>

            <div class="bg-surface-container-low rounded-2xl p-6 mb-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-full border-2 border-primary overflow-hidden bg-surface flex items-center justify-center text-primary font-bold">
                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-headline-md font-bold text-primary">#14</p>
                        <p class="text-label-md font-medium text-text-muted">Posisi Anda di papan peringkat</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex items-center gap-1.5 bg-pastel-peach px-3 py-1.5 rounded-full">
                        <IconCoin :size="16" class="text-orange-500" />
                        <span class="text-label-md font-medium text-orange-800">288 points</span>
                    </div>
                    <div class="flex items-center gap-1.5 bg-pastel-purple px-3 py-1.5 rounded-full">
                        <IconAward :size="16" class="text-purple-600" />
                        <span class="text-label-md font-medium text-purple-800">7 badges</span>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div v-for="player in players" :key="player.rank"
                     class="flex items-center gap-4 p-3 hover:bg-surface-white rounded-xl transition-colors">
                    <span class="text-title-lg font-semibold text-primary w-6">#{{ player.rank }}</span>
                    <div class="w-10 h-10 rounded-full overflow-hidden bg-surface-container flex items-center justify-center text-primary font-bold text-sm">
                        {{ player.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h6 class="text-label-md font-medium text-primary truncate">{{ player.name }}</h6>
                        <p class="text-[11px] text-text-muted truncate">{{ player.role }}</p>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <IconCoin :size="16" class="text-orange-500" />
                        <span class="text-label-md font-medium text-primary">{{ player.points }} poin</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
