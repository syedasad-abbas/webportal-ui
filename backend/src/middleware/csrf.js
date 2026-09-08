const crypto = require('crypto');

const CSRF_COOKIE_NAME = 'csrf_token';
const CSRF_HEADER_NAME = 'x-csrf-token';

const parseCookies = (cookieHeader) => {
  if (!cookieHeader) return {};
  return cookieHeader.split(';').reduce((acc, cookie) => {
    const [name, ...rest] = cookie.trim().split('=');
    if (name) {
      acc[name] = rest.join('=');
    }
    return acc;
  }, {});
};

const generateToken = () => crypto.randomBytes(32).toString('hex');

const getCsrfToken = (req) => {
  const cookies = parseCookies(req.headers.cookie);
  return cookies[CSRF_COOKIE_NAME];
};

const setCsrfTokenCookie = (res, token) => {
  res.cookie(CSRF_COOKIE_NAME, token, {
    httpOnly: false,
    secure: false,
    sameSite: 'lax',
    path: '/',
    maxAge: 24 * 60 * 60 * 1000
  });
};

const validateCsrfToken = (req, res, next) => {
  const method = req.method;
  if (['GET', 'HEAD', 'OPTIONS'].includes(method)) {
    return next();
  }

  if (
    process.env.BACKEND_INTERNAL_TOKEN &&
    req.headers['x-internal-token'] === process.env.BACKEND_INTERNAL_TOKEN
  ) {
    return next();
  }

  const cookieToken = getCsrfToken(req);
  const headerToken = req.headers[CSRF_HEADER_NAME] || req.headers[CSRF_HEADER_NAME.toLowerCase()];

  if (!cookieToken || !headerToken || cookieToken !== headerToken) {
    return res.status(403).json({ message: 'Invalid CSRF token' });
  }

  next();
};

const csrfProtection = (req, res, next) => {
  const cookieToken = getCsrfToken(req);
  if (!cookieToken) {
    const token = generateToken();
    setCsrfTokenCookie(res, token);
  }
  next();
};

module.exports = {
  csrfProtection,
  validateCsrfToken,
  CSRF_HEADER_NAME
};
