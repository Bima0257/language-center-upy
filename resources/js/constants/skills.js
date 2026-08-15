export const SKILL_OPTIONS = [
    { value: 'reading', label: 'Reading' },
    { value: 'listening', label: 'Listening' },
];

export function skillLabel(code) {
    return SKILL_OPTIONS.find((s) => s.value === code)?.label || '';
}

export function materialOfSkill(code) {
    return code === 'listening' ? 'audio' : 'text';
}
