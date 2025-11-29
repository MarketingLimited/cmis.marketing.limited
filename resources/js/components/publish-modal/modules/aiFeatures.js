/**
 * Publish Modal - AI Features Module
 * Handles AI assistant, brand voice, content generation, brand safety
 */

export function getAIFeaturesMethods() {
    return {
        // ============================================
        // BRAND SAFETY
        // ============================================

        checkBrandSafety() {
            // Simulated brand safety check
            this.brandSafetyIssues = [];
            const text = this.content.global.text.toLowerCase();

            // Add real brand safety checks based on selected profile group's policy
            this.brandSafetyStatus = this.brandSafetyIssues.length === 0 ? 'pass' : 'fail';
        },

        // ============================================
        // AI CONTENT GENERATION
        // ============================================

        async generateWithAI() {
            this.isGenerating = true;
            try {
                // Call AI generation API
                const response = await fetch('/api/ai/generate-content', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        original_text: this.content.global.text,
                        brand_voice_id: this.aiSettings.brandVoice,
                        tone: this.aiSettings.tone,
                        length: this.aiSettings.length,
                        instructions: this.aiSettings.prompt
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    this.aiSuggestions = data.suggestions || [];
                } else {
                    const error = await response.json();
                    window.notify && window.notify(error.message || 'Failed to generate AI suggestions', 'error');
                }
            } catch (e) {
                console.error('AI generation failed', e);
                window.notify && window.notify('AI generation failed. Please try again.', 'error');
            } finally {
                this.isGenerating = false;
            }
        },

        useSuggestion(suggestion) {
            this.content.global.text = suggestion;
            this.showAIAssistant = false;
        },

        // ============================================
        // BRAND VOICE LOADING
        // ============================================

        async loadBrandVoices() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/ai/brand-voices`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                if (response.ok) {
                    const result = await response.json();
                    this.brandVoices = result.data || result;
                } else {
                    console.error('Failed to load brand voices');
                }
            } catch (e) {
                console.error('Failed to load brand voices', e);
            }
        },

        // ============================================
        // AI SUGGESTIONS HELPERS
        // ============================================

        async improveTone() {
            this.aiSettings.prompt = 'Improve the tone of this content';
            await this.generateWithAI();
        },

        async makeMoreEngaging() {
            this.aiSettings.prompt = 'Make this content more engaging';
            await this.generateWithAI();
        },

        async shortenContent() {
            this.aiSettings.length = 'short';
            this.aiSettings.prompt = 'Shorten this content while keeping the main message';
            await this.generateWithAI();
        },

        async expandContent() {
            this.aiSettings.length = 'long';
            this.aiSettings.prompt = 'Expand this content with more details';
            await this.generateWithAI();
        },

        async translateContent(language) {
            this.aiSettings.prompt = `Translate this content to ${language}`;
            await this.generateWithAI();
        },

        async addHashtags() {
            this.aiSettings.prompt = 'Add relevant hashtags to this content';
            await this.generateWithAI();
        },

        async addEmojis() {
            this.aiSettings.prompt = 'Add appropriate emojis to this content';
            await this.generateWithAI();
        },

        // ============================================
        // SENTIMENT ANALYSIS
        // ============================================

        async analyzeSentiment() {
            if (!this.content.global.text.trim()) return;

            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/ai/analyze-sentiment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        text: this.content.global.text
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    this.sentimentScore = data.score;
                    this.sentimentLabel = data.label; // positive, neutral, negative
                } else {
                    console.error('Failed to analyze sentiment');
                }
            } catch (e) {
                console.error('Sentiment analysis failed', e);
            }
        }
    };
}

export default getAIFeaturesMethods;
