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
    }

    get isConfigured() {
        return Boolean(this.wsUrl && this.domain && this.username && this.password && this.remoteAudio);
    }

    async ensureClient() {
        if (!this.isConfigured) {
            throw new Error("WebRTC configuration is incomplete");
        }
        if (this.simpleUser) {
            return this.simpleUser;
        }
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
        await this.simpleUser.connect();
        await this.simpleUser.register();
        this.connected = true;
        return this.simpleUser;
    }

    async joinConference(conferenceName) {
        if (!conferenceName) {
            throw new Error("Conference name is required");
        }
        const client = await this.ensureClient();
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
        await client.call(`sip:${conferenceName}@${this.domain}`);
        this.currentConference = conferenceName;
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
}

if (typeof window !== "undefined") {
    window.DialerWebRTC = DialerWebRTC;
}

export default DialerWebRTC;
