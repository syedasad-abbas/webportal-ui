'use strict';

const FAILURE_PATTERNS = {
  'product-bug': [
    /404/, /500/, /status code 5/, /error exception/i, /exception/i,
    /cannot read properties/i, /not found/i, /failed to load/i,
  ],
  'test-bug': [
    /expected .* but/, /assertion failed/i, /missing .*control/i,
    /not visible/i, /not selected/i, /did not/, /timed out/i,
  ],
  'environment-blocker': [
    /not configured/i, /unavailable/i, /connection refused/i,
    /timeout/i, /permission denied/i, /external-service/i,
    /docker|container|driver/i,
  ],
};

function classifyFailure(failure, context = {}) {
  const text = `${failure.name || ''} ${failure.detail || ''}`.toLowerCase();
  const apiTrace = Array.isArray(context.apiTrace) ? context.apiTrace : [];
  const apiFailure = apiTrace.some((entry) => {
    const status = Number(entry.status);
    return status >= 400 && status <= 599;
  });

  if (apiFailure && /404|500|503|502|504/.test(text)) {
    return { classification: 'product-bug', reason: 'API trace contains a failing HTTP response' };
  }
  if (FAILURE_PATTERNS['environment-blocker'].some((pattern) => pattern.test(text))) {
    return { classification: 'environment-blocker', reason: 'failure points to setup or external state' };
  }
  if (FAILURE_PATTERNS['product-bug'].some((pattern) => pattern.test(text))) {
    return { classification: 'product-bug', reason: 'failure evidence matches an application defect' };
  }
  if (FAILURE_PATTERNS['test-bug'].some((pattern) => pattern.test(text))) {
    return { classification: 'test-bug', reason: 'failure indicates an invalid or stale assertion' };
  }
  return { classification: 'test-bug', reason: 'no reliable product or environment signal was available' };
}

function validateReport(report) {
  if (!report || typeof report !== 'object') {
    throw new Error('report must be an object');
  }
  for (const key of ['passed', 'failed']) {
    if (!Array.isArray(report[key])) throw new Error(`${key} must be an array`);
  }
  if (report.blocked !== undefined && !Array.isArray(report.blocked)) {
    throw new Error('blocked must be an array');
  }
  for (const entry of [...report.passed, ...report.failed, ...(report.blocked || [])]) {
    if (!entry || typeof entry.name !== 'string' || !entry.name.trim()) {
      throw new Error('every check must have a nonempty name');
    }
  }
  return { valid: true };
}

function shouldRetry(state) {
  return Number(state.retries || 0) < Number(state.maxRetries || 0);
}

if (require.main === module) {
  const report = JSON.parse(process.argv[2] || '{}');
  const result = validateReport(report);
  console.log(JSON.stringify(result));
}

module.exports = {
  classifyFailure,
  validateReport,
  shouldRetry,
};
