#!/bin/bash

# =============================================================
# CMIS Safe Artisan Scheduler Installer
# إعداد مهمة cron لتشغيل php artisan schedule:run كل دقيقة
# =============================================================

CRON_FILE="/etc/cron.d/cmis_artisan_scheduler"
CHROOT_PATH="/var/www/vhosts/cmis.marketing.limited"
PHP_PATH="/opt/plesk/php/8.3/bin/php"
ARTISAN_PATH="/httpdocs/artisan"
LOG_FILE="/logs/cron.log"

# إنشاء ملف cron الجديد (أو تحديثه إذا كان موجودًا)
echo "* * * * * root chroot ${CHROOT_PATH} /bin/bash -lc '${PHP_PATH} ${ARTISAN_PATH} schedule:run >> ${LOG_FILE} 2>&1'" > ${CRON_FILE}

# ضبط الأذونات الصحيحة
chmod 644 ${CRON_FILE}
chown root:root ${CRON_FILE}

# إعادة تحميل خدمة cron
if command -v systemctl >/dev/null 2>&1; then
    systemctl reload crond || systemctl restart cron
else
    service cron reload || service crond reload
fi

echo "✅ تم إنشاء مهمة Artisan Scheduler بنجاح!"
echo "📅 سيتم تشغيل المهام المجدولة كل دقيقة داخل بيئة chroot: ${CHROOT_PATH}"
echo "📜 سجل التنفيذ: ${CHROOT_PATH}${LOG_FILE}"
