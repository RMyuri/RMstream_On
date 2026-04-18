(function() {
    const API_KEY = window.YOUTUBE_API_KEY || "AIzaSyBRVg9qK01Uf0iou5ts3bSyTi-FAO1bXNw";
    const videoList = document.getElementById('videoList');
    const apiStatus = document.getElementById('apiStatus');
    
    function showApiStatus(success, msg = "") {
        if (!apiStatus) return;
        apiStatus.style.display = 'block';
        if (success) {
            apiStatus.textContent = "API do YouTube carregada com sucesso!";
            apiStatus.classList.remove('error');
        } else {
            apiStatus.textContent = msg || "Erro ao acessar a API do YouTube.";
            apiStatus.classList.add('error');
            console.error("API Error:", msg);
        }
    }
    
    function renderVideoList(items) {
        if (!videoList) return;
        videoList.innerHTML = "";
        
        if (!items || items.length === 0) {
            videoList.innerHTML = "<div class='no-videos'>Nenhum vídeo encontrado.</div>";
            return;
        }
        
        items.forEach(item => {
            // Para vídeos populares, o ID vem direto no item.id (não em item.id.videoId)
            const videoId = typeof item.id === 'string' ? item.id : item.id?.videoId;
            
            if (videoId && item.snippet?.thumbnails?.medium) {
                const div = document.createElement('div');
                div.className = "video-card";
                div.innerHTML = `
                    <img src="${item.snippet.thumbnails.medium.url}" alt="thumbnail" />
                    <div class="video-info">
                        <div class="video-title">${item.snippet.title}</div>
                        <div class="video-channel">
                            <a href="/RMStream/views/channel.php?id=${item.snippet.channelId}" class="channel-link">${item.snippet.channelTitle}</a>
                        </div>
                    </div>
                `;
                
                // Ao clicar no card (exceto no link do canal), abre o vídeo no Room
                div.addEventListener('click', function(e) {
                    if (!e.target.closest('.channel-link')) {
                        window.location.href = `/RMStream/views/room.php?v=${videoId}`;
                    }
                });
                
                videoList.appendChild(div);
            }
        });
    }
    
    // Busca vídeos populares para exibir na página inicial
    async function fetchPopularVideos() {
        videoList.innerHTML = "<div class='loading'>Carregando vídeos populares...</div>";
        
        try {
            const url = `https://www.googleapis.com/youtube/v3/videos?part=snippet&chart=mostPopular&maxResults=10&key=${API_KEY}`;
            
            const response = await fetch(url);
            
            if (!response.ok) {
                showApiStatus(false, `Erro ${response.status}`);
                videoList.innerHTML = `<div class='error'>Erro ao buscar vídeos populares: ${response.status}</div>`;
                return;
            }
            
            const data = await response.json();
            
            if (data.error) {
                showApiStatus(false, `Erro da API: ${data.error.message}`);
                videoList.innerHTML = `<div class='error'>Erro da API: ${data.error.message}</div>`;
                return;
            }
            
            showApiStatus(true);
            renderVideoList(data.items || []);
            
        } catch (error) {
            showApiStatus(false, `Erro: ${error.message}`);
            videoList.innerHTML = `<div class='error'>Erro ao buscar vídeos: ${error.message}</div>`;
            console.error("Fetch error:", error);
        }
    }
    
    // Iniciar carregamento de vídeos populares
    fetchPopularVideos();
})();
    fetchPopularVideos();
;
