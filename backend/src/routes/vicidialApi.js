const express = require('express');
const Joi = require('joi');
const { VicidialApiService } = require('../services/vicidialApiService');
const { requireInternalToken } = require('../middleware/auth');

const router = express.Router();
const vicidial = new VicidialApiService();

router.use(requireInternalToken);

const dispositionSchema = Joi.object({
  call_id: Joi.string().pattern(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i).required(),
  disposition: Joi.string().trim().min(1).max(50).required(),
  notes: Joi.string().trim().max(500).allow('').default('')
});

const lookupSchema = Joi.object({
  phone: Joi.string().trim().min(7).max(15).required()
});

router.post('/disposition', async (req, res) => {
  const { error, value } = dispositionSchema.validate(req.body);
  if (error) return res.status(400).json({ ok: false, message: error.message });
  if (!vicidial.isEnabled()) return res.status(503).json({ ok: false, message: 'VICIDIAL API not configured' });
  try {
    await vicidial.setDisposition(value.call_id, value.disposition, value.notes);
    return res.json({ ok: true });
  } catch (err) {
    return res.status(502).json({ ok: false, message: err.message });
  }
});

router.get('/lookup', async (req, res) => {
  const { error, value } = lookupSchema.validate(req.query);
  if (error) return res.status(400).json({ ok: false, message: error.message });
  if (!vicidial.isEnabled()) return res.status(503).json({ ok: false, message: 'VICIDIAL API not configured' });
  try {
    const result = await vicidial.lookupCustomerByPhone(value.phone);
    return res.json({ ok: true, data: result });
  } catch (err) {
    return res.status(502).json({ ok: false, message: err.message });
  }
});

module.exports = router;
