import { SimpleUser } from "sip.js/lib/platform/web";

const buildIceServers = (servers) => {
    if (!servers || servers.length === 0) {
        return [];
    }
    return servers.map((entry) => {
        if (typeof entry === "string") {
            return { urls: entry };
        }
        if (typeof entry === "object" && entry !== null && entry.urls) {
            return entry;
        }
        return null;
    }).filter(Boolean);
};

class DialerWebRTC {
    constructor(config = {}) {
        this.wsUrl = config.wsUrl;
        this.domain = config.domain;
        this.username = config.username;
        this.password = config.password;
        this.remoteAudioSelector = config.remoteAudioSelector || "#dialer-audio";
        this.remoteAudio = document.querySelector(this.remoteAudioSelector) || null;
        this.iceServers = buildIceServers(config.iceServers || []);
        this.simpleUser = null;
        this.currentConference = null;
        this.connected = false;
        this.ensurePromise = null;
        this.sessionOp = Promise.resolve();
        this.pendingMute = false;
        this.reconnectTimer = null;
        this.shouldReconnect = true;
        this.mediaTimer = null;
        this.mediaSession = null;
        this.playbackBlocked = false;
    }

    reportAudioStatus(message, hasError = false, audioReady = false) {
        window.dispatchEvent(new CustomEvent("dialer:audio-status", {
            detail: { message, hasError, audioReady, playbackBlocked: this.playbackBlocked }
        }));
    }

    async resumeAudio() {
        const session = this.simpleUser?.session;
        if (!session) return;
        if (!this.remoteAudio) return;
        this.remoteAudio.muted = false;
        try {
            await this.remoteAudio.play();
            if (this.mediaSession !== session) return;
            this.playbackBlocked = false;
        } catch (error) {
            if (this.mediaSession !== session) return;
            this.playbackBlocked = true;
            this.reportAudioStatus("Click Enable audio to hear the call.", true);
            console.warn("Remote audio playback failed", error);
        }
    }

    stopMediaMonitor() {
        window.clearInterval(this.mediaTimer);
        this.mediaTimer = null;
        this.mediaSession = null;
        this.playbackBlocked = false;
        this.reportAudioStatus("Browser audio idle");
    }

    startMediaMonitor() {
        this.stopMediaMonitor();
        const session = this.simpleUser?.session;
        const peer = session?.sessionDescriptionHandler?.peerConnection;
        if (!peer) return;
        this.mediaSession = session;
        let lastPackets = 0;
        let lastReceivedAt = Date.now();
        let checking = false;
        this.reportAudioStatus("Checking browser audio…");
        void this.resumeAudio();
        this.mediaTimer = window.setInterval(async () => {
            if (checking || this.mediaSession !== session) return;
            checking = true;
            try {
                const stats = await peer.getStats();
                if (this.mediaSession !== session) return;
                let packets = 0;
                stats.forEach((entry) => {
                    if (entry.type === "inbound-rtp" && (entry.kind || entry.mediaType) === "audio") {
                        packets += entry.packetsReceived || 0;
                    }
                });
                const received = packets > lastPackets;
                if (received) lastReceivedAt = Date.now();
                lastPackets = packets;
                if (["failed", "disconnected", "closed"].includes(peer.connectionState)) {
                    this.reportAudioStatus("Browser audio connection lost.", true);
                } else if (this.playbackBlocked) {
                    this.reportAudioStatus("Click Enable audio to hear the call.", true);
                } else if (Date.now() - lastReceivedAt > 10000) {
                    this.reportAudioStatus("No incoming audio packets; check the media connection.", true);
                } else if (received) {
                    this.reportAudioStatus("Receiving browser audio", false, true);
                    void this.resumeAudio();
                } else {
                    void this.resumeAudio();
                }
            } catch (error) {
                console.warn("Unable to inspect incoming audio", error);
            } finally {
                checking = false;
            }
        }, 2000);
    }

    get isConfigured() {
        return Boolean(this.wsUrl && this.domain && this.username && this.password && this.remoteAudio);
    }

    async resetClient() {
        this.stopMediaMonitor();
        if (!this.simpleUser) {
            return;
        }
        try {
            this.shouldReconnect = false;
            await this.simpleUser.disconnect();
        } catch (error) {
            console.error("Failed to reset WebRTC client", error);
        }
        this.simpleUser = null;
        this.connected = false;
        this.shouldReconnect = true;
    }

    scheduleReconnect() {
        if (!this.shouldReconnect || this.reconnectTimer) return;
        this.reconnectTimer = window.setTimeout(async () => {
            this.reconnectTimer = null;
            try {
                await this.ensureClient();
            } catch (error) {
                console.warn("WebRTC reconnect failed", error);
                this.scheduleReconnect();
            }
        }, 2000);
    }

    isClientConnected() {
        if (!this.simpleUser) {
            return false;
        }
        if (typeof this.simpleUser.isConnected === "function") {
            return this.simpleUser.isConnected();
        }
        return this.connected;
    }

    withSessionOp(operation) {
        const run = this.sessionOp.then(() => operation(), () => operation());
        this.sessionOp = run.catch(() => {});
        return run;
    }

    shouldRetryJoin(error) {
        const message = (error?.message || "").toLowerCase();
        return (
            message.includes("peer connection undefined") ||
            message.includes("per connection undefined") ||
            message.includes("session already exists") ||
            message.includes("not connected") ||
            message.includes("disconnected")
        );
    }

    async dialConference(conferenceName) {
        const target = `sip:${conferenceName}@${this.domain}`;
        let client = await this.ensureClient();
        if (client.session) {
            try {
                await client.hangup();
            } catch (error) {
                console.error("Failed to hangup previous session", error);
            }
            client.session = null;
        }

        try {
            await client.call(target);
            this.currentConference = conferenceName;
            if (this.pendingMute) {
                await this.applyMuteState(this.pendingMute);
            }
            return;
        } catch (error) {
            if (!this.shouldRetryJoin(error)) {
                throw error;
            }
        }

        await this.resetClient();
        client = await this.ensureClient();
        await client.call(target);
        this.currentConference = conferenceName;
        if (this.pendingMute) {
            await this.applyMuteState(this.pendingMute);
        }
    }

    async ensureClient() {
        if (!this.isConfigured) {
            throw new Error("WebRTC configuration is incomplete");
        }
        if (this.simpleUser && this.isClientConnected()) {
            return this.simpleUser;
        }
        if (this.ensurePromise) {
            return this.ensurePromise;
        }
        this.ensurePromise = (async () => {
            if (!this.simpleUser) {
                const aor = `sip:${this.username}@${this.domain}`;
                const options = {
                    aor,
                    // This wrapper owns reconnects; avoid a competing SIP.js retry loop.
                    reconnectionAttempts: 0,
                    media: {
                        constraints: { audio: true, video: false },
                        remote: { audio: this.remoteAudio }
                    },
                    userAgentOptions: {
                        authorizationUsername: this.username,
                        authorizationPassword: this.password,
                        transportOptions: {
                            server: this.wsUrl,
                            connectionTimeout: 15,
                            keepAliveInterval: 25
                        },
                        sessionDescriptionHandlerFactoryOptions: {
                            peerConnectionConfiguration: {
                                iceServers: this.iceServers
                            }
                        }
                    },
                    delegate: {
                        onServerConnect: () => {
                            this.connected = true;
                            if (this.reconnectTimer) {
                                window.clearTimeout(this.reconnectTimer);
                                this.reconnectTimer = null;
                            }
                        },
                        onServerDisconnect: () => {
                            this.stopMediaMonitor();
                            this.connected = false;
                            this.currentConference = null;
                            if (this.shouldReconnect) window.dispatchEvent(new CustomEvent("dialer:sip-disconnected"));
                            this.scheduleReconnect();
                        },
                        onCallReceived: () => {
                            const remoteIdentity = this.simpleUser?.session?.remoteIdentity;
                            window.dispatchEvent(new CustomEvent("dialer:sip-incoming", {
                                detail: {
                                    callerIdNumber: remoteIdentity?.uri?.user || remoteIdentity?.displayName || "Unknown"
                                }
                            }));
                        },
                        onCallAnswered: () => {
                            this.startMediaMonitor();
                            void this.applyMuteState(this.pendingMute);
                            window.dispatchEvent(new CustomEvent("dialer:sip-answered"));
                        },
                        onCallHangup: () => {
                            this.stopMediaMonitor();
                            this.currentConference = null;
                            window.dispatchEvent(new CustomEvent("dialer:sip-hangup"));
                        }
                    }
                };
                this.simpleUser = new SimpleUser(this.wsUrl, options);
            }
            if (!this.isClientConnected()) {
                await this.simpleUser.connect();
                await this.simpleUser.register();
                this.connected = true;
            }
            return this.simpleUser;
        })();
        try {
            return await this.ensurePromise;
        } finally {
            this.ensurePromise = null;
        }
    }

    async joinConference(conferenceName) {
        return this.withSessionOp(async () => {
            if (!conferenceName) {
                throw new Error("Conference name is required");
            }
            if (this.currentConference === conferenceName && this.simpleUser?.session) {
                return;
            }
            await this.dialConference(conferenceName);
        });
    }

    async answerIncoming() {
        if (!this.simpleUser?.session || typeof this.simpleUser.answer !== "function") {
            throw new Error("No incoming SIP call is available");
        }
        await this.simpleUser.answer();
    }

    async declineIncoming() {
        if (!this.simpleUser?.session || typeof this.simpleUser.decline !== "function") {
            return;
        }
        await this.simpleUser.decline();
    }

    async leaveConference() {
        return this.withSessionOp(async () => {
            this.stopMediaMonitor();
            if (this.simpleUser?.session) {
                try {
                    await this.simpleUser.hangup();
                } catch (error) {
                    console.error("Unable to hangup session", error);
                }
            }
            this.currentConference = null;
            // Keep registration/transport ready for the next call and inbound calls.
        });
    }

    async disconnect() {
        this.shouldReconnect = false;
        if (this.reconnectTimer) {
            window.clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
        if (this.simpleUser) {
            try {
                await this.simpleUser.unregister();
            } catch (error) {
                console.error("Failed to unregister WebRTC client", error);
            }
        }
        await this.leaveConference();
        await this.simpleUser?.disconnect();
        this.simpleUser = null;
        this.connected = false;
    }

    async applyMuteState(muted) {
        const session = this.simpleUser?.session;
        const handler = session?.sessionDescriptionHandler;
        const peer = handler?.peerConnection;
        if (!peer) {
            return;
        }
        peer.getSenders().forEach((sender) => {
            if (sender.track && sender.track.kind === "audio") {
                sender.track.enabled = !muted;
            }
        });
    }

    async setMuted(muted) {
        this.pendingMute = muted;
        if (!this.simpleUser?.session) {
            return;
        }
        await this.applyMuteState(muted);
    }

    async sendDtmf(digits) {
        if (!this.simpleUser?.session || typeof this.simpleUser.sendDTMF !== "function") {
            throw new Error("No active browser call is available for DTMF");
        }
        await this.simpleUser.sendDTMF(String(digits));
    }
}

if (typeof window !== "undefined") {
    window.DialerWebRTC = DialerWebRTC;
}

export default DialerWebRTC;
