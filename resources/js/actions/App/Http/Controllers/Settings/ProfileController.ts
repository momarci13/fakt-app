import { makeRoute } from '@/lib/route';

export default {
    update: makeRoute('/settings/profile', 'patch'),
    destroy: makeRoute('/settings/profile', 'delete'),
};
