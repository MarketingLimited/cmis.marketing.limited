/**
 * CMIS API Client
 *
 * Standardized JavaScript client for interacting with CMIS API endpoints.
 * Provides consistent error handling, loading states, and request formatting.
 *
 * @version 1.0.0
 */

class CMISApiClient {
    constructor(options = {}) {
        this.baseUrl = options.baseUrl || '/api';
        this.token = options.token || this.getStoredToken();
        this.orgId = options.orgId || this.getStoredOrgId();
        this.onError = options.onError || this.defaultErrorHandler;
        this.onUnauthorized = options.onUnauthorized || this.defaultUnauthorizedHandler;
    }

    /**
     * Set authentication token
     */
    setToken(token) {
        this.token = token;
        localStorage.setItem('cmis_auth_token', token);
    }

    /**
     * Set organization ID
     */
    setOrgId(orgId) {
        this.orgId = orgId;
        localStorage.setItem('cmis_org_id', orgId);
    }

    /**
     * Get stored token
     */
    getStoredToken() {
        return localStorage.getItem('cmis_auth_token');
    }

    /**
     * Get stored org ID
     */
    getStoredOrgId() {
        return localStorage.getItem('cmis_org_id');
    }

    /**
     * Build headers for requests
     */
    getHeaders(customHeaders = {}) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...customHeaders,
        };

        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }

        return headers;
    }

    /**
     * Make HTTP request
     */
    async request(method, endpoint, data = null, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;

        const config = {
            method,
            headers: this.getHeaders(options.headers),
        };

        if (data) {
            if (method === 'GET') {
                // Convert data to query string
                const params = new URLSearchParams(data);
                const queryString = params.toString();
                return this.request(method, `${endpoint}?${queryString}`, null, options);
            } else {
                config.body = JSON.stringify(data);
            }
        }

        try {
            const response = await fetch(url, config);

            // Handle non-JSON responses
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response;
            }

            const responseData = await response.json();

            // Handle errors
            if (!response.ok) {
                if (response.status === 401) {
                    this.onUnauthorized(responseData);
                }
                throw new APIError(responseData.message || 'Request failed', {
                    status: response.status,
                    errors: responseData.errors,
                    response: responseData,
                });
            }

            return responseData;
        } catch (error) {
            if (error instanceof APIError) {
                this.onError(error);
                throw error;
            }

            const apiError = new APIError('Network error or invalid response', {
                originalError: error,
            });
            this.onError(apiError);
            throw apiError;
        }
    }

    /**
     * GET request
     */
    get(endpoint, params = {}, options = {}) {
        return this.request('GET', endpoint, params, options);
    }

    /**
     * POST request
     */
    post(endpoint, data = {}, options = {}) {
        return this.request('POST', endpoint, data, options);
    }

    /**
     * PUT request
     */
    put(endpoint, data = {}, options = {}) {
        return this.request('PUT', endpoint, data, options);
    }

    /**
     * DELETE request
     */
    delete(endpoint, options = {}) {
        return this.request('DELETE', endpoint, null, options);
    }

    /**
     * Default error handler
     */
    defaultErrorHandler(error) {
        console.error('CMIS API Error:', error);
    }

    /**
     * Default unauthorized handler
     */
    defaultUnauthorizedHandler(response) {
        console.warn('Unauthorized request, redirecting to login');
        window.location.href = '/login';
    }

    // ==============================================
    // CAMPAIGNS API
    // ==============================================

    campaigns = {
        list: (params = {}) => this.get('/campaigns', params),
        get: (id) => this.get(`/campaigns/${id}`),
        create: (data) => this.post('/campaigns', data),
        update: (id, data) => this.put(`/campaigns/${id}`, data),
        delete: (id) => this.delete(`/campaigns/${id}`),
        analytics: (id, params = {}) => this.get(`/campaigns/${id}/analytics`, params),
    };

    // ==============================================
    // CONTENT PLANS API
    // ==============================================

    contentPlans = {
        list: (params = {}) => this.get('/creative/content-plans', params),
        get: (id) => this.get(`/creative/content-plans/${id}`),
        create: (data) => this.post('/creative/content-plans', data),
        update: (id, data) => this.put(`/creative/content-plans/${id}`, data),
        delete: (id) => this.delete(`/creative/content-plans/${id}`),
        generate: (id, data = {}) => this.post(`/creative/content-plans/${id}/generate`, data),
        approve: (id) => this.post(`/creative/content-plans/${id}/approve`),
        reject: (id, reason) => this.post(`/creative/content-plans/${id}/reject`, { reason }),
        publish: (id) => this.post(`/creative/content-plans/${id}/publish`),
        stats: () => this.get('/creative/content-plans-stats'),
    };

    // ==============================================
    // ORGANIZATION MARKETS API
    // ==============================================

    markets = {
        list: (params = {}) => this.get(`/orgs/${this.orgId}/markets`, params),
        get: (marketId) => this.get(`/orgs/${this.orgId}/markets/${marketId}`),
        add: (data) => this.post(`/orgs/${this.orgId}/markets`, data),
        update: (marketId, data) => this.put(`/orgs/${this.orgId}/markets/${marketId}`, data),
        remove: (marketId) => this.delete(`/orgs/${this.orgId}/markets/${marketId}`),
        available: () => this.get(`/orgs/${this.orgId}/markets/available`),
        stats: () => this.get(`/orgs/${this.orgId}/markets/stats`),
        calculateRoi: (marketId, revenue) => this.post(`/orgs/${this.orgId}/markets/${marketId}/roi`, { revenue }),
    };

    // ==============================================
    // GPT API
    // ==============================================

    gpt = {
        getContext: () => this.get('/gpt/context'),
        listCampaigns: (params = {}) => this.get('/gpt/campaigns', params),
        getCampaign: (id) => this.get(`/gpt/campaigns/${id}`),
        createCampaign: (data) => this.post('/gpt/campaigns', data),
        getCampaignAnalytics: (id, params = {}) => this.get(`/gpt/campaigns/${id}/analytics`, params),

        contentPlans: {
            list: (params = {}) => this.get('/gpt/content-plans', params),
            create: (data) => this.post('/gpt/content-plans', data),
            generate: (id, data = {}) => this.post(`/gpt/content-plans/${id}/generate`, data),
        },

        knowledge: {
            search: (query, options = {}) => this.post('/gpt/knowledge/search', { query, ...options }),
            add: (data) => this.post('/gpt/knowledge', data),
        },

        conversation: {
            getSession: (sessionId = null) => this.get('/gpt/conversation/session', { session_id: sessionId }),
            sendMessage: (sessionId, message) => this.post('/gpt/conversation/message', { session_id: sessionId, message }),
            getHistory: (sessionId, limit = 20) => this.get(`/gpt/conversation/${sessionId}/history`, { limit }),
            clear: (sessionId) => this.delete(`/gpt/conversation/${sessionId}/clear`),
            stats: (sessionId) => this.get(`/gpt/conversation/${sessionId}/stats`),
        },

        insights: (contextType, contextId, question = null) =>
            this.post('/gpt/ai/insights', { context_type: contextType, context_id: contextId, question }),
    };

    // ==============================================
    // AUTH API
    // ==============================================

    auth = {
        login: (email, password) =>
            this.post('/auth/login', { email, password }),

        register: (data) =>
            this.post('/auth/register', data),

        logout: () =>
            this.post('/auth/logout'),

        refreshToken: () =>
            this.post('/auth/refresh'),

        me: () =>
            this.get('/auth/me'),
    };
}

/**
 * Custom API Error class
 */
class APIError extends Error {
    constructor(message, details = {}) {
        super(message);
        this.name = 'APIError';
        this.status = details.status;
        this.errors = details.errors;
        this.response = details.response;
        this.originalError = details.originalError;
    }

    hasValidationErrors() {
        return !!this.errors;
    }

    getValidationErrors() {
        return this.errors || {};
    }

    getFieldError(field) {
        return this.errors?.[field]?.[0] || null;
    }
}

/**
 * Export for use in modules
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CMISApiClient, APIError };
}

// Export for browser global use
if (typeof window !== 'undefined') {
    window.CMISApiClient = CMISApiClient;
    window.APIError = APIError;
}

/**
 * Usage Examples:
 *
 * // Initialize client
 * const api = new CMISApiClient({
 *     baseUrl: '/api',
 *     token: 'your-auth-token',
 *     orgId: 'your-org-id',
 *     onError: (error) => console.error('API Error:', error),
 * });
 *
 * // List campaigns
 * const campaigns = await api.campaigns.list({ status: 'active' });
 *
 * // Create content plan
 * const plan = await api.contentPlans.create({
 *     campaign_id: 'uuid',
 *     name: 'Q4 Campaign',
 *     channels: ['facebook', 'instagram'],
 * });
 *
 * // Generate content
 * const result = await api.contentPlans.generate(plan.data.plan_id, {
 *     prompt: 'Create engaging social media post',
 *     async: true,
 * });
 *
 * // GPT conversation
 * const session = await api.gpt.conversation.getSession();
 * const response = await api.gpt.conversation.sendMessage(
 *     session.data.session_id,
 *     'What are my top performing campaigns?'
 * );
 *
 * // Handle errors
 * try {
 *     await api.campaigns.create({});
 * } catch (error) {
 *     if (error.hasValidationErrors()) {
 *         console.log('Validation errors:', error.getValidationErrors());
 *     }
 * }
 */
