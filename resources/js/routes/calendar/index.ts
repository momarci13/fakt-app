import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../wayfinder';
import events from './events';
import meeting from './meeting';
import token from './token';
/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
export const feed = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: feed.url(args, options),
    method: 'get',
});

feed.definition = {
    methods: ['get', 'head'],
    url: '/calendar/feed/{token}.ics',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
feed.url = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { token: args };
    }

    if (Array.isArray(args)) {
        args = {
            token: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        token: args.token,
    };

    return (
        feed.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
feed.get = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: feed.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
feed.head = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: feed.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
const feedForm = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: feed.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
feedForm.get = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: feed.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
feedForm.head = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: feed.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

feed.form = feedForm;
/**
 * @see \App\Http\Controllers\CalendarController::index
 * @see app/Http/Controllers/CalendarController.php:21
 * @route '/naptar'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/naptar',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\CalendarController::index
 * @see app/Http/Controllers/CalendarController.php:21
 * @route '/naptar'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CalendarController::index
 * @see app/Http/Controllers/CalendarController.php:21
 * @route '/naptar'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CalendarController::index
 * @see app/Http/Controllers/CalendarController.php:21
 * @route '/naptar'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\CalendarController::index
 * @see app/Http/Controllers/CalendarController.php:21
 * @route '/naptar'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\CalendarController::index
 * @see app/Http/Controllers/CalendarController.php:21
 * @route '/naptar'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CalendarController::index
 * @see app/Http/Controllers/CalendarController.php:21
 * @route '/naptar'
 */
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

index.form = indexForm;
/**
 * @see \App\Http\Controllers\CalendarController::rsvp
 * @see app/Http/Controllers/CalendarController.php:53
 * @route '/naptar/esemenyek/{event}/visszajelzes'
 */
export const rsvp = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: rsvp.url(args, options),
    method: 'put',
});

rsvp.definition = {
    methods: ['put'],
    url: '/naptar/esemenyek/{event}/visszajelzes',
} satisfies RouteDefinition<['put']>;

/**
 * @see \App\Http\Controllers\CalendarController::rsvp
 * @see app/Http/Controllers/CalendarController.php:53
 * @route '/naptar/esemenyek/{event}/visszajelzes'
 */
rsvp.url = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { event: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { event: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            event: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        event: typeof args.event === 'object' ? args.event.id : args.event,
    };

    return (
        rsvp.definition.url
            .replace('{event}', parsedArgs.event.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\CalendarController::rsvp
 * @see app/Http/Controllers/CalendarController.php:53
 * @route '/naptar/esemenyek/{event}/visszajelzes'
 */
rsvp.put = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: rsvp.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\CalendarController::rsvp
 * @see app/Http/Controllers/CalendarController.php:53
 * @route '/naptar/esemenyek/{event}/visszajelzes'
 */
const rsvpForm = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rsvp.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::rsvp
 * @see app/Http/Controllers/CalendarController.php:53
 * @route '/naptar/esemenyek/{event}/visszajelzes'
 */
rsvpForm.put = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rsvp.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

rsvp.form = rsvpForm;
/**
 * @see \App\Http\Controllers\CalendarController::finalize
 * @see app/Http/Controllers/CalendarController.php:62
 * @route '/naptar/esemenyek/{event}/jelenlet'
 */
export const finalize = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: finalize.url(args, options),
    method: 'patch',
});

finalize.definition = {
    methods: ['patch'],
    url: '/naptar/esemenyek/{event}/jelenlet',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\CalendarController::finalize
 * @see app/Http/Controllers/CalendarController.php:62
 * @route '/naptar/esemenyek/{event}/jelenlet'
 */
finalize.url = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { event: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { event: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            event: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        event: typeof args.event === 'object' ? args.event.id : args.event,
    };

    return (
        finalize.definition.url
            .replace('{event}', parsedArgs.event.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\CalendarController::finalize
 * @see app/Http/Controllers/CalendarController.php:62
 * @route '/naptar/esemenyek/{event}/jelenlet'
 */
finalize.patch = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: finalize.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\CalendarController::finalize
 * @see app/Http/Controllers/CalendarController.php:62
 * @route '/naptar/esemenyek/{event}/jelenlet'
 */
const finalizeForm = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: finalize.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::finalize
 * @see app/Http/Controllers/CalendarController.php:62
 * @route '/naptar/esemenyek/{event}/jelenlet'
 */
finalizeForm.patch = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: finalize.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

finalize.form = finalizeForm;
const calendar = {
    feed: Object.assign(feed, feed),
    index: Object.assign(index, index),
    events: Object.assign(events, events),
    rsvp: Object.assign(rsvp, rsvp),
    finalize: Object.assign(finalize, finalize),
    meeting: Object.assign(meeting, meeting),
    token: Object.assign(token, token),
};

export default calendar;
