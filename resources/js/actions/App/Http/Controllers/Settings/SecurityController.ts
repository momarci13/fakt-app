import { makeRoute } from '@/lib/route';

export default {
    update: makeRoute('/settings/password', 'put'),
};
