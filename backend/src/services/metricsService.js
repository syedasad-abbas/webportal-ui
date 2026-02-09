const db = require('../db');
const config = require('../config');
const { emitSocketEvent } = require('../socket');

const presenceMinutes = config.metrics?.presenceMinutes || 5;
const activityWindowHours = config.metrics?.activityWindowHours || 24;
const broadcastIntervalSeconds = config.metrics?.broadcastIntervalSeconds || 15;
const dialingWindowMinutes = config.metrics?.dialingWindowMinutes || 5;

const fetchDashboardMetrics = async () => {
  const presenceQuery = await db.query(
    `
      SELECT
        COUNT(*)::int AS total_users,
        COALESCE((
          SELECT COUNT(DISTINCT user_id)::int
          FROM sessions
          WHERE user_id IS NOT NULL
            AND last_activity >= EXTRACT(EPOCH FROM NOW())::int - ($1 * 60)
        ), 0)::int AS active_users
      FROM users
    `
    , [presenceMinutes]
  );

  const totalUsers = presenceQuery.rows[0]?.total_users || 0;
  const activeUsers = presenceQuery.rows[0]?.active_users || 0;
  const offlineUsers = Math.max(totalUsers - activeUsers, 0);

  const dialingQuery = await db.query(
    `
      WITH in_call AS (
        SELECT DISTINCT user_id
          FROM call_logs
         WHERE connected_at IS NOT NULL
           AND ended_at IS NULL
      ),
      dialing_calls AS (
        SELECT DISTINCT user_id
          FROM call_logs
         WHERE connected_at IS NULL
           AND ended_at IS NULL
           AND status IN ('queued', 'ringing', 'trying')
           AND created_at IS NOT NULL
           AND created_at >= NOW() - INTERVAL '${dialingWindowMinutes} minutes'
      ),
      dialing_campaigns AS (
        SELECT DISTINCT user_id
          FROM campaign_runs
         WHERE is_running = true
      )
      SELECT COALESCE(COUNT(DISTINCT d.user_id), 0)::int AS dialing_users
        FROM (
          SELECT user_id FROM dialing_calls
          UNION
          SELECT user_id FROM dialing_campaigns
        ) d
       WHERE d.user_id NOT IN (SELECT user_id FROM in_call)
    `
  );

  const inCallQuery = await db.query(
    `SELECT COALESCE(COUNT(DISTINCT user_id), 0)::int AS in_call_users
       FROM call_logs
      WHERE connected_at IS NOT NULL
        AND ended_at IS NULL`
  );

  const activityQuery = await db.query(
    `
      SELECT
        COALESCE(COUNT(*), 0)::int AS total_calls,
        COALESCE(SUM(CASE WHEN sip_status = 200 THEN 1 ELSE 0 END), 0)::int AS ok_200,
        COALESCE(SUM(CASE WHEN sip_status = 503 THEN 1 ELSE 0 END), 0)::int AS err_503,
        COALESCE(SUM(
          CASE
            WHEN sip_status IS NOT NULL AND sip_status NOT IN (200, 503) THEN 1
            ELSE 0
          END
        ), 0)::int AS other_calls
      FROM call_logs
      WHERE created_at >= NOW() - INTERVAL '${activityWindowHours} hours'
    `
  );

  const activity = activityQuery.rows[0] || {};

  return {
    generatedAt: new Date().toISOString(),
    presence: {
      total: totalUsers,
      active: activeUsers,
      offline: offlineUsers,
      windowMinutes: presenceMinutes
    },
    dialingUsers: dialingQuery.rows[0]?.dialing_users || 0,
    inCallUsers: inCallQuery.rows[0]?.in_call_users || 0,
    activity: {
      total: activity.total_calls || 0,
      ok200: activity.ok_200 || 0,
      err503: activity.err_503 || 0,
      other: activity.other_calls || 0,
      windowHours: activityWindowHours
    }
  };
};

let broadcastTimer = null;
let broadcastInterval = null;

const scheduleMetricsBroadcast = () => {
  if (broadcastTimer) {
    return;
  }

  broadcastTimer = setTimeout(async () => {
    broadcastTimer = null;
    try {
      const snapshot = await fetchDashboardMetrics();
      emitSocketEvent('dashboard.metrics', snapshot);
    } catch (err) {
      console.warn('[metrics] failed to broadcast metrics', err.message);
    }
  }, 250);
};

const startMetricsBroadcasting = () => {
  if (broadcastInterval) {
    return;
  }

  const intervalMs = Math.max(broadcastIntervalSeconds, 5) * 1000;
  const emitSnapshot = async () => {
    try {
      const snapshot = await fetchDashboardMetrics();
      emitSocketEvent('dashboard.metrics', snapshot);
    } catch (err) {
      console.warn('[metrics] failed to broadcast metrics', err.message);
    }
  };

  emitSnapshot();
  broadcastInterval = setInterval(emitSnapshot, intervalMs);
};

module.exports = {
  fetchDashboardMetrics,
  scheduleMetricsBroadcast,
  startMetricsBroadcasting
};
