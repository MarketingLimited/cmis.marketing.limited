@extends('layouts.admin')

@section('title', __('users.profile'))

@section('content')
<div class="container mx-auto px-4 py-6" x-data="userProfile()">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('users.profile') }}</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('users.manage_users_desc') }}</p>
    </div>

    <!-- Success Message -->
    <div x-show="successMessage"
         x-transition
         class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg"
         x-text="successMessage"
         @click="successMessage = ''">
    </div>

    <!-- Error Message -->
    <div x-show="errorMessage"
         x-transition
         class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg"
         x-text="errorMessage"
         @click="errorMessage = ''">
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <!-- Avatar Section -->
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <img :src="user.avatar || '/images/default-avatar.png'"
                             :alt="user.name"
                             class="w-32 h-32 rounded-full object-cover border-4 border-gray-200 dark:border-gray-700">

                        <!-- Upload Button Overlay -->
                        <label for="avatar-upload"
                               class="absolute bottom-0 end-0 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-2 cursor-pointer shadow-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </label>
                        <input type="file"
                               id="avatar-upload"
                               accept="image/*"
                               @change="uploadAvatar"
                               class="hidden">
                    </div>

                    <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-white" x-text="user.name"></h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="user.email"></p>

                    <div class="mt-4 flex items-center gap-2 text-sm">
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full font-medium"
                              x-text="user.status || '{{ __('users.status_active') }}'">
                        </span>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('users.role') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize" x-text="user.role || '{{ __('users.n_a') }}'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('users.member_since') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white" x-text="formatDate(user.created_at)"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('users.current_language') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                <span x-text="user.locale === 'ar' ? '{{ __('users.arabic') }}' : '{{ __('users.english') }}'"></span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Right Column - Profile Forms -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Information -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('users.personal_information') }}</h3>

                <form @submit.prevent="updateProfile" class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('users.name') }}
                        </label>
                        <input type="text"
                               id="name"
                               x-model="form.name"
                               required
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>

                    <!-- Email (Read-only) -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('users.email') }}
                        </label>
                        <input type="email"
                               id="email"
                               :value="user.email"
                               disabled
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                    </div>

                    <!-- Bio -->
                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('users.bio') }}
                        </label>
                        <textarea id="bio"
                                  x-model="form.bio"
                                  rows="4"
                                  placeholder="{{ __('users.bio_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit"
                                :disabled="saving"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg font-medium transition">
                            <span x-show="!saving">{{ __('users.save_changes') }}</span>
                            <span x-show="saving">{{ __('users.saving') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Preferences -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('users.preferences') }}</h3>

                <form @submit.prevent="updateLanguage" class="space-y-4">
                    <!-- Language Preference -->
                    <div>
                        <label for="locale" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('users.preferred_language') }}
                        </label>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ __('users.language_description') }}</p>

                        <select id="locale"
                                x-model="form.locale"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">{{ __('users.select_language') }}</option>
                            <option value="ar">{{ __('users.arabic') }}</option>
                            <option value="en">{{ __('users.english') }}</option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit"
                                :disabled="savingLanguage"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg font-medium transition">
                            <span x-show="!savingLanguage">{{ __('users.save_changes') }}</span>
                            <span x-show="savingLanguage">{{ __('users.saving') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Settings -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('users.account_settings') }}</h3>

                <dl class="space-y-3">
                    <div class="flex justify-between items-center py-3 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('users.user_id') }}</dt>
                            <dd class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-mono" x-text="user.id"></dd>
                        </div>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('users.account_status') }}</dt>
                            <dd class="mt-1 text-sm">
                                <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded text-xs font-medium"
                                      x-text="user.status || '{{ __('users.status_active') }}'">
                                </span>
                            </dd>
                        </div>
                    </div>
                    <div class="flex justify-between items-center py-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('users.joined') }}</dt>
                            <dd class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="formatDate(user.created_at)"></dd>
                        </div>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function userProfile() {
    return {
        user: {},
        form: {
            name: '',
            bio: '',
            locale: ''
        },
        saving: false,
        savingLanguage: false,
        successMessage: '',
        errorMessage: '',

        async init() {
            await this.loadUser();
        },

        async loadUser() {
            try {
                const response = await fetch('/api/auth/me');
                if (!response.ok) throw new Error('Failed to load user');

                this.user = await response.json();

                // Initialize form with user data
                this.form.name = this.user.name || '';
                this.form.bio = this.user.bio || '';
                this.form.locale = this.user.locale || 'ar';
            } catch (error) {
                console.error('Error loading user:', error);
                this.showError('{{ __('users.load_failed') }}');
            }
        },

        async updateProfile() {
            this.saving = true;
            this.clearMessages();

            try {
                const response = await fetch('/api/profile', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        name: this.form.name,
                        bio: this.form.bio
                    })
                });

                if (!response.ok) throw new Error('Update failed');

                await this.loadUser();
                this.showSuccess('{{ __('users.profile_updated') }}');
            } catch (error) {
                console.error('Error updating profile:', error);
                this.showError('{{ __('users.update_failed') }}');
            } finally {
                this.saving = false;
            }
        },

        async updateLanguage() {
            this.savingLanguage = true;
            this.clearMessages();

            try {
                const response = await fetch('/api/profile/language', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        locale: this.form.locale
                    })
                });

                if (!response.ok) throw new Error('Language update failed');

                // Update cookie and reload page to apply language change
                document.cookie = `app_locale=${this.form.locale}; path=/; max-age=31536000; SameSite=Lax`;

                this.showSuccess('{{ __('users.language_updated') }}');

                // Reload page after 1 second to apply language change
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } catch (error) {
                console.error('Error updating language:', error);
                this.showError('{{ __('users.update_failed') }}');
            } finally {
                this.savingLanguage = false;
            }
        },

        async uploadAvatar(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                this.showError('{{ __('users.avatar_size_error') }}');
                return;
            }

            // Validate file type
            if (!file.type.startsWith('image/')) {
                this.showError('{{ __('users.avatar_type_error') }}');
                return;
            }

            const formData = new FormData();
            formData.append('avatar', file);

            try {
                const response = await fetch('/api/profile/avatar', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Avatar upload failed');

                const data = await response.json();
                this.user.avatar = data.avatar_url;
                this.showSuccess('{{ __('users.avatar_updated') }}');
            } catch (error) {
                console.error('Error uploading avatar:', error);
                this.showError('Failed to upload avatar');
            }
        },

        formatDate(date) {
            if (!date) return '{{ __('users.n_a') }}';
            return new Date(date).toLocaleDateString('{{ app()->getLocale() }}', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        showSuccess(message) {
            this.successMessage = message;
            setTimeout(() => this.successMessage = '', 5000);
        },

        showError(message) {
            this.errorMessage = message;
            setTimeout(() => this.errorMessage = '', 5000);
        },

        clearMessages() {
            this.successMessage = '';
            this.errorMessage = '';
        }
    };
}
</script>
@endpush
@endsection
