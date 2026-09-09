// Local speaker feedback only; never mixed into microphone/carrier audio.
class DialerFeedback {
    constructor() {
        this.context = null;
        this.nodes = [];
        this.timer = null;
        this.kind = null;
    }
    unlock() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            this.context ||= new AudioContext();
            if (this.context.state === 'suspended') void this.context.resume().catch(() => {});
        } catch (_) { /* Visual call status remains available. */ }
    }
    stop() {
        clearTimeout(this.timer);
        this.timer = null;
        this.nodes.forEach((node) => { try { node.stop(); } catch (_) {} });
        this.nodes = [];
        this.kind = null;
    }
    set(kind) {
        if (kind === this.kind) return;
        this.stop();
        this.kind = kind;
        const patterns = {
            calling: { frequencies: [440], on: 0.12, period: 1.5 },
            ringing: { frequencies: [440, 480], on: 2, period: 6 },
            busy: { frequencies: [480, 620], on: 0.5, period: 1, count: 4 },
            unavailable: { frequencies: [425], on: 0.25, period: 0.5, count: 6 },
            failed: { frequencies: [330], on: 0.6, period: 1, count: 2 }
        };
        const pattern = patterns[kind];
        if (!pattern || !this.context) return;
        let count = 0;
        const pulse = () => {
            if (this.kind !== kind) return;
            const now = this.context.currentTime;
            const gain = this.context.createGain();
            gain.gain.setValueAtTime(0, now);
            gain.gain.linearRampToValueAtTime(0.07, now + 0.01);
            gain.gain.setValueAtTime(0.07, now + pattern.on - 0.01);
            gain.gain.linearRampToValueAtTime(0, now + pattern.on);
            gain.connect(this.context.destination);
            let remaining = pattern.frequencies.length;
            pattern.frequencies.forEach((frequency) => {
                const osc = this.context.createOscillator();
                osc.frequency.value = frequency;
                osc.connect(gain);
                this.nodes.push(osc);
                osc.onended = () => {
                    osc.disconnect();
                    this.nodes = this.nodes.filter((node) => node !== osc);
                    if (--remaining === 0) gain.disconnect();
                };
                osc.start(now);
                osc.stop(now + pattern.on);
            });
            count += 1;
            if (!pattern.count || count < pattern.count) {
                this.timer = setTimeout(pulse, pattern.period * 1000);
            }
        };
        try { pulse(); } catch (_) { this.stop(); }
    }
    update(status, sipStatus, cause, answered = false) {
        const code = Number(sipStatus);
        const terminal = ['ended', 'completed', 'failed', 'busy'].includes(status);
        if (terminal) {
            if ([486, 600].includes(code) || cause === 'USER_BUSY' || status === 'busy') this.set('busy');
            else if (code === 503) this.set('unavailable');
            else if (!answered && (status === 'failed' || code >= 400 ||
                (cause && !['NORMAL_CLEARING', 'ORIGINATOR_CANCEL'].includes(cause)))) this.set('failed');
            else this.stop();
        } else if (status === 'ringing') this.set('ringing');
        else if (['trying', 'queued'].includes(status)) this.set('calling');
        else this.stop();
    }
}
window.DialerFeedback = DialerFeedback;
