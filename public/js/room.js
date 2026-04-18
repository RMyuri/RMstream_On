(function() {
    const API_KEY = window.YOUTUBE_API_KEY || "AIzaSyBRVg9qK01Uf0iou5ts3bSyTi-FAO1bXNw";
    const videoList = document.getElementById('videoList');
    const apiStatus = document.getElementById('apiStatus');
    const currentVideoId = window.CURRENT_VIDEO_ID || '';
    
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
            videoList.innerHTML = "<div class='no-videos'>Nenhum vídeo relacionado encontrado.</div>";
            return;
        }
        
        items.forEach(item => {
            const videoId = item.id?.videoId || (item.id?.kind === "youtube#video" ? item.id.videoId : null);
            
            if (videoId && item.snippet?.thumbnails?.medium) {
                const div = document.createElement('div');
                div.className = "video-card";
                div.innerHTML = `
                    <img src="${item.snippet.thumbnails.medium.url}" alt="thumbnail" />
                    <div class="video-info">
                        <div class="video-title">${item.snippet.title}</div>
                        <div class="video-channel">${item.snippet.channelTitle}</div>
                    </div>
                `;
                
                // Ao clicar, carrega este vídeo
                div.addEventListener('click', function() {
                    window.location.href = `/RMStream/views/room.php?v=${videoId}`;
                });
                
                videoList.appendChild(div);
            }
        });
    }
    
    // Busca vídeos relacionados ao vídeo atual
    async function fetchRelatedVideos() {
        if (!currentVideoId) {
            fetchPopularVideos();
            return;
        }
        
        videoList.innerHTML = "<div class='loading'>Carregando vídeos relacionados...</div>";
        
        try {
            const url = `https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&maxResults=10&relatedToVideoId=${currentVideoId}&key=${API_KEY}`;
            console.log("Buscando relacionados:", url.replace(API_KEY, "API_KEY_HIDDEN"));
            
            const response = await fetch(url);
            console.log("Status HTTP:", response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                showApiStatus(false, `Erro ${response.status}: ${errorText}`);
                videoList.innerHTML = `<div class='error'>Erro ao buscar vídeos: ${response.status}</div>`;
                // Se falhar, tenta buscar vídeos populares como fallback
                fetchPopularVideos();
                return;
            }
            
            const data = await response.json();
            
            if (data.error) {
                showApiStatus(false, `Erro da API: ${data.error.message}`);
                videoList.innerHTML = `<div class='error'>Erro da API: ${data.error.message}</div>`;
                // Se falhar, tenta buscar vídeos populares como fallback
                fetchPopularVideos();
                return;
            }
            
            showApiStatus(true);
            renderVideoList(data.items || []);
            
        } catch (error) {
            showApiStatus(false, `Erro: ${error.message}`);
            videoList.innerHTML = `<div class='error'>Erro: ${error.message}</div>`;
            console.error("Fetch error:", error);
            // Se falhar, tenta buscar vídeos populares como fallback
            fetchPopularVideos();
        }
    }
    
    // Busca vídeos populares
    async function fetchPopularVideos() {
        videoList.innerHTML = "<div class='loading'>Carregando vídeos populares...</div>";
        
        try {
            const url = `https://www.googleapis.com/youtube/v3/videos?part=snippet&chart=mostPopular&maxResults=10&key=${API_KEY}`;
            console.log("Buscando populares:", url.replace(API_KEY, "API_KEY_HIDDEN"));
            
            const response = await fetch(url);
            console.log("Status HTTP:", response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                showApiStatus(false, `Erro ${response.status}: ${errorText}`);
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
            videoList.innerHTML = `<div class='error'>Erro: ${error.message}</div>`;
            console.error("Fetch error:", error);
        }
    }
    
    // Inicia a busca pelos vídeos apenas se houver um ID de vídeo atual
    if (currentVideoId) {
        fetchRelatedVideos();
    } else {
        // Não há vídeo atual, então busca os vídeos populares
        fetchPopularVideos();
    }
})();

searchInput.addEventListener('keydown', e => {
    if (e.key === "Enter") searchBtn.onclick();
});

// Fechar overlay ao clicar fora do vídeo
overlay.addEventListener('click', e => {
    if (e.target === overlay) {
        overlay.style.display = "none";
        mainVideo.src = "";
    }
});
overlay.addEventListener('click', e => {
    if (e.target === overlay) {
        overlay.style.display = "none";
        mainVideo.src = "";
    }
});
// Fechar overlay ao clicar fora do vídeo
overlay.addEventListener('click', e => {
    if (e.target === overlay) {
        overlay.style.display = "none";
        mainVideo.src = "";
    }
});
overlay.addEventListener('click', e => {
    if (e.target === overlay) {
        overlay.style.display = "none";
        mainVideo.src = "";
    }
});
;
