/**
 * Real-time Date Validation Mixin
 * Issue #5: Prevents start date after end date
 *
 * Usage:
 * x-data="{ ...dateValidation(), ...yourData }"
 */

export function dateValidation() {
    return {
        dateErrors: {
            start_date: '',
            end_date: ''
        },

        validateDates() {
            this.dateErrors = {
                start_date: '',
                end_date: ''
            };

            if (!this.formData.start_date || !this.formData.end_date) {
                return true; // Skip validation if dates not set
            }

            const startDate = new Date(this.formData.start_date);
            const endDate = new Date(this.formData.end_date);

            if (startDate > endDate) {
                this.dateErrors.end_date = 'End date must be after start date';
                return false;
            }

            if (startDate < new Date()) {
                this.dateErrors.start_date = 'Start date cannot be in the past';
                return false;
            }

            return true;
        },

        watchDates() {
            this.$watch('formData.start_date', () => this.validateDates());
            this.$watch('formData.end_date', () => this.validateDates());
        },

        isDateValid() {
            return !this.dateErrors.start_date && !this.dateErrors.end_date;
        },

        canSubmit() {
            return this.validateDates() && this.isFormValid();
        }
    };
}
