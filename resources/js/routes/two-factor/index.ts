import { makeRoute } from '@/lib/route';

export const enable = makeRoute('/user/two-factor-authentication', 'post');
export const disable = makeRoute('/user/two-factor-authentication', 'delete');
export const confirm = makeRoute(
    '/user/confirmed-two-factor-authentication',
    'post',
);
export const qrCode = makeRoute('/user/two-factor-qr-code');
export const secretKey = makeRoute('/user/two-factor-secret-key');
export const recoveryCodes = makeRoute('/user/two-factor-recovery-codes');
export const regenerateRecoveryCodes = makeRoute(
    '/user/two-factor-recovery-codes',
    'post',
);
