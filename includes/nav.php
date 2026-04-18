<nav>
    <a href="/RMStream/views/index.php" class="logo">RMStream</a>
    <div class="nav-container">
        <div class="nav-item">
            <a href="/RMStream/views/index.php" class="nav-link <?php echo $current_page === 'index' ? 'active' : ''; ?>">Início</a>
        </div>
        <div class="nav-item nav-item-with-submenu">
            <a href="/RMStream/views/room.php" class="nav-link <?php echo $current_page === 'room' ? 'active' : ''; ?>">Room</a>
            <div class="submenu">
                <a href="#" class="submenu-item create-room">Criar Sala</a>
                <a href="#" class="submenu-item join-room">Entrar em Sala</a>
            </div>
        </div>
        <div class="nav-item">
            <a href="/RMStream/views/chart.php" class="nav-link <?php echo $current_page === 'chart' ? 'active' : ''; ?>">Chart</a>
        </div>
        <div class="nav-item">
            <a href="/RMStream/views/chat/index.php" class="nav-link <?php echo $current_page === 'chat' ? 'active' : ''; ?>">Chat</a>
        </div>
        <div class="nav-item">
            <a href="/RMStream/views/profile/index.php" class="nav-link <?php echo $current_page === 'profile' ? 'active' : ''; ?>">Perfil</a>
        </div>
    </div>
    <div class="nav-right-group">
        <!-- Ícone de notificações -->
        <div class="notifications-container">
            <div class="notifications-icon">
                <i class="fas fa-bell"></i>
                <span class="notifications-badge">0</span>
            </div>
            <div class="notifications-dropdown">
                <div class="notifications-header">
                    <div class="notifications-title">Notificações</div>
                    <div class="mark-all-read">Marcar todas como lidas</div>
                </div>
                <ul class="notifications-list">
                    <!-- Notificações serão carregadas via JavaScript -->
                </ul>
                <div class="notifications-footer">
                    <a href="/RMStream/views/notifications.php" class="view-all">Ver todas as notificações</a>
                </div>
            </div>
        </div>
        
        <div class="nav-profile">
            <a href="/RMStream/views/profile/index.php">
                <img src="<?php echo isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : '/RMStream/public/images/default-avatar.png'; ?>" 
                     alt="Avatar" class="profile-avatar">
            </a>
            <a href="/RMStream/views/auth/logout.php" class="nav-link">Sair</a>
        </div>
    </div>
</nav>
