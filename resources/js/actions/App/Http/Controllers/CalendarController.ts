import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../../wayfinder';
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
 * @see \App\Http\Controllers\CalendarController::store
 * @see app/Http/Controllers/CalendarController.php:33
 * @route '/naptar/esemenyek'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/naptar/esemenyek',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\CalendarController::store
 * @see app/Http/Controllers/CalendarController.php:33
 * @route '/naptar/esemenyek'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CalendarController::store
 * @see app/Http/Controllers/CalendarController.php:33
 * @route '/naptar/esemenyek'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::store
 * @see app/Http/Controllers/CalendarController.php:33
 * @route '/naptar/esemenyek'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::store
 * @see app/Http/Controllers/CalendarController.php:33
 * @route '/naptar/esemenyek'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
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
/**
 * @see \App\Http\Controllers\CalendarController::updateMeeting
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
export const updateMeeting = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updateMeeting.url(args, options),
    method: 'patch',
});

updateMeeting.definition = {
    methods: ['patch'],
    url: '/naptar/esemenyek/{event}/jegyzokonyv',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\CalendarController::updateMeeting
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
updateMeeting.url = (
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
        updateMeeting.definition.url
            .replace('{event}', parsedArgs.event.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\CalendarController::updateMeeting
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
updateMeeting.patch = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: updateMeeting.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\CalendarController::updateMeeting
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
const updateMeetingForm = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateMeeting.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::updateMeeting
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
updateMeetingForm.patch = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: updateMeeting.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

updateMeeting.form = updateMeetingForm;
/**
 * @see \App\Http\Controllers\CalendarController::rotateToken
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
export const rotateToken = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: rotateToken.url(options),
    method: 'post',
});

rotateToken.definition = {
    methods: ['post'],
    url: '/naptar/token',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\CalendarController::rotateToken
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
rotateToken.url = (options?: RouteQueryOptions) => {
    return rotateToken.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CalendarController::rotateToken
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
rotateToken.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rotateToken.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::rotateToken
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
const rotateTokenForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rotateToken.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::rotateToken
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
rotateTokenForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rotateToken.url(options),
    method: 'post',
});

rotateToken.form = rotateTokenForm;
const CalendarController = {
    index,
    store,
    rsvp,
    finalize,
    updateMeeting,
    rotateToken,
};

export default CalendarController;
