@extends('layouts.admin')

@section('title', 'مركز الذكاء الاصطناعي')

@section('content')
<div x-data="aiCenterManager()" x-init="init()">

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">مركز الذكاء الاصطناعي</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">توليد المحتوى، التوصيات الذكية، والبحث الدلالي</p>
        </div>
        <div class="flex space-x-3 space-x-reverse">
            <x-ui.button @click="showGenerateModal = true" variant="primary" icon="fas fa-magic">
                توليد محتوى جديد
            </x-ui.button>
            <x-ui.button @click="showSearchModal = true" variant="secondary" icon="fas fa-search">
                البحث الدلالي
            </x-ui.button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">محتوى مولد</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.generatedContent"></p>
                </div>
                <i class="fas fa-robot text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-arrow-up ml-1"></i>
                <span x-text="stats.contentChange + '% هذا الأسبوع'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">توصيات نشطة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.activeRecommendations"></p>
                </div>
                <i class="fas fa-lightbulb text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-check-circle ml-1"></i>
                <span x-text="stats.appliedRecommendations + ' مطبقة'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">حملات AI</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.aiCampaigns"></p>
                </div>
                <i class="fas fa-bullhorn text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-chart-line ml-1"></i>
                <span x-text="stats.campaignSuccess + '% معدل النجاح'"></span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm opacity-90">مستندات معالجة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.processedDocs"></p>
                </div>
                <i class="fas fa-file-alt text-4xl opacity-50"></i>
            </div>
            <div class="flex items-center text-sm">
                <i class="fas fa-database ml-1"></i>
                <span x-text="stats.vectorsStored + ' متجه'"></span>
            </div>
        </div>
    </div>

    <!-- AI Services Status -->
    <x-ui.card title="حالة خدمات الذكاء الاصطناعي" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <template x-for="service in aiServices" :key="service.id">
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <i :class="service.icon + ' text-2xl text-' + service.color + '-600'"></i>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white" x-text="service.name"></h4>
                                <p class="text-xs text-gray-500" x-text="service.provider"></p>
                            </div>
                        </div>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full"
                              :class="service.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                              x-text="service.status === 'active' ? 'نشط' : 'متوقف'"></span>
                    </div>
                    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex justify-between">
                            <span>الطلبات اليوم:</span>
                            <span class="font-semibold" x-text="service.requests"></span>
                        </div>
                        <div class="flex justify-between">
                            <span>وقت الاستجابة:</span>
                            <span class="font-semibold" x-text="service.responseTime"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </x-ui.card>

    <!-- Content Generation & Recommendations -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Recent Generated Content -->
        <x-ui.card title="آخر المحتوى المولد">
            <div class="space-y-3">
                <template x-for="content in recentContent" :key="content.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 space-x-reverse mb-1">
                                    <span class="px-2 py-1 text-xs font-semibold rounded"
                                          :class="getContentTypeClass(content.type)"
                                          x-text="content.type"></span>
                                    <span class="text-xs text-gray-500" x-text="content.timeAgo"></span>
                                </div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1" x-text="content.title"></h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2" x-text="content.preview"></p>
                            </div>
                            <button @click="viewContent(content.id)"
                                    class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>
                                <i class="fas fa-language ml-1"></i>
                                <span x-text="content.language"></span>
                            </span>
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <button @click="editContent(content.id)" class="hover:text-blue-600">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="copyContent(content.id)" class="hover:text-green-600">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button @click="useContent(content.id)" class="hover:text-purple-600">
                                    <i class="fas fa-check-circle"></i> استخدام
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <!-- AI Recommendations -->
        <x-ui.card title="التوصيات الذكية">
            <div class="space-y-3">
                <template x-for="rec in recommendations" :key="rec.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 space-x-reverse mb-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded"
                                          :class="rec.priority === 'high' ? 'bg-red-100 text-red-800' : rec.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'"
                                          x-text="rec.priority === 'high' ? 'عالي' : rec.priority === 'medium' ? 'متوسط' : 'منخفض'"></span>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <i class="fas fa-chart-line ml-1"></i>
                                        <span x-text="'التأثير المتوقع: +' + rec.impact + '%'"></span>
                                    </div>
                                </div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1" x-text="rec.title"></h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="rec.description"></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-3">
                            <div class="flex items-center text-xs text-gray-500">
                                <i class="fas fa-brain ml-1"></i>
                                <span x-text="'الثقة: ' + rec.confidence + '%'"></span>
                            </div>
                            <div class="flex space-x-2 space-x-reverse">
                                <x-ui.button @click="applyRecommendation(rec.id)" size="sm" variant="success">
                                    تطبيق
                                </x-ui.button>
                                <x-ui.button @click="dismissRecommendation(rec.id)" size="sm" variant="secondary">
                                    تجاهل
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>
    </div>

    <!-- AI Models & Knowledge Base -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- AI Models -->
        <x-ui.card title="النماذج المتاحة">
            <div class="space-y-3">
                <template x-for="model in aiModels" :key="model.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white" x-text="model.name"></h4>
                                <p class="text-xs text-gray-500" x-text="model.family"></p>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                  :class="model.status === 'trained' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                  x-text="model.status === 'trained' ? 'مدرب' : 'قيد التدريب'"></span>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>الدقة:</span>
                                <span class="font-semibold" x-text="model.accuracy + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" :style="'width: ' + model.accuracy + '%'"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500">
                                <span x-text="'آخر تدريب: ' + model.lastTrained"></span>
                                <span x-text="model.predictions + ' تنبؤ'"></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <!-- Knowledge Base Browser -->
        <x-ui.card title="قاعدة المعرفة">
            <div class="mb-4">
                <div class="relative">
                    <input type="text"
                           x-model="knowledgeSearch"
                           @input="searchKnowledge()"
                           placeholder="ابحث في قاعدة المعرفة..."
                           class="w-full px-4 py-2 pr-10 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                <template x-for="doc in knowledgeDocs" :key="doc.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition cursor-pointer"
                         @click="openDocument(doc.id)">
                        <div class="flex items-start space-x-3 space-x-reverse">
                            <i :class="doc.icon + ' text-xl text-gray-400 mt-1'"></i>
                            <div class="flex-1">
                                <h5 class="font-semibold text-gray-900 dark:text-white text-sm" x-text="doc.title"></h5>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2" x-text="doc.excerpt"></p>
                                <div class="flex items-center space-x-3 space-x-reverse mt-2 text-xs text-gray-500">
                                    <span>
                                        <i class="fas fa-tag ml-1"></i>
                                        <span x-text="doc.category"></span>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock ml-1"></i>
                                        <span x-text="doc.updatedAt"></span>
                                    </span>
                                    <span class="text-purple-600">
                                        <i class="fas fa-star ml-1"></i>
                                        <span x-text="doc.relevance + '%'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>
    </div>

    <!-- Semantic Search Results -->
    <x-ui.card title="نتائج البحث الدلالي" x-show="semanticResults.length > 0">
        <div class="space-y-3">
            <template x-for="result in semanticResults" :key="result.id">
                <div class="border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 space-x-reverse mb-2">
                                <span class="text-sm font-semibold text-purple-700 dark:text-purple-400" x-text="result.source"></span>
                                <span class="px-2 py-1 text-xs bg-purple-200 dark:bg-purple-800 text-purple-800 dark:text-purple-200 rounded" x-text="'تطابق: ' + result.similarity + '%'"></span>
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1" x-text="result.title"></h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300" x-text="result.content"></p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-3 text-xs">
                        <span class="text-gray-500" x-text="result.timestamp"></span>
                        <button @click="useSemanticResult(result.id)" class="text-purple-600 hover:text-purple-700 font-semibold">
                            <i class="fas fa-plus-circle ml-1"></i>
                            استخدام في الحملة
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-ui.card>

    <!-- Generate Content Modal -->
    <x-ui.modal name="generateModal" title="توليد محتوى جديد" max-width="lg" x-show="showGenerateModal" @close="showGenerateModal = false">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">نوع المحتوى</label>
                <select x-model="generateForm.contentType"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="ad_copy">نص إعلاني</option>
                    <option value="social_post">منشور سوشيال ميديا</option>
                    <option value="email">بريد إلكتروني</option>
                    <option value="landing_page">صفحة هبوط</option>
                    <option value="video_script">سكريبت فيديو</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">الموضوع / المنتج</label>
                <input type="text"
                       x-model="generateForm.topic"
                       placeholder="مثال: حملة ترويجية لمنتج جديد..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">الهدف التسويقي</label>
                <select x-model="generateForm.objective"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="awareness">زيادة الوعي</option>
                    <option value="consideration">التفكير بالمنتج</option>
                    <option value="conversion">التحويل والمبيعات</option>
                    <option value="engagement">زيادة التفاعل</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">اللغة</label>
                <div class="grid grid-cols-2 gap-3">
                    <button @click="generateForm.language = 'ar'"
                            :class="generateForm.language === 'ar' ? 'bg-purple-100 border-purple-600 text-purple-700' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600'"
                            class="px-4 py-2 border-2 rounded-lg font-semibold">
                        العربية
                    </button>
                    <button @click="generateForm.language = 'en'"
                            :class="generateForm.language === 'en' ? 'bg-purple-100 border-purple-600 text-purple-700' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600'"
                            class="px-4 py-2 border-2 rounded-lg font-semibold">
                        English
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">الأسلوب</label>
                <select x-model="generateForm.tone"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="professional">احترافي</option>
                    <option value="friendly">ودي</option>
                    <option value="persuasive">مقنع</option>
                    <option value="casual">غير رسمي</option>
                    <option value="urgent">عاجل</option>
                </select>
            </div>
        </div>
        <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
            <x-ui.button @click="showGenerateModal = false" variant="secondary">
                إلغاء
            </x-ui.button>
            <x-ui.button @click="generateContent()" variant="primary" icon="fas fa-magic">
                توليد المحتوى
            </x-ui.button>
        </div>
    </x-ui.modal>

    <!-- Semantic Search Modal -->
    <x-ui.modal name="searchModal" title="البحث الدلالي المتقدم" max-width="lg" x-show="showSearchModal" @close="showSearchModal = false">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">استعلام البحث</label>
                <textarea x-model="searchForm.query"
                          rows="4"
                          placeholder="اكتب سؤالك أو استفسارك هنا... سيقوم النظام بالبحث الدلالي في قاعدة المعرفة"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">البحث في</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="searchForm.sources" value="campaigns" class="rounded text-blue-600">
                        <span class="mr-2 text-sm">الحملات السابقة</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="searchForm.sources" value="documents" class="rounded text-blue-600">
                        <span class="mr-2 text-sm">المستندات</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="searchForm.sources" value="analytics" class="rounded text-blue-600">
                        <span class="mr-2 text-sm">بيانات التحليلات</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="searchForm.sources" value="knowledge" class="rounded text-blue-600">
                        <span class="mr-2 text-sm">قاعدة المعرفة</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="mt-6 flex justify-end space-x-3 space-x-reverse">
            <x-ui.button @click="showSearchModal = false" variant="secondary">
                إلغاء
            </x-ui.button>
            <x-ui.button @click="performSemanticSearch()" variant="primary" icon="fas fa-search">
                بحث
            </x-ui.button>
        </div>
    </x-ui.modal>

</div>
@endsection

@push('scripts')
<script>
function aiCenterManager() {
    return {
        showGenerateModal: false,
        showSearchModal: false,
        stats: {
            generatedContent: 0,
            contentChange: 0,
            activeRecommendations: 0,
            appliedRecommendations: 0,
            aiCampaigns: 0,
            campaignSuccess: 0,
            processedDocs: 0,
            vectorsStored: 0
        },
        aiServices: [],
        recentContent: [],
        recommendations: [],
        aiModels: [],
        knowledgeDocs: [],
        knowledgeSearch: '',
        semanticResults: [],
        generateForm: {
            contentType: 'ad_copy',
            topic: '',
            objective: 'awareness',
            language: 'ar',
            tone: 'professional'
        },
        searchForm: {
            query: '',
            sources: ['campaigns', 'documents', 'knowledge']
        },

        async init() {
            await this.fetchData();
        },

        async fetchData() {
            try {
                // TODO: Backend Controller Needed - AIGenerationController
                // This page requires a new controller for AI content generation and semantic search
                // Required API endpoints:
                // - GET /api/ai/dashboard - Get AI stats and service status
                // - POST /api/ai/generate - Generate content using AI (Gemini/GPT-4)
                // - POST /api/ai/semantic-search - Perform semantic search with pgvector
                // - GET /api/ai/recommendations - Get AI-powered recommendations
                // - GET /api/ai/models - Get AI model performance data
                // - GET /api/ai/knowledge - Get knowledge base documents
                // - POST /api/ai/knowledge/process - Process and vectorize documents
                // - GET /api/ai/content/history - Get recently generated content

                // Simulated data until backend is implemented
                this.stats = {
                    generatedContent: 1247,
                    contentChange: 28.5,
                    activeRecommendations: 34,
                    appliedRecommendations: 89,
                    aiCampaigns: 156,
                    campaignSuccess: 87.3,
                    processedDocs: 2843,
                    vectorsStored: 45620
                };

                this.aiServices = [
                    { id: 1, name: 'Gemini Pro', provider: 'Google AI', icon: 'fas fa-brain', color: 'purple', status: 'active', requests: 1523, responseTime: '1.2s' },
                    { id: 2, name: 'GPT-4', provider: 'OpenAI', icon: 'fas fa-robot', color: 'blue', status: 'active', requests: 2341, responseTime: '0.8s' },
                    { id: 3, name: 'PgVector', provider: 'PostgreSQL', icon: 'fas fa-database', color: 'green', status: 'active', requests: 5672, responseTime: '0.3s' }
                ];

                this.recentContent = [
                    { id: 1, type: 'نص إعلاني', title: 'حملة الجمعة البيضاء', preview: 'اكتشف عروضنا الحصرية! خصم يصل إلى 70% على جميع المنتجات...', language: 'العربية', timeAgo: 'منذ ساعة' },
                    { id: 2, type: 'منشور سوشيال', title: 'إطلاق المنتج الجديد', preview: 'نحن متحمسون للإعلان عن منتجنا الثوري الجديد...', language: 'English', timeAgo: 'منذ 3 ساعات' },
                    { id: 3, type: 'بريد إلكتروني', title: 'رسالة ترحيبية', preview: 'مرحباً بك في عائلتنا! نحن سعداء بانضمامك...', language: 'العربية', timeAgo: 'منذ 5 ساعات' },
                    { id: 4, type: 'سكريبت فيديو', title: 'فيديو توضيحي', preview: '[المشهد الافتتاحي] شخص يواجه مشكلة شائعة...', language: 'العربية', timeAgo: 'منذ يوم' }
                ];

                this.recommendations = [
                    { id: 1, priority: 'high', title: 'زيادة الميزانية لحملة Meta', description: 'الحملة تحقق ROAS 5.2x، يُنصح بزيادة الميزانية بنسبة 30%', impact: 45, confidence: 92 },
                    { id: 2, priority: 'medium', title: 'تحسين استهداف الجمهور', description: 'الفئة العمرية 25-34 تظهر أعلى معدل تحويل، قم بزيادة التركيز', impact: 28, confidence: 85 },
                    { id: 3, priority: 'high', title: 'تغيير وقت النشر', description: 'البيانات تظهر أن النشر بين 8-10 مساءً يحقق أفضل نتائج', impact: 35, confidence: 88 },
                    { id: 4, priority: 'low', title: 'اختبار نصوص إعلانية جديدة', description: 'معدل النقر CTR يمكن تحسينه باستخدام نداء أقوى للعمل', impact: 15, confidence: 72 }
                ];

                this.aiModels = [
                    { id: 1, name: 'CTR Predictor', family: 'Random Forest', status: 'trained', accuracy: 94.2, lastTrained: 'منذ يومين', predictions: 15234 },
                    { id: 2, name: 'Budget Optimizer', family: 'Neural Network', status: 'trained', accuracy: 89.7, lastTrained: 'منذ أسبوع', predictions: 8956 },
                    { id: 3, name: 'Audience Segmenter', family: 'K-Means Clustering', status: 'training', accuracy: 76.3, lastTrained: 'قيد التدريب', predictions: 3421 }
                ];

                this.knowledgeDocs = [
                    { id: 1, title: 'دليل أفضل ممارسات إعلانات Meta', excerpt: 'استراتيجيات متقدمة لتحسين أداء حملات Meta Ads...', category: 'إعلانات', icon: 'fas fa-file-pdf', updatedAt: 'منذ 3 أيام', relevance: 95 },
                    { id: 2, title: 'تقرير اتجاهات السوق Q4 2025', excerpt: 'تحليل شامل لاتجاهات التسويق الرقمي في الربع الأخير...', category: 'تقارير', icon: 'fas fa-chart-line', updatedAt: 'منذ أسبوع', relevance: 88 },
                    { id: 3, title: 'دراسة حالة: حملة ناجحة', excerpt: 'كيف حققت شركة XYZ زيادة 300% في المبيعات...', category: 'دراسات', icon: 'fas fa-lightbulb', updatedAt: 'منذ أسبوعين', relevance: 82 }
                ];

            } catch (error) {
                console.error(error);
                window.notify('فشل تحميل البيانات', 'error');
            }
        },

        getContentTypeClass(type) {
            const classes = {
                'نص إعلاني': 'bg-blue-100 text-blue-800',
                'منشور سوشيال': 'bg-green-100 text-green-800',
                'بريد إلكتروني': 'bg-purple-100 text-purple-800',
                'صفحة هبوط': 'bg-yellow-100 text-yellow-800',
                'سكريبت فيديو': 'bg-red-100 text-red-800'
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
        },

        async generateContent() {
            if (!this.generateForm.topic) {
                window.notify('الرجاء إدخال الموضوع', 'warning');
                return;
            }

            try {
                window.notify('جاري توليد المحتوى...', 'info');

                // TODO: Implement actual AI content generation API call
                // const response = await fetch('/api/ai/generate', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     },
                //     body: JSON.stringify({
                //         content_type: this.generateForm.contentType,
                //         topic: this.generateForm.topic,
                //         objective: this.generateForm.objective,
                //         language: this.generateForm.language,
                //         tone: this.generateForm.tone,
                //         model: 'gemini-pro' // or 'gpt-4'
                //     })
                // });
                //
                // if (!response.ok) {
                //     const error = await response.json();
                //     throw new Error(error.message || 'Failed to generate content');
                // }
                //
                // const result = await response.json();
                // // Display generated content to user
                // // result.content, result.model_used, result.tokens_used

                // Simulate API delay
                await new Promise(resolve => setTimeout(resolve, 2000));

                window.notify('تم توليد المحتوى بنجاح!', 'success');
                this.showGenerateModal = false;
                this.generateForm = {
                    contentType: 'ad_copy',
                    topic: '',
                    objective: 'awareness',
                    language: 'ar',
                    tone: 'professional'
                };
                await this.fetchData();
            } catch (error) {
                console.error('Error generating content:', error);
                window.notify(error.message || 'فشل توليد المحتوى', 'error');
            }
        },

        async performSemanticSearch() {
            if (!this.searchForm.query) {
                window.notify('الرجاء إدخال استعلام البحث', 'warning');
                return;
            }

            try {
                window.notify('جاري البحث...', 'info');

                // TODO: Implement actual semantic search API call using pgvector
                // const response = await fetch('/api/ai/semantic-search', {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/json',
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     },
                //     body: JSON.stringify({
                //         query: this.searchForm.query,
                //         sources: this.searchForm.sources,
                //         limit: 10,
                //         threshold: 0.7 // similarity threshold
                //     })
                // });
                //
                // if (!response.ok) {
                //     const error = await response.json();
                //     throw new Error(error.message || 'Failed to perform search');
                // }
                //
                // const results = await response.json();
                // this.semanticResults = results.items; // Array of similar documents/campaigns

                // Simulate API delay
                await new Promise(resolve => setTimeout(resolve, 1500));

                this.semanticResults = [
                    { id: 1, source: 'حملة سابقة', title: 'حملة الصيف 2024', content: 'استخدمنا استهداف متقدم للجمهور وحققنا نتائج ممتازة...', similarity: 94, timestamp: 'يوليو 2024' },
                    { id: 2, source: 'قاعدة المعرفة', title: 'دليل الاستهداف', content: 'أفضل الممارسات في استهداف الجمهور تتضمن...', similarity: 87, timestamp: 'سبتمبر 2024' }
                ];

                window.notify('تم العثور على ' + this.semanticResults.length + ' نتيجة', 'success');
                this.showSearchModal = false;
            } catch (error) {
                window.notify('فشل البحث', 'error');
            }
        },

        searchKnowledge() {
            // API Integration Point: GET /api/ai/knowledge/search?q={query}
            console.log('Searching knowledge base:', this.knowledgeSearch);
        },

        viewContent(id) {
            window.notify('عرض المحتوى #' + id, 'info');
        },

        editContent(id) {
            window.notify('تحرير المحتوى #' + id, 'info');
        },

        copyContent(id) {
            window.notify('تم نسخ المحتوى', 'success');
        },

        useContent(id) {
            window.notify('تم إضافة المحتوى إلى الحملة', 'success');
        },

        applyRecommendation(id) {
            window.notify('جاري تطبيق التوصية...', 'info');
            // API Integration Point: POST /api/ai/recommendations/{id}/apply
        },

        dismissRecommendation(id) {
            this.recommendations = this.recommendations.filter(r => r.id !== id);
            window.notify('تم تجاهل التوصية', 'success');
        },

        openDocument(id) {
            window.notify('فتح المستند #' + id, 'info');
        },

        useSemanticResult(id) {
            window.notify('تم إضافة النتيجة إلى الحملة', 'success');
        }
    };
}
</script>
@endpush
