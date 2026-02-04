const db = require('../db');

const LEAD_COMPLETION_STATUSES = ['called', 'failed'];

const validateCampaignReady = (campaignRow) => {
  if (!campaignRow) {
    throw new Error('Campaign not found.');
  }
  if (campaignRow.import_status !== 'completed') {
    throw new Error('Campaign import is still in progress.');
  }
  if (!campaignRow.imported_rows) {
    throw new Error('Campaign has no leads to dial.');
  }
};

const reserveNextLead = async (client, { campaignId, agent }) => {
  const result = await client.query(
    `WITH candidate AS (
        SELECT id, phone
          FROM campaign_leads
         WHERE campaign_id = $1
           AND status = 'new'
         ORDER BY id
         FOR UPDATE SKIP LOCKED
         LIMIT 1
      )
      UPDATE campaign_leads AS leads
         SET status = 'in_progress',
             agent = $2,
             reserved_at = NOW(),
             updated_at = NOW()
        FROM candidate
       WHERE leads.id = candidate.id
    RETURNING leads.id, leads.phone`,
    [campaignId, agent]
  );

  return result.rows[0] || null;
};

const finalizeLeadIfNeeded = async (client, { leadId, campaignId, agent, status }) => {
  if (!leadId) {
    return;
  }
  if (!status || !LEAD_COMPLETION_STATUSES.includes(status)) {
    throw new Error('Invalid lead status supplied.');
  }

  const result = await client.query(
    `UPDATE campaign_leads
        SET status = $1,
            updated_at = NOW()
      WHERE id = $2
        AND campaign_id = $3
        AND agent = $4
    RETURNING id`,
    [status, leadId, campaignId, agent]
  );

  if (result.rowCount === 0) {
    throw new Error('Unable to update lead status.');
  }
};

const startRun = async ({ userId, campaignId, agent }) => {
  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');

    const campaignResult = await client.query(
      'SELECT id, import_status, imported_rows FROM campaigns WHERE id = $1 FOR UPDATE',
      [campaignId]
    );
    validateCampaignReady(campaignResult.rows[0]);

    await client.query(
      `INSERT INTO campaign_runs (user_id, campaign_id, agent, is_running, created_at, updated_at)
       VALUES ($1, $2, $3, true, NOW(), NOW())
       ON CONFLICT (user_id)
     DO UPDATE SET campaign_id = EXCLUDED.campaign_id,
                   agent = EXCLUDED.agent,
                   is_running = true,
                   updated_at = NOW()`,
      [userId, campaignId, agent]
    );

    await client.query(
      `UPDATE campaign_leads
          SET status = 'new',
              agent = NULL,
              reserved_at = NULL,
              updated_at = NOW()
        WHERE campaign_id = $1
          AND agent = $2
          AND status = 'in_progress'`,
      [campaignId, agent]
    );

    const nextLead = await reserveNextLead(client, { campaignId, agent });

    await client.query('COMMIT');
    return nextLead;
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
};

const stopRun = async ({ userId }) => {
  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');
    const runResult = await client.query(
      'SELECT campaign_id, agent FROM campaign_runs WHERE user_id = $1 FOR UPDATE',
      [userId]
    );

    if (runResult.rowCount === 0) {
      await client.query('COMMIT');
      return;
    }

    const run = runResult.rows[0];

    await client.query('UPDATE campaign_runs SET is_running = false, updated_at = NOW() WHERE user_id = $1', [userId]);

    await client.query(
      `UPDATE campaign_leads
          SET status = 'new',
              agent = NULL,
              reserved_at = NULL,
              updated_at = NOW()
        WHERE campaign_id = $1
          AND agent = $2
          AND status = 'in_progress'`,
      [run.campaign_id, run.agent]
    );

    await client.query('COMMIT');
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
};

const nextLead = async ({ userId, lastLeadId, lastLeadStatus }) => {
  const client = await db.pool.connect();
  try {
    await client.query('BEGIN');
    const runResult = await client.query(
      'SELECT campaign_id, agent, is_running FROM campaign_runs WHERE user_id = $1 FOR UPDATE',
      [userId]
    );

    if (runResult.rowCount === 0 || !runResult.rows[0].is_running) {
      throw new Error('No active campaign run.');
    }
    const run = runResult.rows[0];

    if (lastLeadId) {
      await finalizeLeadIfNeeded(client, {
        leadId: lastLeadId,
        campaignId: run.campaign_id,
        agent: run.agent,
        status: lastLeadStatus || 'called'
      });
    }

    const nextLeadRow = await reserveNextLead(client, {
      campaignId: run.campaign_id,
      agent: run.agent
    });

    if (!nextLeadRow) {
      await client.query('UPDATE campaign_runs SET is_running = false, updated_at = NOW() WHERE user_id = $1', [userId]);
    }

    await client.query('COMMIT');
    return nextLeadRow;
  } catch (error) {
    await client.query('ROLLBACK');
    throw error;
  } finally {
    client.release();
  }
};

module.exports = {
  startRun,
  stopRun,
  nextLead
};
