/**
 * Real-time chat updates via polling
 */
class ChatRealtime {
    constructor(options = {}) {
        this.options = Object.assign({
            pollInterval: 5000, // Default polling interval in milliseconds
            messageContainer: null,
            conversationType: 'direct', // 'direct' or 'group'
            conversationId: null,
            currentUserId: null,
            onNewMessage: null,
            onMessageStatusChange: null
        }, options);

        this.lastMessageId = 0;
        this.isPolling = false;
        this.pollTimer = null;
        this.lastActivity = Date.now();
        this.initialized = false;
    }

    /**
     * Initialize real-time updates
     */
    init() {
        if (!this.options.conversationId || !this.options.currentUserId) {
            console.error('ChatRealtime: Missing required options');
            return;
        }

        this.initialized = true;
        
        // Find last message ID if messages exist
        if (this.options.messageContainer) {
            const messages = this.options.messageContainer.querySelectorAll('.message');
            if (messages.length > 0) {
                const lastMessage = messages[messages.length - 1];
                const messageId = parseInt(lastMessage.getAttribute('data-message-id') || 0);
                this.lastMessageId = messageId;
            }
        }

        // Start polling
        this.startPolling();
        
        // Monitor user activity
        document.addEventListener('click', () => this.onUserActivity());
        document.addEventListener('keydown', () => this.onUserActivity());
        document.addEventListener('mousemove', () => this.onUserActivity());
        document.addEventListener('touchstart', () => this.onUserActivity());
        
        // Optimize polling when tab is not visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.slowDownPolling();
            } else {
                this.speedUpPolling();
                this.onUserActivity();
            }
        });
    }

    /**
     * Start polling for new messages
     */
    startPolling() {
        if (this.isPolling || !this.initialized) return;
        
        this.isPolling = true;
        this.pollForMessages();
    }

    /**
     * Stop polling for new messages
     */
    stopPolling() {
        this.isPolling = false;
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
    }

    /**
     * Poll for new messages
     */
    pollForMessages() {
        if (!this.isPolling) return;

        const params = new URLSearchParams({
            type: this.options.conversationType,
            id: this.options.conversationId,
            since_id: this.lastMessageId
        });

        fetch(`/RMStream/api/messages_realtime.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages && data.messages.length > 0) {
                    // Update last message ID
                    const newestMessage = data.messages[data.messages.length - 1];
                    this.lastMessageId = newestMessage.id;
                    
                    // Handle new messages
                    if (typeof this.options.onNewMessage === 'function') {
                        this.options.onNewMessage(data.messages);
                    }
                }
                
                // Schedule next poll
                const interval = document.hidden ? this.options.pollInterval * 3 : this.options.pollInterval;
                this.pollTimer = setTimeout(() => this.pollForMessages(), interval);
            })
            .catch(error => {
                console.error('Error polling for messages:', error);
                // Retry after a longer interval
                this.pollTimer = setTimeout(() => this.pollForMessages(), this.options.pollInterval * 2);
            });
    }

    /**
     * Slow down polling frequency (for inactive users)
     */
    slowDownPolling() {
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = setTimeout(() => this.pollForMessages(), this.options.pollInterval * 3);
        }
    }

    /**
     * Speed up polling frequency (for active users)
     */
    speedUpPolling() {
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = setTimeout(() => this.pollForMessages(), this.options.pollInterval);
        }
    }

    /**
     * Handle user activity
     */
    onUserActivity() {
        this.lastActivity = Date.now();
        this.speedUpPolling();
    }

    /**
     * Cleanup resources
     */
    destroy() {
        this.stopPolling();
    }
}

// Initialize real-time updates when in a conversation
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messagesContainer');
    
    if (messagesContainer && typeof conversationData !== 'undefined') {
        const realtime = new ChatRealtime({
            messageContainer: messagesContainer,
            conversationType: conversationData.type,
            conversationId: conversationData.id,
            currentUserId: conversationData.currentUser.id,
            onNewMessage: function(messages) {
                // Add new messages to the chat
                messages.forEach(message => {
                    const messageElement = createMessageElement(message);
                    messagesContainer.appendChild(messageElement);
                });
                
                // Scroll to bottom
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                
                // Play notification sound for new messages (not from current user)
                const hasExternalMessages = messages.some(m => parseInt(m.sender_id) !== conversationData.currentUser.id);
                if (hasExternalMessages) {
                    playMessageSound();
                }
            }
        });
        
        realtime.init();
        
        // Attach to window to avoid garbage collection
        window.chatRealtime = realtime;
    }
    
    // Function to play notification sound
    function playMessageSound() {
        const audio = new Audio('/RMStream/public/sounds/message.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Error playing sound:', e));
    }
});
