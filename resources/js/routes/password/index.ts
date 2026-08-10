import { makeRoute } from '@/lib/route';

export const request = makeRoute('/forgot-password');
export const email = makeRoute('/forgot-password', 'post');
export const update = makeRoute('/reset-password', 'post');
