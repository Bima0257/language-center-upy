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

/**
 * Cari skill berdasarkan ID dari array skillOptions.
 * @param {Array} skillOptions - Array [{value: id, label: name, code: code}]
 * @param {number} id - Skill ID
 * @returns {Object|null}
 */
export function skillById(skillOptions, id) {
    return skillOptions.find((s) => s.value === id) || null;
}

/**
 * Dapatkan label skill berdasarkan ID.
 * @param {Array} skillOptions
 * @param {number} id
 * @returns {string}
 */
export function skillLabelById(skillOptions, id) {
    return skillById(skillOptions, id)?.label || '';
}

/**
 * Dapatkan material type berdasarkan skill code.
 * @param {string} code - 'reading' atau 'listening'
 * @returns {string}
 */
export function materialOfSkillCode(code) {
    return code === 'listening' ? 'audio' : 'text';
}
