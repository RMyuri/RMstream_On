document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('results-container');
  const params    = new URLSearchParams(window.location.search);
  const query     = params.get('q')?.trim(); // <-- Corrigido aqui

  if (!query) {
    container.innerHTML = '<p class="info">Digite algo na barra de busca acima.</p>';
    return;
  }

  const url = new URL('https://www.googleapis.com/youtube/v3/search');
  url.search = new URLSearchParams({
    part: 'snippet',
    type: 'video',
    maxResults: '16',
    q: query,
    key: YT_API_KEY
  });

  fetch(url)  
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        throw new Error(data.error.message);
      }
      const items = data.items || [];
      if (items.length === 0) {
        container.innerHTML = `<p class="info">Nenhum resultado para “${query}”.</p>`;
        return;
      }
      container.innerHTML = items.map(item => {
        const id    = item.id.videoId;
        const thumb = item.snippet.thumbnails.medium.url;
        const title = item.snippet.title;
        return `
          <a href="index.php?videoId=${id}" class="result-card">
            <img src="${thumb}" alt="">
            <div class="info">
              <div class="title">${title}</div>
            </div>
          </a>
        `;
      }).join('');
    })
    .catch(err => {
      container.innerHTML = `<p class="error">Erro ao buscar: ${err.message}</p>`;
    });
});