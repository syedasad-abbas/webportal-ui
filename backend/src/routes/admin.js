/**
 * Admin routes for authentication, user, group, and carrier management
 * Access restricted to admin and superadmin roles
 */
const express = require('express');
const Joi = require('joi');
// Service layer dependencies
// Business logic services
// Application services
const authService = require('../services/authService');
const userService = require('../services/userService');
const groupService = require('../services/groupService');
const carrierService = require('../services/carrierService');
const { authenticate } = require('../middleware/auth');
const config = require('../config');
// End workers
// Create router
const router = express.Router();
// Helper function to check for internal token
const hasInternalToken = (req) => {
  const token = config.internalTokens?.backendSync;
  if (!token) {
    return false;
  }
  return req.get('x-internal-token') === token;
};
// Middleware to allow internal token or admin authentication
const allowInternalOrAdmin = (req, res, next) => {
  if (hasInternalToken(req)) {
    return next();
  }
  return authenticate(['admin', 'superadmin'])(req, res, next);
};
// Authentication routes
// User management routes
// Carrier management routes
router.post('/login', async (req, res) => {
  const schema = Joi.object({
    email: Joi.string().email({ tlds: { allow: false } }).required(),
    password: Joi.string().required()
  });
// Validate request body
  const { error, value } = schema.validate(req.body);
  if (error) {
    console.warn('[admin] user sync validation failed', {
      message: error.message,
      body: req.body
    });
    return res.status(400).json({ message: error.message });
  }

  try {
    const existing = await userService.getUserByEmail(value.email);
    await userService.upsertUser({
      fullName: existing?.full_name || value.email,
      email: value.email,
      password: value.password,
      groupId: existing?.group_id || null,
      carrierId: existing?.carrier_id || null,
      permissions: existing ? undefined : [],
      role: existing?.role || 'user',
      recordingEnabled: existing?.recording_enabled ?? true
    });
    const response = await authService.authenticate(value.email, value.password);
    return res.json(response);
  } catch (err) {
    console.warn('[admin/login] failed', { email: value.email, error: err.message });
    return res.status(401).json({ message: err.message });
  }
});
// User management routes
router.get('/users', authenticate(['admin', 'superadmin']), async (_req, res) => {
  const users = await userService.listUsers();
  return res.json(users);
});

router.get('/users/:userId', authenticate(['admin', 'superadmin']), async (req, res) => {
  try {
    const user = await userService.getUserById(req.params.userId);
    if (!user || user.role !== 'user') {
      return res.status(404).json({ message: 'User not found' });
    }
    return res.json(user);
  } catch (err) {
    return res.status(404).json({ message: 'User not found' });
  }
});
// Create new user
router.post('/users', authenticate(['admin', 'superadmin']), async (req, res) => {
  const schema = Joi.object({
    fullName: Joi.string().required(),
    email: Joi.string().email({ tlds: { allow: false } }).required(),
    password: Joi.string().min(6).required(),
    groupId: Joi.alternatives().try(Joi.number().integer(), Joi.string()).allow(null, '').optional(),
    carrierId: Joi.alternatives().try(Joi.number().integer(), Joi.string()).allow(null, '').optional(),
    permissions: Joi.array().items(Joi.string()).optional(),
    role: Joi.string().optional(),
    recordingEnabled: Joi.boolean().optional()
  });

  const { error, value } = schema.validate(req.body);
  if (error) {
    return res.status(400).json({ message: error.message });
  }

  try {
    const user = await userService.createUser(value);
    return res.status(201).json(user);
  } catch (err) {
    return res.status(500).json({ message: err.message });
  }
});
// Sync (create or update) user
router.post('/users/sync', allowInternalOrAdmin, async (req, res) => {
  const schema = Joi.object({
    fullName: Joi.string().allow('', null),
    email: Joi.string().email({ tlds: { allow: false } }).required(),
    password: Joi.string().min(6).allow(null, ''),
    groupId: Joi.alternatives().try(Joi.number().integer(), Joi.string()).allow(null, '').optional(),
    carrierId: Joi.alternatives().try(Joi.number().integer(), Joi.string()).allow(null, '').optional(),
    permissions: Joi.array().items(Joi.string()).optional(),
    role: Joi.string().optional(),
    recordingEnabled: Joi.boolean().optional()
  });
// Validate request body
  const { error, value } = schema.validate(req.body);
  if (error) {
    return res.status(400).json({ message: error.message });
  }

  try {
    const user = await userService.upsertUser({
      ...value,
      fullName: value.fullName || value.email,
      password: value.password || null
    });
    return res.json(user);
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});
// Update user
router.put('/users/:userId', authenticate(['admin', 'superadmin']), async (req, res) => {
  const schema = Joi.object({
    fullName: Joi.string().optional(),
    email: Joi.string().email({ tlds: { allow: false } }).optional(),
    password: Joi.string().min(6).allow('', null),
    groupId: Joi.alternatives().try(Joi.number().integer(), Joi.string()).allow(null, '').optional(),
    carrierId: Joi.alternatives().try(Joi.number().integer(), Joi.string()).allow(null, '').optional(),
    permissions: Joi.array().items(Joi.string()).optional(),
    recordingEnabled: Joi.boolean().optional()
  });
// Validate request body
  const { error, value } = schema.validate(req.body);
  if (error) {
    return res.status(400).json({ message: error.message });
  }

  try {
    const user = await userService.updateUser(req.params.userId, {
      ...value,
      password: value.password || undefined
    });
    return res.json(user);
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});
// Delete user
router.delete('/users/:userId', authenticate(['admin', 'superadmin']), async (req, res) => {
  try {
    await userService.deleteUser(req.params.userId);
    return res.status(204).send();
  } catch (err) {
    return res.status(400).json({ message: err.message });
  }
});
// Group management routes
router.get('/groups', authenticate(['admin', 'superadmin']), async (_req, res) => {
  const groups = await groupService.listGroups();
  return res.json(groups);
});
// Create new group
router.post('/groups', authenticate(['admin', 'superadmin']), async (req, res) => {
  const schema = Joi.object({
    name: Joi.string().required(),
    permissions: Joi.array().items(Joi.string()).default([])
  });
// Validate request body
  const { error, value } = schema.validate(req.body);
  if (error) {
    return res.status(400).json({ message: error.message });
  }

  const group = await groupService.createGroup(value);
  return res.status(201).json(group);
});
// Carrier management routes
router.get('/carriers', authenticate(['admin', 'superadmin']), async (_req, res) => {
  const carriers = await carrierService.listCarriers();
  return res.json(carriers);
});
// Get carrier by ID
router.get('/carriers/:carrierId', authenticate(['admin', 'superadmin']), async (req, res) => {
  const carrier = await carrierService.getCarrierById(req.params.carrierId);
  if (!carrier) {
    return res.status(404).json({ message: 'Carrier not found' });
  }
  return res.json(carrier);
});
// Create new carrier
router.post('/carriers', authenticate(['admin', 'superadmin']), async (req, res) => {
  const schema = Joi.object({
    name: Joi.string().required(),
    callerId: Joi.string().allow('', null),
    callerIdRequired: Joi.boolean().default(false),
    transport: Joi.string().valid('udp', 'tcp', 'tls').default('udp'),
    sipDomain: Joi.string().required(),
    sipPort: Joi.number().integer().min(1).max(65535).required(),
    outboundProxy: Joi.string().allow('', null),
    registrationRequired: Joi.boolean().default(false),
    registrationUsername: Joi.string().allow('', null),
    registrationPassword: Joi.string().allow('', null),
    prefix: Joi.string().allow('', null),
    prefixCallerId: Joi.string().allow('', null)
  });
// Validate request body
  const { error, value } = schema.validate(req.body);
  if (error) {
    return res.status(400).json({ message: error.message });
  }

  try {
    let carrier = await carrierService.createCarrier(value);
    if (value.prefix) {
      const prefixCaller =
        value.prefixCallerId || value.callerId || config.defaults.carrierCallerId || null;
      await carrierService.addPrefix({
        carrierId: carrier.id,
        prefix: value.prefix,
        callerId: prefixCaller
      });
      carrier = await carrierService.getCarrierById(carrier.id);
    }
    return res.status(201).json(carrier);
  } catch (err) {
    return res.status(400).json({ message: err.message || 'Unable to create carrier' });
  }
});
// Update carrier
router.put('/carriers/:carrierId', authenticate(['admin', 'superadmin']), async (req, res) => {
  const schema = Joi.object({
    name: Joi.string().optional(),
    callerId: Joi.string().allow('', null).optional(),
    callerIdRequired: Joi.boolean().truthy('1').falsy('0').optional(),
    transport: Joi.string().valid('udp', 'tcp', 'tls').optional(),
    sipDomain: Joi.string().allow('', null).optional(),
    sipPort: Joi.number().integer().min(1).max(65535).allow(null, '').optional(),
    outboundProxy: Joi.string().allow('', null).optional(),
    registrationRequired: Joi.boolean().truthy('1').falsy('0').optional(),
    registrationUsername: Joi.string().allow('', null).optional(),
    registrationPassword: Joi.string().allow('', null).optional(),
    prefix: Joi.string().allow('', null),
    prefixCallerId: Joi.string().allow('', null)
  });

  const { error, value } = schema.validate(req.body);
  if (error) {
    console.error('carrier update validation error:', error.message);
    return res.status(400).json({ message: error.message });
  }

  try {
    let carrier = await carrierService.updateCarrier(req.params.carrierId, value);
    if (value.prefix) {
      const prefixCaller =
        value.prefixCallerId || value.callerId || carrier.default_caller_id || config.defaults.carrierCallerId || null;
      await carrierService.upsertPrefix({
        carrierId: carrier.id,
        prefix: value.prefix,
        callerId: prefixCaller
      });
      carrier = await carrierService.getCarrierById(carrier.id);
    }
    return res.json(carrier);
  } catch (err) {
    console.error('carrier update error:', err);
    return res.status(400).json({ message: err.message || 'Unable to update carrier' });
  }
});
// Delete carrier
router.delete('/carriers/:carrierId', authenticate(['admin', 'superadmin']), async (req, res) => {
  try {
    await carrierService.deleteCarrier(req.params.carrierId);
    return res.status(204).send();
  } catch (err) {
    if (err && err.code === 'CARRIER_IN_USE') {
      return res.status(409).json({ message: err.message });
    }
    return res.status(400).json({ message: err.message || 'Unable to delete carrier.' });
  }
});
// Add prefix to carrier
router.post('/carriers/:carrierId/prefixes', authenticate(['admin', 'superadmin']), async (req, res) => {
  const schema = Joi.object({
    prefix: Joi.string().required(),
    callerId: Joi.string().required()
  });

  const { error, value } = schema.validate(req.body);
  if (error) {
    return res.status(400).json({ message: error.message });
  }
// Add prefix
  const entry = await carrierService.addPrefix({
    carrierId: req.params.carrierId,
    prefix: value.prefix,
    callerId: value.callerId
  });
  return res.status(201).json(entry);
});

module.exports = router;
