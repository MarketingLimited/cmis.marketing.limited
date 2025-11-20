<?php

namespace App\Models\Knowledge;

// Alias to KnowledgeIndex - the actual knowledge base table is cmis_knowledge.index
class_alias(\App\Models\Knowledge\KnowledgeIndex::class, 'App\Models\Knowledge\KnowledgeBase');
