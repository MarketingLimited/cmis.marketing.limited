#!/usr/bin/env python3
"""
CMIS Embeddings Service - خدمة الـ Embeddings الحقيقية
يربط قاعدة البيانات CMIS مع Google Gemini API لإنشاء embeddings حقيقية
"""

import os
import sys
import json
import hashlib
import asyncio
import logging
from datetime import datetime, timedelta
from typing import List, Dict, Optional, Tuple
import asyncpg
import google.generativeai as genai
from google.generativeai import GenerativeModel
import numpy as np

# ================================================================
# التكوين والإعدادات
# ================================================================

class Config:
    """إعدادات الخدمة"""
    
    # قاعدة البيانات
    DB_HOST = os.getenv('DB_HOST', 'localhost')
    DB_PORT = os.getenv('DB_PORT', '5432')
    DB_NAME = os.getenv('DB_NAME', 'cmis')
    DB_USER = os.getenv('DB_USER', 'begin')
    DB_PASSWORD = os.getenv('DB_PASSWORD', 'your_password')
    
    # Gemini API
    GEMINI_API_KEY = os.getenv('GEMINI_API_KEY', 'YOUR_API_KEY_HERE')
    EMBEDDING_MODEL = 'models/text-embedding-004'  # أحدث نموذج
    
    # إعدادات الأداء
    BATCH_SIZE = 20  # عدد النصوص في الدفعة الواحدة
    MAX_RETRIES = 3
    RETRY_DELAY = 2  # ثواني
    RATE_LIMIT_DELAY = 1  # تأخير بين الطلبات (ثانية)
    
    # التنظيف
    CACHE_EXPIRY_DAYS = 30
    
    @classmethod
    def get_db_url(cls):
        return f"postgresql://{cls.DB_USER}:{cls.DB_PASSWORD}@{cls.DB_HOST}:{cls.DB_PORT}/{cls.DB_NAME}"

# ================================================================
# إعداد السجلات
# ================================================================

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('cmis_embeddings.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# ================================================================
# خدمة الـ Embeddings
# ================================================================

class EmbeddingsService:
    """خدمة إنشاء وإدارة الـ embeddings"""
    
    def __init__(self):
        self.db_pool = None
        self.genai_model = None
        
    async def initialize(self):
        """تهيئة الاتصالات"""
        try:
            # الاتصال بقاعدة البيانات
            self.db_pool = await asyncpg.create_pool(
                Config.get_db_url(),
                min_size=2,
                max_size=10,
                command_timeout=60
            )
            logger.info("تم الاتصال بقاعدة البيانات")
            
            # تهيئة Gemini API
            genai.configure(api_key=Config.GEMINI_API_KEY)
            self.genai_model = GenerativeModel(Config.EMBEDDING_MODEL)
            logger.info(f"تم تهيئة Gemini API مع نموذج {Config.EMBEDDING_MODEL}")
            
        except Exception as e:
            logger.error(f"خطأ في التهيئة: {e}")
            raise
    
    async def close(self):
        """إغلاق الاتصالات"""
        if self.db_pool:
            await self.db_pool.close()
    
    # ================================================================
    # دوال Embeddings
    # ================================================================
    
    async def generate_embedding(self, text: str, retry_count: int = 0) -> Optional[List[float]]:
        """
        إنشاء embedding لنص واحد باستخدام Gemini API
        """
        if not text or not text.strip():
            return None
        
        try:
            # استدعاء Gemini API
            result = await asyncio.to_thread(
                self.genai_model.embed_content,
                content=text,
                task_type="RETRIEVAL_DOCUMENT",  # نوع المهمة
                title=None
            )
            
            # الحصول على الـ embedding
            embedding = result['embedding']
            
            # التأكد من الحجم الصحيح (768 بُعد)
            if len(embedding) != 768:
                # تعديل الحجم إذا لزم (Gemini قد يُرجع أحجام مختلفة)
                embedding = self._resize_embedding(embedding, 768)
            
            return embedding
            
        except Exception as e:
            logger.error(f"خطأ في إنشاء embedding: {e}")
            
            # إعادة المحاولة
            if retry_count < Config.MAX_RETRIES:
                await asyncio.sleep(Config.RETRY_DELAY * (retry_count + 1))
                return await self.generate_embedding(text, retry_count + 1)
            
            return None
    
    def _resize_embedding(self, embedding: List[float], target_size: int) -> List[float]:
        """تغيير حجم الـ embedding للحجم المطلوب"""
        current_size = len(embedding)
        
        if current_size == target_size:
            return embedding
        elif current_size < target_size:
            # إضافة أصفار
            return embedding + [0.0] * (target_size - current_size)
        else:
            # اقتطاع
            return embedding[:target_size]
    
    async def generate_batch_embeddings(self, texts: List[str]) -> List[Optional[List[float]]]:
        """إنشاء embeddings لمجموعة نصوص"""
        embeddings = []
        
        for text in texts:
            # تأخير لتجنب تجاوز حد المعدل
            await asyncio.sleep(Config.RATE_LIMIT_DELAY)
            
            embedding = await self.generate_embedding(text)
            embeddings.append(embedding)
            
        return embeddings
    
    # ================================================================
    # دوال قاعدة البيانات
    # ================================================================
    
    async def get_texts_without_embeddings(self, limit: int = 100) -> List[Tuple[str, str]]:
        """
        الحصول على النصوص التي تحتاج embeddings
        Returns: قائمة من (knowledge_id, text)
        """
        query = """
            SELECT 
                ki.knowledge_id::text,
                COALESCE(ki.topic, '') || ' ' || 
                COALESCE(array_to_string(ki.keywords, ' '), '') || ' ' ||
                COALESCE(km.content, '') as full_text
            FROM cmis_knowledge.index ki
            LEFT JOIN cmis_knowledge.marketing km ON km.knowledge_id = ki.knowledge_id
            WHERE ki.topic_embedding IS NULL
               OR ki.semantic_fingerprint IS NULL
            LIMIT $1
        """
        
        async with self.db_pool.acquire() as conn:
            rows = await conn.fetch(query, limit)
            return [(row['knowledge_id'], row['full_text']) for row in rows]
    
    async def save_embedding(self, text: str, embedding: List[float], provider: str = 'gemini'):
        """حفظ embedding في جدول الـ cache"""
        text_hash = hashlib.sha256(text.encode()).hexdigest()
        
        # تحويل القائمة إلى تنسيق PostgreSQL vector
        embedding_str = f"[{','.join(map(str, embedding))}]"
        
        query = """
            INSERT INTO cmis_knowledge.embeddings_cache 
            (input_text, embedding, provider)
            VALUES ($1, $2::vector(768), $3)
            ON CONFLICT (input_hash) DO UPDATE
            SET embedding = EXCLUDED.embedding,
                provider = EXCLUDED.provider,
                last_used_at = CURRENT_TIMESTAMP
        """
        
        async with self.db_pool.acquire() as conn:
            await conn.execute(query, text, embedding_str, provider)
    
    async def update_knowledge_embeddings(self, knowledge_id: str, embedding: List[float]):
        """تحديث embeddings في جدول المعرفة"""
        embedding_str = f"[{','.join(map(str, embedding))}]"
        
        query = """
            UPDATE cmis_knowledge.index
            SET topic_embedding = $1::vector(768),
                semantic_fingerprint = $1::vector(768),
                last_verified_at = CURRENT_TIMESTAMP
            WHERE knowledge_id = $2::uuid
        """
        
        async with self.db_pool.acquire() as conn:
            await conn.execute(query, embedding_str, knowledge_id)
    
    async def cleanup_old_cache(self):
        """تنظيف الـ cache القديم"""
        query = """
            DELETE FROM cmis_knowledge.embeddings_cache
            WHERE last_used_at < CURRENT_TIMESTAMP - INTERVAL '%s days'
              AND provider != 'manual'
        """
        
        async with self.db_pool.acquire() as conn:
            deleted = await conn.execute(query % Config.CACHE_EXPIRY_DAYS)
            logger.info(f"تم حذف {deleted} سجل قديم من الـ cache")
    
    # ================================================================
    # العملية الرئيسية
    # ================================================================
    
    async def process_pending_embeddings(self):
        """معالجة جميع النصوص التي تحتاج embeddings"""
        logger.info("بدء معالجة الـ embeddings المعلقة...")
        
        total_processed = 0
        
        while True:
            # الحصول على دفعة من النصوص
            texts_data = await self.get_texts_without_embeddings(Config.BATCH_SIZE)
            
            if not texts_data:
                break
            
            logger.info(f"معالجة {len(texts_data)} نص...")
            
            for knowledge_id, text in texts_data:
                if not text.strip():
                    continue
                
                # إنشاء embedding
                embedding = await self.generate_embedding(text)
                
                if embedding:
                    # حفظ في الـ cache
                    await self.save_embedding(text, embedding)
                    
                    # تحديث جدول المعرفة
                    await self.update_knowledge_embeddings(knowledge_id, embedding)
                    
                    total_processed += 1
                    logger.info(f"تم معالجة {total_processed} نص")
        
        logger.info(f"انتهى! تم معالجة {total_processed} نص إجمالاً")
    
    async def run_continuous(self):
        """تشغيل مستمر مع معالجة دورية"""
        while True:
            try:
                # معالجة الـ embeddings المعلقة
                await self.process_pending_embeddings()
                
                # تنظيف الـ cache القديم
                await self.cleanup_old_cache()
                
                # انتظار ساعة قبل الدورة التالية
                logger.info("انتظار ساعة قبل الدورة التالية...")
                await asyncio.sleep(3600)
                
            except Exception as e:
                logger.error(f"خطأ في الدورة: {e}")
                await asyncio.sleep(60)  # انتظار دقيقة عند الخطأ

# ================================================================
# دالة SQL لاستدعاء الـ embeddings من Python
# ================================================================

CREATE_FUNCTION_SQL = """
-- دالة محسنة تستدعي Python للحصول على embeddings حقيقية
CREATE OR REPLACE FUNCTION cmis_knowledge.generate_embedding_real(input_text text)
RETURNS vector
LANGUAGE plpython3u
AS $$
import hashlib
import json
import requests
import time

# التحقق من الـ cache أولاً
cache_query = """
    SELECT embedding::text 
    FROM cmis_knowledge.embeddings_cache
    WHERE input_hash = %s
    LIMIT 1
"""
text_hash = hashlib.sha256(input_text.encode()).hexdigest()
result = plpy.execute(cache_query, [text_hash])

if result:
    # إرجاع من الـ cache
    plpy.execute("""
        UPDATE cmis_knowledge.embeddings_cache
        SET last_used_at = CURRENT_TIMESTAMP
        WHERE input_hash = %s
    """, [text_hash])
    return result[0]['embedding']

# إذا لم يوجد في الـ cache، استخدم الدالة المؤقتة
# (سيتم استبدالها بـ API call عند تشغيل الخدمة)
fallback_query = """
    SELECT cmis_knowledge.generate_embedding_improved(%s)::text as embedding
"""
result = plpy.execute(fallback_query, [input_text])
return result[0]['embedding']
$$;
"""

# ================================================================
# نقطة الدخول الرئيسية
# ================================================================

async def main():
    """الدالة الرئيسية"""
    service = EmbeddingsService()
    
    try:
        # التهيئة
        await service.initialize()
        
        # تثبيت دالة Python في قاعدة البيانات
        async with service.db_pool.acquire() as conn:
            try:
                await conn.execute(CREATE_FUNCTION_SQL)
                logger.info("تم تثبيت دالة Python في قاعدة البيانات")
            except Exception as e:
                logger.warning(f"الدالة موجودة بالفعل أو خطأ: {e}")
        
        # اختيار وضع التشغيل
        if len(sys.argv) > 1 and sys.argv[1] == '--once':
            # تشغيل مرة واحدة
            await service.process_pending_embeddings()
        else:
            # تشغيل مستمر
            logger.info("بدء الخدمة في وضع التشغيل المستمر...")
            await service.run_continuous()
            
    except KeyboardInterrupt:
        logger.info("إيقاف الخدمة...")
    except Exception as e:
        logger.error(f"خطأ غير متوقع: {e}")
    finally:
        await service.close()

if __name__ == "__main__":
    # تشغيل الخدمة
    asyncio.run(main())

# ================================================================
# تعليمات الاستخدام
# ================================================================
"""
1. تثبيت المتطلبات:
   pip install asyncpg google-generativeai numpy

2. تعيين متغيرات البيئة:
   export DB_PASSWORD="your_password"
   export GEMINI_API_KEY="your_gemini_api_key"

3. تشغيل مرة واحدة:
   python embeddings_service.py --once

4. تشغيل كخدمة مستمرة:
   python embeddings_service.py

5. تشغيل كـ systemd service:
   - انسخ الملف إلى /opt/cmis/
   - أنشئ ملف /etc/systemd/system/cmis-embeddings.service
   - systemctl enable cmis-embeddings
   - systemctl start cmis-embeddings
"""
