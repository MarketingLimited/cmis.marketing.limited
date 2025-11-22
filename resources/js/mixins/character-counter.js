/**
 * Character Counter Mixin
 * Issue #6: Shows character count on text fields
 *
 * Usage:
 * x-data="{ ...characterCounter(), ...yourData }"
 */

export function characterCounter() {
    return {
        characterCounts: {},
        characterLimits: {},

        initCharacterCounter(fieldName, limit) {
            this.characterLimits[fieldName] = limit;
            this.updateCharacterCount(fieldName);

            // Watch for changes
            this.$watch(`formData.${fieldName}`, () => {
                this.updateCharacterCount(fieldName);
            });
        },

        updateCharacterCount(fieldName) {
            const value = this.formData[fieldName] || '';
            this.characterCounts[fieldName] = value.length;
        },

        getCharacterCount(fieldName) {
            return this.characterCounts[fieldName] || 0;
        },

        getCharacterLimit(fieldName) {
            return this.characterLimits[fieldName] || 0;
        },

        getCharacterCountText(fieldName) {
            const count = this.getCharacterCount(fieldName);
            const limit = this.getCharacterLimit(fieldName);
            return `${count}/${limit}`;
        },

        isNearLimit(fieldName, threshold = 0.9) {
            const count = this.getCharacterCount(fieldName);
            const limit = this.getCharacterLimit(fieldName);
            return count >= (limit * threshold);
        },

        isOverLimit(fieldName) {
            const count = this.getCharacterCount(fieldName);
            const limit = this.getCharacterLimit(fieldName);
            return count > limit;
        },

        getCounterClass(fieldName) {
            if (this.isOverLimit(fieldName)) {
                return 'text-red-600 font-bold';
            } else if (this.isNearLimit(fieldName)) {
                return 'text-orange-500';
            }
            return 'text-gray-500';
        }
    };
}
