/**
 * Publish Modal - Profile Management Module
 * Handles profile group and profile selection logic
 */

export function getProfileManagementMethods() {
    return {
        // ============================================
        // COMPUTED PROPERTIES
        // ============================================

        // Filter profile groups based on selected group IDs
        get filteredProfileGroups() {
            // Safety check for initialization
            if (!this.selectedGroupIds || !Array.isArray(this.selectedGroupIds) || !this.profileGroups || !Array.isArray(this.profileGroups)) {
                return [];
            }

            // If no groups selected, show nothing (must select groups first)
            if (this.selectedGroupIds.length === 0) {
                return [];
            }

            return this.profileGroups
                .filter(group => this.selectedGroupIds.includes(group.group_id))
                .map(group => ({
                    ...group,
                    profiles: group.profiles?.filter(p =>
                        (!this.platformFilter || p.platform === this.platformFilter) &&
                        (!this.profileSearch || p?.account_name?.toLowerCase().includes(this.profileSearch.toLowerCase()))
                    ) || []
                })).filter(g => g.profiles.length > 0);
        },

        // Check if a profile is selected
        isProfileSelected(integrationId) {
            return this.selectedProfiles.some(p => p.integration_id === integrationId);
        },

        // ============================================
        // STEP 1: Profile Group Selection Functions
        // ============================================

        // Toggle a group ID in the selection
        toggleGroupId(groupId) {
            const index = this.selectedGroupIds.indexOf(groupId);
            if (index >= 0) {
                this.selectedGroupIds.splice(index, 1);
                // Also remove profiles from this group
                const group = this.profileGroups.find(g => g.group_id === groupId);
                if (group?.profiles) {
                    group.profiles.forEach(profile => {
                        const profileIndex = this.selectedProfiles.findIndex(p => p.integration_id === profile.integration_id);
                        if (profileIndex >= 0) {
                            this.selectedProfiles.splice(profileIndex, 1);
                        }
                    });
                }
            } else {
                this.selectedGroupIds.push(groupId);
            }
        },

        // Select all groups
        selectAllGroups() {
            this.selectedGroupIds = this.profileGroups.map(g => g.group_id);
        },

        // Clear all selected groups and profiles
        clearSelectedGroups() {
            this.selectedGroupIds = [];
            this.selectedProfiles = [];
        },

        // ============================================
        // STEP 2: Profile Selection Functions
        // ============================================

        // Toggle profile selection
        toggleProfileSelection(profile) {
            const index = this.selectedProfiles.findIndex(p => p.integration_id === profile.integration_id);
            if (index >= 0) {
                this.selectedProfiles.splice(index, 1);
            } else {
                this.selectedProfiles.push(profile);
            }
        },

        selectAllProfiles() {
            this.filteredProfileGroups.forEach(group => {
                group.profiles.forEach(profile => {
                    if (!this.isProfileSelected(profile.integration_id)) {
                        this.selectedProfiles.push(profile);
                    }
                });
            });
        },

        clearSelectedProfiles() {
            this.selectedProfiles = [];
        },

        // Check if all profiles in a group are selected
        isGroupFullySelected(group) {
            if (!group.profiles || group.profiles.length === 0) return false;
            return group.profiles.every(p => this.isProfileSelected(p.integration_id));
        },

        // Check if any profile in a group is selected
        isGroupPartiallySelected(group) {
            if (!group.profiles || group.profiles.length === 0) return false;
            const selectedCount = group.profiles.filter(p => this.isProfileSelected(p.integration_id)).length;
            return selectedCount > 0 && selectedCount < group.profiles.length;
        },

        // Toggle all profiles in a group
        toggleGroupSelection(group) {
            if (!group.profiles) return;

            if (this.isGroupFullySelected(group)) {
                // Deselect all profiles in this group
                group.profiles.forEach(profile => {
                    const index = this.selectedProfiles.findIndex(p => p.integration_id === profile.integration_id);
                    if (index >= 0) {
                        this.selectedProfiles.splice(index, 1);
                    }
                });
            } else {
                // Select all profiles in this group
                group.profiles.forEach(profile => {
                    if (!this.isProfileSelected(profile.integration_id)) {
                        this.selectedProfiles.push(profile);
                    }
                });
            }
        },

        // ============================================
        // HELPER METHODS
        // ============================================

        getSelectedPlatforms() {
            // Safety check for initialization
            if (!this.selectedProfiles || !Array.isArray(this.selectedProfiles)) {
                return [];
            }
            return [...new Set(this.selectedProfiles.map(p => p.platform))];
        },

        getPlatformIcon(platform) {
            const icons = {
                facebook: 'fab fa-facebook',
                instagram: 'fab fa-instagram',
                twitter: 'fab fa-twitter',
                linkedin: 'fab fa-linkedin',
                tiktok: 'fab fa-tiktok',
                youtube: 'fab fa-youtube',
                google_business: 'fas fa-google'
            };
            return icons[platform] || 'fas fa-globe';
        },

        getPlatformBgClass(platform) {
            const classes = {
                facebook: 'bg-blue-600',
                instagram: 'bg-pink-500',
                twitter: 'bg-sky-500',
                linkedin: 'bg-blue-700',
                tiktok: 'bg-gray-900',
                youtube: 'bg-red-600',
                google_business: 'bg-green-600'
            };
            return classes[platform] || 'bg-gray-500';
        },

        getDefaultAvatar(profile) {
            return `https://ui-avatars.com/api/?name=${encodeURIComponent(profile.account_name || 'U')}&background=6366f1&color=fff`;
        },

        // Load profile groups from API
        async loadProfileGroups() {
            try {
                const response = await fetch('/api/profile-groups');
                if (response.ok) {
                    this.profileGroups = await response.json();
                }
            } catch (error) {
                console.error('Failed to load profile groups:', error);
            }
        }
    };
}

export default getProfileManagementMethods;
