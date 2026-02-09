const db = require('../db');
const config = require('../config');
const { emitSocketEvent } = require('../socket');

const presenceMinutes = config.metrics?.presenceMinutes || 5;
const activityWindowHours = config.metrics?.activityWindowHours || 24;

const fetchDashboardMetrics = async () => {
  const presenceQuery = await db.query(
    `
      SELECT
        COUNT(*)::int AS total_users,
        COUNT(*) FILTER (
          WHERE last_seen_at IS NOT NULL
            AND last_seen_at >= NOW() - INTERVAL '${presenceMinutes} minutes'
        )::int AS active_users
      FROM users
    `
  );

  const totalUsers = presenceQuery.rows[0]?.total_users || 0;
  const activeUsers = presenceQuery.rows[0]?.active_users || 0;
  const offlineUsers = Math.max(totalUsers - activeUsers, 0);

  const dialingQuery = await db.query(
    `SELECT COALESCE(COUNT(DISTINCT user_id), 0)::int AS dialing_users FROM campaign_runs WHERE is_running = true`
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

module.exports = {
  fetchDashboardMetrics,
  scheduleMetricsBroadcast
};
