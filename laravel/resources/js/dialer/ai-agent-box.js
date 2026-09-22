const aiAgentBoxComponent = () => ({
  enabled: false,
  expanded: true,
  status: 'idle',
  statusDetail: '',
  goal: 'Collect the patient name, phone number, preferred appointment date, and doctor name.',
  mode: 'lead',
  voice: 'professional',
  autoNotes: true,
  humanHandoff: true,
  conversation: [],
  isCapturing: false,
  audioContext: null,
  processor: null,
  stream: null,
  socket: null,
  sessionId: null,
  labels: {},
  feedback: '',

  init() {
    if (this.socket) return;
    const labelsEl = document.getElementById('ai-agent-labels');
    if (labelsEl) {
      try { this.labels = JSON.parse(labelsEl.textContent); } catch (e) { this.labels = {}; }
    }

    let configuredUrl = '';
    const socketConfigEl = document.getElementById('dialer-inbound-socket');
    if (socketConfigEl) {
      try {
        configuredUrl = JSON.parse(socketConfigEl.dataset.config || '{}').url || '';
      } catch (_error) {}
    }
    let backendHost = window.location.hostname;
    if (['localhost', '127.0.0.1', '::1'].includes(backendHost)) {
      const webrtcConfigEl = document.getElementById('dialer-webrtc-config');
      try {
        const sipConfig = JSON.parse(webrtcConfigEl?.dataset.config || '{}');
        backendHost = sipConfig.domain || backendHost;
      } catch (_error) {}
    }
    const localBackendUrl = `${window.location.protocol}//${backendHost}:4000`;
    let backendUrl = configuredUrl || (window.location.port === '8080' ? localBackendUrl : window.location.origin);
    try {
      const parsedBackendUrl = new URL(backendUrl, window.location.origin);
      if (['localhost', '127.0.0.1', '::1'].includes(parsedBackendUrl.hostname) && backendHost !== parsedBackendUrl.hostname) {
        parsedBackendUrl.hostname = backendHost;
        backendUrl = parsedBackendUrl.toString();
      }
    } catch (_error) {
      backendUrl = localBackendUrl;
    }

    this.socket = window.io(`${backendUrl.replace(/\/$/, '')}/ai`, {
      transports: ['websocket'],
      withCredentials: true
    });

    this.socket.on('connect', () => {
      this.status = 'idle';
      this.statusDetail = this.labels.ready || 'Ready';
    });

    this.socket.on('disconnect', () => {
      this.status = 'error';
      this.statusDetail = this.labels.disconnected || 'Disconnected';
    });

    this.socket.on('ai:status', (data) => {
      this.status = data.status || 'idle';
      this.statusDetail = data.detail || '';
    });

    this.socket.on('ai:transcript', (data) => {
      this.conversation.push({
        role: data.role,
        text: data.text,
        timestamp: new Date().toISOString()
      });
    });

    this.socket.on('ai:response', (data) => {
      this.status = 'speaking';
      this.statusDetail = this.labels.speaking || 'Speaking';
    });

    this.socket.on('ai:audio', (data) => {
      this.status = 'speaking';
    });

    this.socket.on('ai:error', (data) => {
      this.status = 'error';
      this.statusDetail = data.message || (this.labels.error || 'Error');
      this.feedback = data.message || '';
    });

    this.socket.on('ai:stopped', () => {
      this.status = 'idle';
      this.statusDetail = this.labels.stopped || 'Stopped';
      this.isCapturing = false;
    });
  },

  toggle() {
    if (this.enabled) {
      this.stop();
    } else {
      this.start();
    }
  },

  async start() {
    if (this.isCapturing) return;
    this.feedback = '';

    try {
      this.stream = await navigator.mediaDevices.getUserMedia({
        audio: {
          echoCancellation: true,
          noiseSuppression: true,
          autoGainControl: true,
          sampleRate: 16000
        }
      });

      this.audioContext = new AudioContext({ sampleRate: 16000 });
      this.processor = this.audioContext.createScriptProcessor(4096, 1, 1);
      const source = this.audioContext.createMediaStreamSource(this.stream);

      this.processor.onaudioprocess = (e) => {
        if (!this.isCapturing || !this.socket) return;
        const inputBuffer = e.inputBuffer.getChannel(0);
        const PCMBuffer = new ArrayBuffer(inputBuffer.length * 2);
        const view = new DataView(PCMBuffer);
        for (let i = 0; i < inputBuffer.length; i++) {
          const s = Math.max(-1, Math.min(1, inputBuffer[i]));
          view.setInt16(i * 2, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
        }
        const base64 = btoa(String.fromCharCode(...new Uint8Array(PCMBuffer)));
        this.socket.emit('ai:audio', { audio: base64, sampleRate: 16000 });
      };

      source.connect(this.processor);
      this.processor.connect(this.audioContext.destination);

      this.isCapturing = true;
      this.socket.emit('ai:start', {
        goal: this.goal,
        mode: this.mode,
        voice: this.voice,
        autoNotes: this.autoNotes,
        humanHandoff: this.humanHandoff
      });

      this.status = 'listening';
      this.statusDetail = this.labels.listening || 'Listening';
    } catch (err) {
      this.status = 'error';
      this.statusDetail = (this.labels.error || 'Error') + ': ' + err.message;
      this.feedback = err.message;
    }
  },

  stop() {
    this.isCapturing = false;
    if (this.socket) this.socket.emit('ai:stop');
    if (this.processor) { this.processor.disconnect(); this.processor = null; }
    if (this.audioContext) { this.audioContext.close(); this.audioContext = null; }
    if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
    this.status = 'idle';
    this.statusDetail = this.labels.stopped || 'Stopped';
  },

  minimize() {
    this.expanded = false;
  },

  expand() {
    this.expanded = true;
  },

  setMode(value) {
    this.mode = value;
  },

  setVoice(value) {
    this.voice = value;
  },

  get statusClass() {
    const classes = {
      listening: 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
      speaking: 'bg-violet-500/10 text-violet-600 dark:text-violet-300',
      error: 'bg-red-500/10 text-red-600 dark:text-red-300',
      idle: 'bg-slate-500/10 text-slate-500 dark:text-slate-400'
    };
    return classes[this.status] || classes.idle;
  },

  get toggleLabel() {
    return this.enabled ? (this.labels.stop || 'Stop') : (this.labels.start || 'Start');
  },

  get toggleIcon() {
    return this.enabled ? 'bi-stop-fill' : 'bi-play-fill';
  }
});

export default aiAgentBoxComponent;
