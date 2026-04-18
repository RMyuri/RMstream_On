(function() {
    const API_KEY = window.YOUTUBE_API_KEY;
    const channelId = new URLSearchParams(window.location.search).get('id');
    const channelProfile = document.getElementById('channelProfile');
    const channelVideos = document.getElementById('channelVideos');
    const apiStatus = document.getElementById('apiStatus');

    function showApiStatus(success, msg = "") {
        if (!apiStatus) return;
        apiStatus.style.display = 'block';
        apiStatus.classList.toggle('error', !success);
        apiStatus.textContent = msg || (success ? "API do YouTube carregada com sucesso!" : "Erro ao acessar a API do YouTube.");
        console.log("API status:", success, msg);
    }

    async function loadChannelInfo() {
        if (!channelId) {
            showApiStatus(false, "ID do canal não fornecido");
            return;
        }

        if (!API_KEY) {
            showApiStatus(false, "Chave da API não definida");
            return;
        }

        try {
            const url = `https://www.googleapis.com/youtube/v3/channels?part=snippet,brandingSettings&id=${channelId}&key=${API_KEY}`;
            const res = await fetch(url);
            
            if (!res.ok) {
                showApiStatus(false, `Erro HTTP: ${res.status}`);
                return;
            }
            
            const data = await res.json();
            
            if (data.error) {
                showApiStatus(false, `Erro da API: ${data.error.message}`);
                return;
            }
            
            if (data.items && data.items.length) {
                showApiStatus(true);
                const channel = data.items[0];
                const banner = channel.brandingSettings?.image?.bannerExternalUrl || '';
                const thumb = channel.snippet.thumbnails?.default?.url || '';
                const title = channel.snippet.title;
                const description = channel.snippet.description || '';
                
                channelProfile.innerHTML = `
                    <div class="channel-banner" style="background-image:url('${banner}')"></div>
                    <div class="channel-header">
                        <img class="channel-thumb" src="${thumb}" alt="Foto do canal">
                        <div class="channel-info">
                            <div class="channel-title">${title}</div>
                            <div class="channel-description">${description}</div>
                        </div>
                    </div>
                `;
            } else {
                showApiStatus(false, "Canal não encontrado");
            }
        } catch (err) {
            showApiStatus(false, `Erro: ${err.message}`);
        }
    }

    async function loadChannelVideos() {
        if (!channelId) return;
        
        try {
            const url = `https://www.googleapis.com/youtube/v3/search?part=snippet&channelId=${channelId}&maxResults=20&type=video&key=${API_KEY}`;
            const res = await fetch(url);
            
            if (!res.ok) {
                channelVideos.innerHTML = `<div>Erro HTTP: ${res.status}</div>`;
                return;
            }
            
            const data = await res.json();
            
            if (data.error) {
                channelVideos.innerHTML = `<div>Erro da API: ${data.error.message}</div>`;
                return;
            }
            
            channelVideos.innerHTML = "";
            
            if (data.items && data.items.length) {
                data.items.forEach(item => {
                    let div = document.createElement('div');
                    div.className = "video-card-diagonal"; // Mantendo o nome da classe por compatibilidade
                    div.innerHTML = `
                        <img src="${item.snippet.thumbnails.medium.url}" alt="thumb" />
                        <div class="video-info">
                            <div class="video-title">${item.snippet.title}</div>
                        </div>
                    `;
                    div.onclick = () => {
                        window.location.href = `/RMStream/views/room.php?v=${item.id.videoId}`;
                    };
                    channelVideos.appendChild(div);
                });
            } else {
                channelVideos.innerHTML = "<div>Nenhum vídeo encontrado.</div>";
            }
        } catch (err) {
            channelVideos.innerHTML = `<div>Erro: ${err.message}</div>`;
        }
    }

    loadChannelInfo();
    loadChannelVideos();
})();
