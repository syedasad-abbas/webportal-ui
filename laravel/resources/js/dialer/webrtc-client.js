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
        this.pendingMute = false;
    }

    get isConfigured() {
        return Boolean(this.wsUrl && this.domain && this.username && this.password && this.remoteAudio);
    }

    async resetClient() {
        if (!this.simpleUser) {
            return;
        }
        try {
            await this.simpleUser.disconnect();
        } catch (error) {
            console.error("Failed to reset WebRTC client", error);
        }
        this.simpleUser = null;
        this.connected = false;
    }

    async ensureClient() {
        if (!this.isConfigured) {
            throw new Error("WebRTC configuration is incomplete");
        }
        if (this.simpleUser && this.connected) {
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
                    media: {
                        constraints: { audio: true, video: false },
                        remote: { audio: this.remoteAudio }
                    },
                    userAgentOptions: {
                        authorizationUsername: this.username,
                        authorizationPassword: this.password,
                        transportOptions: {
                            server: this.wsUrl
                        },
                        sessionDescriptionHandlerFactoryOptions: {
                            peerConnectionOptions: {
                                rtcConfiguration: {
                                    iceServers: this.iceServers
                                }
                            }
                        }
                    }
                };
                this.simpleUser = new SimpleUser(this.wsUrl, options);
            }
            if (!this.connected) {
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
        if (!conferenceName) {
            throw new Error("Conference name is required");
        }
        await this.resetClient();
        let client = await this.ensureClient();
        if (this.currentConference === conferenceName) {
            return;
        }
        if (client.session) {
            try {
                await client.hangup();
            } catch (error) {
                console.error("Failed to hangup previous session", error);
            }
        }
        try {
            await client.call(`sip:${conferenceName}@${this.domain}`);
            this.currentConference = conferenceName;
            if (this.pendingMute) {
                await this.applyMuteState(this.pendingMute);
            }
        } catch (error) {
            const message = error?.message || "";
            if (/peer connection undefined|per connection undefined/i.test(message)) {
                await this.resetClient();
                client = await this.ensureClient();
                await client.call(`sip:${conferenceName}@${this.domain}`);
                this.currentConference = conferenceName;
                return;
            }
            throw error;
        }
    }

    async leaveConference() {
        if (!this.simpleUser) {
            return;
        }
        if (this.simpleUser.session) {
            try {
                await this.simpleUser.hangup();
            } catch (error) {
                console.error("Unable to hangup session", error);
            }
        }
        this.currentConference = null;
    }

    async disconnect() {
        await this.leaveConference();
        if (!this.simpleUser) {
            return;
        }
        try {
            await this.simpleUser.unregister();
        } catch (error) {
            console.error("Failed to unregister WebRTC client", error);
        }
        try {
            await this.simpleUser.disconnect();
        } catch (error) {
            console.error("Failed to disconnect WebRTC client", error);
        }
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
}

if (typeof window !== "undefined") {
    window.DialerWebRTC = DialerWebRTC;
}

export default DialerWebRTC;
