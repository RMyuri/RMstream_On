/**
 * Global notification system
 */
class NotificationSystem {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.isLoading = false;
        this.isOpen = false;
        this.pollInterval = 30000; // 30 seconds
        this.pollTimer = null;
        
        // DOM Elements
        this.notificationsBtn = document.getElementById('notificationsBtn');
        this.notificationsCount = document.getElementById('notificationsCount');
        this.notificationsDropdown = document.getElementById('notificationsDropdown');
        this.notificationsList = document.getElementById('notificationsList');
        this.markAllReadBtn = document.getElementById('markAllReadBtn');
    }
    
    /**
     * Initialize the notification system
     */
    init() {
        if (!this.notificationsBtn) return;
        
        // Setup event listeners
        this.notificationsBtn.addEventListener('click', () => this.toggleDropdown());
        
        if (this.markAllReadBtn) {
            this.markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (this.isOpen && !this.notificationsDropdown.contains(event.target) && 
                event.target !== this.notificationsBtn) {
                this.closeDropdown();
            }
        });
        
        // Load initial notifications
        this.loadNotifications();
        
        // Start polling for new notifications
        this.startPolling();
    }
    
    /**
     * Toggle notifications dropdown
     */
    toggleDropdown() {
        if (this.isOpen) {
            this.closeDropdown();
        } else {
            this.openDropdown();
        }
    }
    
    /**
     * Open notifications dropdown
     */
    openDropdown() {
        if (!this.notificationsDropdown) return;
        
        this.notificationsDropdown.classList.add('open');
        this.isOpen = true;
        
        // Mark notifications as read when opened
        this.markVisibleAsRead();
    }
    
    /**
     * Close notifications dropdown
     */
    closeDropdown() {
        if (!this.notificationsDropdown) return;
        
        this.notificationsDropdown.classList.remove('open');
        this.isOpen = false;
    }
    
    /**
     * Load notifications from API
     */
    loadNotifications() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        
        if (this.notificationsList) {
            this.notificationsList.innerHTML = '<div class="notification-loading"><i class="fas fa-spinner fa-spin"></i> Carregando notificações...</div>';
        }
        
        fetch('/RMStream/api/notifications.php?limit=5')
            .then(response => response.json())
            .then(data => {
                this.isLoading = false;
                
                if (data.success) {
                    this.notifications = data.notifications;
                    this.unreadCount = data.unread_count;
                    
                    // Update UI
                    this.updateNotificationCount();
                    this.renderNotifications();
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                this.isLoading = false;
                
                if (this.notificationsList) {
                    this.notificationsList.innerHTML = '<div class="notification-error">Erro ao carregar notificações</div>';
                }
            });
    }
    
    /**
     * Update notification count badge
     */
    updateNotificationCount() {
        if (!this.notificationsCount) return;
        
        if (this.unreadCount > 0) {
            this.notificationsCount.textContent = this.unreadCount > 9 ? '9+' : this.unreadCount;
            this.notificationsCount.style.display = 'flex';
        } else {
            this.notificationsCount.style.display = 'none';
        }
    }
    
    /**
     * Render notifications in dropdown
     */
    renderNotifications() {
        if (!this.notificationsList) return;
        
        if (this.notifications.length === 0) {
            this.notificationsList.innerHTML = '<div class="notification-empty">Nenhuma notificação</div>';
            return;
        }
        
        let html = '';
        
        this.notifications.forEach(notification => {
            const isRead = notification.is_read === '1' || notification.is_read === 1;
            const timeAgo = this.getTimeAgo(notification.created_at);
            const notificationClass = isRead ? 'notification-item' : 'notification-item unread';
            
            html += `
                <div class="${notificationClass}" data-id="${notification.id}">
                    <div class="notification-avatar">
                        ${notification.trigger_user_id ? 
                            `<img src="${notification.profile_image || '/RMStream/public/images/default-avatar.png'}" alt="Avatar">` : 
                            `<i class="fas fa-bell"></i>`}
                    </div>
                    <div class="notification-content">
                        <div class="notification-message">
                            ${notification.trigger_user_id ? 
                                `<strong>${notification.display_name || notification.username}</strong> ` : ''}
                            ${notification.message}
                        </div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                </div>
            `;
        });
        
        this.notificationsList.innerHTML = html;
        
        // Add click event to navigate to related content
        const notificationItems = this.notificationsList.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', () => {
                const notificationId = item.getAttribute('data-id');
                this.handleNotificationClick(
                    this.notifications.find(n => n.id == notificationId)
                );
            });
        });
    }
    
    /**
     * Handle notification click
     */
    handleNotificationClick(notification) {
        if (!notification) return;
        
        // Mark as read
        this.markAsRead(notification.id);
        
        // Navigate based on notification type
        switch (notification.type) {
            case 'friend_request':
                window.location.href = '/RMStream/views/chat/find_friends.php';
                break;
                
            case 'friend_accepted':
                if (notification.trigger_user_id) {
                    window.location.href = `/RMStream/views/chat/conversation.php?user_id=${notification.trigger_user_id}`;
                } else {
                    window.location.href = '/RMStream/views/chat/index.php';
                }
                break;
                
            case 'new_message':
                if (notification.reference_type === 'direct' && notification.reference_id) {
                    window.location.href = `/RMStream/views/chat/conversation.php?user_id=${notification.reference_id}`;
                } else if (notification.reference_type === 'group' && notification.reference_id) {
                    window.location.href = `/RMStream/views/chat/conversation.php?group_id=${notification.reference_id}`;
                } else {
                    window.location.href = '/RMStream/views/chat/index.php';
                }
                break;
                
            case 'group_invite':
                if (notification.reference_id) {
                    window.location.href = `/RMStream/views/chat/conversation.php?group_id=${notification.reference_id}`;
                } else {
                    window.location.href = '/RMStream/views/chat/index.php';
                }
                break;
                
            default:
                // Generic fallback
                this.closeDropdown();
        }
    }
    
    /**
     * Mark a notification as read
     */
    markAsRead(notificationId) {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('notification_id', notificationId);
        
        fetch('/RMStream/api/notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update local state
                this.notifications = this.notifications.map(n => {
                    if (n.id == notificationId) {
                        n.is_read = 1;
                    }
                    return n;
                });
                
                // Update UI
                const notification = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (notification) {
                    notification.classList.remove('unread');
                }
                
                // Recount unread
                this.unreadCount = this.notifications.filter(n => n.is_read === 0 || n.is_read === '0').length;
                this.updateNotificationCount();
            }
        })
        .catch(error => console.error('Error marking notification as read:', error));
    }
    
    /**
     * Mark all visible notifications as read
     */
    markVisibleAsRead() {
        const unreadNotifications = this.notifications.filter(n => n.is_read === 0 || n.is_read === '0');
        
        if (unreadNotifications.length === 0) return;
        
        unreadNotifications.forEach(notification => {
            this.markAsRead(notification.id);
        });
    }
    
    /**
     * Mark all notifications as read
     */
    markAllAsRead() {
        const formData = new FormData();
        formData.append('action', 'mark_all_read');
        
        fetch('/RMStream/api/notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update local state
                this.notifications = this.notifications.map(n => {
                    n.is_read = 1;
                    return n;
                });
                
                // Update UI
                const notificationItems = document.querySelectorAll('.notification-item');
                notificationItems.forEach(item => {
                    item.classList.remove('unread');
                });
                
                this.unreadCount = 0;
                this.updateNotificationCount();
            }
        })
        .catch(error => console.error('Error marking all notifications as read:', error));
    }
    
    /**
     * Start polling for new notifications
     */
    startPolling() {
        this.pollTimer = setInterval(() => {
            this.checkForNewNotifications();
        }, this.pollInterval);
    }
    
    /**
     * Check for new notifications
     */
    checkForNewNotifications() {
        fetch('/RMStream/api/notifications.php?limit=5')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Check if we have new notifications
                    const oldCount = this.unreadCount;
                    this.unreadCount = data.unread_count;
                    
                    if (this.unreadCount > oldCount) {
                        // Play notification sound
                        this.playNotificationSound();
                        
                        // Update notifications list
                        this.notifications = data.notifications;
                        this.renderNotifications();
                    }
                    
                    this.updateNotificationCount();
                }
            })
            .catch(error => console.error('Error checking for new notifications:', error));
    }
    
    /**
     * Play notification sound
     */
    playNotificationSound() {
        const audio = new Audio('/RMStream/public/sounds/notification.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Error playing notification sound:', e));
    }
    
    /**
     * Get time ago string from timestamp
     */
    getTimeAgo(timestamp) {
        const now = new Date();
        const date = new Date(timestamp);
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) {
            return 'agora';
        }
        
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) {
            return `${minutes} min atrás`;
        }
        
        const hours = Math.floor(minutes / 60);
        if (hours < 24) {
            return `${hours} hora${hours > 1 ? 's' : ''} atrás`;
        }
        
        const days = Math.floor(hours / 24);
        if (days < 7) {
            return `${days} dia${days > 1 ? 's' : ''} atrás`;
        }
        
        return date.toLocaleDateString('pt-BR');
    }
    
    /**
     * Clean up resources
     */
    destroy() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
        }
    }
}

// Initialize notification system
document.addEventListener('DOMContentLoaded', function() {
    const notificationSystem = new NotificationSystem();
    notificationSystem.init();
    
    // Make available globally
    window.notificationSystem = notificationSystem;
});
