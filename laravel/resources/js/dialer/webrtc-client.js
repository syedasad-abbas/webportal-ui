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
                    },
                    delegate: {
                        onServerConnect: () => {
                            this.connected = true;
                        },
                        onServerDisconnect: () => {
                            this.connected = false;
                            this.currentConference = null;
                        },
                        onCallHangup: () => {
                            this.currentConference = null;
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

    async leaveConference() {
        return this.withSessionOp(async () => {
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
        });
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
