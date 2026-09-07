const rateLimitMap = new Map();

const createRateLimiter = (windowMs, maxAttempts) => {
  return (req, res, next) => {
    const key = `${req.ip}:${req.originalUrl}`;
    const now = Date.now();
    const record = rateLimitMap.get(key);

    if (!record || now - record.startTime > windowMs) {
      rateLimitMap.set(key, { count: 1, startTime: now });
      return next();
    }

    if (record.count >= maxAttempts) {
      return res.status(429).json({ message: 'Too many attempts. Please try again later.' });
    }

    record.count += 1;
    next();
  };
};

const authRateLimiter = createRateLimiter(15 * 60 * 1000, 5);

module.exports = { authRateLimiter };
