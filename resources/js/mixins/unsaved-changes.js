/**
 * Unsaved Changes Warning Mixin
 * Issue #4: Warns users before leaving forms with unsaved changes
 *
 * Usage in Alpine.js components:
 * x-data="{ ...unsavedChanges(), ...yourData }"
 */

export function unsavedChanges() {
    return {
        hasUnsavedChanges: false,
        originalFormData: null,
        autoSaveInterval: null,
        lastAutoSave: null,

        initUnsavedChangesWarning() {
            // Capture initial form state
            this.captureFormState();

            // Enable browser warning
            window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));

            // Auto-save every 30 seconds
            this.startAutoSave();

            // Mark as changed when form inputs change
            this.$watch('formData', () => {
                this.hasUnsavedChanges = this.isFormChanged();
            });
        },

        captureFormState() {
            if (this.formData) {
                this.originalFormData = JSON.parse(JSON.stringify(this.formData));
            }
        },

        isFormChanged() {
            if (!this.originalFormData || !this.formData) return false;
            return JSON.stringify(this.originalFormData) !== JSON.stringify(this.formData);
        },

        handleBeforeUnload(e) {
            if (this.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        },

        startAutoSave() {
            if (!this.autoSave) return; // Only if autoSave function is defined

            this.autoSaveInterval = setInterval(() => {
                if (this.hasUnsavedChanges) {
                    this.autoSave();
                    this.lastAutoSave = new Date();
                }
            }, 30000); // 30 seconds
        },

        markAsSaved() {
            this.hasUnsavedChanges = false;
            this.captureFormState();
        },

        cleanup() {
            window.removeEventListener('beforeunload', this.handleBeforeUnload);
            if (this.autoSaveInterval) {
                clearInterval(this.autoSaveInterval);
            }
        }
    };
}
