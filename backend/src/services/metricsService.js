const db = require('../db');
const config = require('../config');
const { emitSocketEvent } = require('../socket');

const presenceMinutes = config.metrics?.presenceMinutes || 5;
const activityWindowHours = config.metrics?.activityWindowHours || 24;
const broadcastIntervalSeconds = config.metrics?.broadcastIntervalSeconds || 15;
const activityTimezone = config.metrics?.activityTimezone || 'Asia/Karachi';
const activityAnchorHour = Number.isFinite(config.metrics?.activityAnchorHour)
  ? config.metrics.activityAnchorHour
  : 21;

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
         WHERE ended_at IS NULL
           AND (connected_at IS NOT NULL OR status = 'in_call')
      ),
      dialing_calls AS (
        SELECT DISTINCT user_id
          FROM call_logs
         WHERE ended_at IS NULL
           AND status IN ('queued', 'ringing', 'trying')
      )
      SELECT COALESCE(COUNT(DISTINCT d.user_id), 0)::int AS dialing_users
        FROM dialing_calls d
       WHERE d.user_id NOT IN (SELECT user_id FROM in_call)
    `
  );

  const inCallQuery = await db.query(
    `SELECT COALESCE(COUNT(DISTINCT user_id), 0)::int AS in_call_users
       FROM call_logs
      WHERE ended_at IS NULL
        AND (connected_at IS NOT NULL OR status = 'in_call')`
  );

  const activityQuery = await db.query(
    `
      WITH local_now AS (
        SELECT (NOW() AT TIME ZONE $1) AS local_now
      ),
      anchor AS (
        SELECT (date_trunc('day', local_now) + make_interval(hours => $2)) AS anchor_local
        FROM local_now
      ),
      window_start AS (
        SELECT CASE
          WHEN local_now < anchor_local THEN anchor_local - INTERVAL '1 day'
          ELSE anchor_local
        END AS window_start_local
        FROM local_now, anchor
      ),
      window_bounds AS (
        SELECT
          ((window_start_local AT TIME ZONE $1) AT TIME ZONE 'UTC') AS window_start_utc,
          ((window_start_local AT TIME ZONE $1) AT TIME ZONE 'UTC') + INTERVAL '1 day' AS window_end_utc
        FROM window_start
      )
      SELECT
        COALESCE(COUNT(*), 0)::int AS total_calls,
        COALESCE(SUM(
          CASE
            WHEN COALESCE(sip_status, 0) = 200 OR status = 'completed' THEN 1
            ELSE 0
          END
        ), 0)::int AS ok_200,
        COALESCE(SUM(
          CASE
            WHEN COALESCE(sip_status, 0) = 503
              AND NOT (COALESCE(sip_status, 0) = 200 OR status = 'completed')
            THEN 1 ELSE 0
          END
        ), 0)::int AS err_503,
        COALESCE(SUM(
          CASE
            WHEN NOT (COALESCE(sip_status, 0) = 200 OR status = 'completed')
              AND COALESCE(sip_status, 0) <> 503
            THEN 1 ELSE 0
          END
        ), 0)::int AS other_calls
      FROM call_logs, window_bounds
      WHERE COALESCE(created_at, ended_at, connected_at) >= window_bounds.window_start_utc
        AND COALESCE(created_at, ended_at, connected_at) < window_bounds.window_end_utc
    `,
    [activityTimezone, activityAnchorHour]
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
      windowHours: activityWindowHours,
      windowTimezone: activityTimezone,
      windowAnchorHour: activityAnchorHour
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
