import { makeRoute } from '@/lib/route';

export const home = makeRoute('/');
export const dashboard = makeRoute('/dashboard');
export const logout = makeRoute('/logout', 'post');
export const login = makeRoute('/login');
