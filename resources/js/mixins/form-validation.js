/**
 * Field-Level Validation Highlighting
 * Issue #15: Highlights validation errors on specific fields
 *
 * Usage:
 * x-data="{ ...formValidation(), ...yourData }"
 */

export function formValidation() {
    return {
        errors: {},
        touched: {},

        initValidation() {
            // Load server-side validation errors
            this.loadServerErrors();
        },

        loadServerErrors() {
            const errorsElement = document.querySelector('[data-validation-errors]');
            if (errorsElement) {
                try {
                    this.errors = JSON.parse(errorsElement.textContent);
                } catch (e) {
                    console.error('Failed to parse validation errors:', e);
                }
            }
        },

        hasError(fieldName) {
            return this.errors[fieldName] !== undefined && this.errors[fieldName].length > 0;
        },

        getError(fieldName) {
            if (!this.hasError(fieldName)) return '';
            return Array.isArray(this.errors[fieldName])
                ? this.errors[fieldName][0]
                : this.errors[fieldName];
        },

        getAllErrors(fieldName) {
            return this.errors[fieldName] || [];
        },

        clearError(fieldName) {
            delete this.errors[fieldName];
        },

        clearAllErrors() {
            this.errors = {};
        },

        setError(fieldName, message) {
            this.errors[fieldName] = Array.isArray(message) ? message : [message];
        },

        markTouched(fieldName) {
            this.touched[fieldName] = true;
        },

        isTouched(fieldName) {
            return this.touched[fieldName] || false;
        },

        shouldShowError(fieldName) {
            return this.isTouched(fieldName) && this.hasError(fieldName);
        },

        getFieldClasses(fieldName, baseClasses = '') {
            const errorClasses = this.hasError(fieldName)
                ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';

            return `${baseClasses} ${errorClasses}`;
        },

        // Client-side validation rules
        validateRequired(fieldName, value, message = 'This field is required') {
            if (!value || value.trim() === '') {
                this.setError(fieldName, message);
                return false;
            }
            this.clearError(fieldName);
            return true;
        },

        validateEmail(fieldName, value, message = 'Please enter a valid email address') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (value && !emailRegex.test(value)) {
                this.setError(fieldName, message);
                return false;
            }
            this.clearError(fieldName);
            return true;
        },

        validateMinLength(fieldName, value, minLength, message = null) {
            const msg = message || `Minimum ${minLength} characters required`;
            if (value && value.length < minLength) {
                this.setError(fieldName, msg);
                return false;
            }
            this.clearError(fieldName);
            return true;
        },

        validateMaxLength(fieldName, value, maxLength, message = null) {
            const msg = message || `Maximum ${maxLength} characters allowed`;
            if (value && value.length > maxLength) {
                this.setError(fieldName, msg);
                return false;
            }
            this.clearError(fieldName);
            return true;
        },

        isFormValid() {
            return Object.keys(this.errors).length === 0;
        }
    };
}
