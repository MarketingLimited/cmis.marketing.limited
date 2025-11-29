/**
 * Publish Modal - Scheduling Management Module
 * Handles calendar, scheduling, best times, and queue functionality
 */

export function getSchedulingManagementMethods() {
    return {
        // ============================================
        // CALENDAR VIEW METHODS
        // ============================================

        getCalendarDays() {
            const year = this.calendarYear || new Date().getFullYear();
            const month = this.calendarMonth !== undefined ? this.calendarMonth : new Date().getMonth();
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();

            const days = [];
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Previous month days
            const prevMonthLastDay = new Date(year, month, 0).getDate();
            for (let i = startingDayOfWeek - 1; i >= 0; i--) {
                const dayNumber = prevMonthLastDay - i;
                const date = new Date(year, month - 1, dayNumber);
                days.push({
                    dayNumber,
                    date: date.toISOString().split('T')[0],
                    isCurrentMonth: false,
                    isToday: false,
                    posts: this.getPostsForDate(date)
                });
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dateStr = date.toISOString().split('T')[0];
                const todayStr = today.toISOString().split('T')[0];
                days.push({
                    dayNumber: day,
                    date: dateStr,
                    isCurrentMonth: true,
                    isToday: dateStr === todayStr,
                    posts: this.getPostsForDate(date)
                });
            }

            // Next month days
            const remainingDays = 42 - days.length;
            for (let day = 1; day <= remainingDays; day++) {
                const date = new Date(year, month + 1, day);
                days.push({
                    dayNumber: day,
                    date: date.toISOString().split('T')[0],
                    isCurrentMonth: false,
                    isToday: false,
                    posts: this.getPostsForDate(date)
                });
            }

            return days;
        },

        getPostsForDate(date) {
            if (!this.scheduledPosts || !Array.isArray(this.scheduledPosts)) {
                return [];
            }
            const dateStr = date.toISOString().split('T')[0];
            return this.scheduledPosts.filter(post => post.scheduled_date === dateStr);
        },

        async loadScheduledPosts() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/posts-scheduled?month=${this.calendarMonth + 1}&year=${this.calendarYear}`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const data = await response.json();
                this.scheduledPosts = data.data || data.posts || [];
            } catch (error) {
                console.error('Failed to load scheduled posts:', error);
                this.scheduledPosts = [];
            }
        },

        previousMonth() {
            if (this.calendarMonth === 0) {
                this.calendarMonth = 11;
                this.calendarYear--;
            } else {
                this.calendarMonth--;
            }
            this.loadScheduledPosts();
        },

        nextMonth() {
            if (this.calendarMonth === 11) {
                this.calendarMonth = 0;
                this.calendarYear++;
            } else {
                this.calendarMonth++;
            }
            this.loadScheduledPosts();
        },

        editScheduledPost(post) {
            this.editMode = true;
            this.editPostId = post.id;
            this.content.global.text = post.content || '';
            this.scheduleEnabled = true;
            this.schedule.date = post.scheduled_date;
            this.schedule.time = post.scheduled_time;
            this.showCalendar = false;
        },

        // ============================================
        // BEST TIMES METHODS
        // ============================================

        applyOptimalTime(time) {
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const targetDay = days.indexOf(time.day);
            const today = new Date();
            const currentDay = today.getDay();

            let daysToAdd = targetDay - currentDay;
            if (daysToAdd <= 0) {
                daysToAdd += 7;
            }

            const targetDate = new Date(today);
            targetDate.setDate(today.getDate() + daysToAdd);

            this.schedule.date = targetDate.toISOString().split('T')[0];
            this.schedule.time = time.time;
            this.scheduleEnabled = true;
            this.showBestTimes = false;
        },

        async loadOptimalTimes() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/optimal-times`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const data = await response.json();
                this.optimalTimes = data.times || this.optimalTimes;
            } catch (error) {
                console.error('Failed to load optimal times:', error);
            }
        },

        // ============================================
        // BULK SCHEDULING METHODS
        // ============================================

        addBulkScheduleTime() {
            this.bulkSchedule.times.push({ date: '', time: '' });
        },

        removeBulkScheduleTime(index) {
            this.bulkSchedule.times.splice(index, 1);
        },

        applyBulkSchedule() {
            // Implementation for bulk scheduling
            console.log('Applying bulk schedule:', this.bulkSchedule);
            this.showBulkScheduling = false;
        },

        // ============================================
        // QUEUE METHODS
        // ============================================

        async getNextQueueSlot() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/queue/next-slot`, {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const data = await response.json();
                return data.slot || null;
            } catch (error) {
                console.error('Failed to get next queue slot:', error);
                return null;
            }
        }
    };
}

export default getSchedulingManagementMethods;
