// js/room.js
document.addEventListener('DOMContentLoaded', () => {
  if (!IN_ROOM) return;
  // Conecta no WS
  const wsUrl = `ws://${location.hostname}:8080/?room=${encodeURIComponent(ROOM)}`;
  const socket = new WebSocket(wsUrl);

  // Chat
  const chatBox  = document.getElementById('chat-box');
  const chatForm = document.getElementById('chat-form');
  const chatInput= document.getElementById('chat-input');

  // Vídeo (YouTube IFrame API)
  let player;
  window.onYouTubeIframeAPIReady = () => {
    player = new YT.Player('player', {
      videoId: VIDEO_ID,  // defina VIDEO_ID globalmente
      events: { onStateChange }
    });
  };

  // envia mensagem de chat
  chatForm.addEventListener('submit', ev => {
    ev.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    const payload = JSON.stringify({ type:'chat', text });
    socket.send(payload);
    chatInput.value = '';
    appendChat('Você', text);
  });

  // detecta mudanças de estado no vídeo
  function onStateChange(evt) {
    const state = evt.data;           // –1, 0, 1, 2…
    const time  = player.getCurrentTime();
    socket.send(JSON.stringify({ type:'video', state, time }));
  }

  // recebe qualquer mensagem do servidor
  socket.onmessage = ({data}) => {
    const msg = JSON.parse(data);
    if (msg.type === 'chat') {
      appendChat('Outro', msg.text);
    }
    if (msg.type === 'video') {
      // sincroniza player
      if (Math.abs(player.getCurrentTime() - msg.time) > 1) {
        player.seekTo(msg.time, true);
      }
      if (msg.state === YT.PlayerState.PAUSED) player.pauseVideo();
      if (msg.state === YT.PlayerState.PLAYING) player.playVideo();
    }
  };

  function appendChat(user, text) {
    const div = document.createElement('div');
    div.className = 'msg';
    div.innerHTML = `<strong>${user}</strong>: ${text}`;
    chatBox.appendChild(div);
    chatBox.scrollTop = chatBox.scrollHeight;
  }
});
