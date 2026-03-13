<?php

return [
    'mfa_email_code_ttl_minutes' => (int) env('MFA_EMAIL_CODE_TTL_MINUTES', 10),
    'mfa_email_max_attempts' => (int) env('MFA_EMAIL_MAX_ATTEMPTS', 5),
    'mfa_used_code_retention_days' => (int) env('MFA_USED_CODE_RETENTION_DAYS', 7),
    'audit_retention_days' => (int) env('SECURITY_AUDIT_RETENTION_DAYS', 180),
];
