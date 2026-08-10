import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\CalendarController::update
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
export const update = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

update.definition = {
    methods: ['patch'],
    url: '/naptar/esemenyek/{event}/jegyzokonyv',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\CalendarController::update
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
update.url = (
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
        update.definition.url
            .replace('{event}', parsedArgs.event.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\CalendarController::update
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
update.patch = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\CalendarController::update
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
const updateForm = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::update
 * @see app/Http/Controllers/CalendarController.php:72
 * @route '/naptar/esemenyek/{event}/jegyzokonyv'
 */
updateForm.patch = (
    args:
        | { event: number | { id: number } }
        | [event: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update.form = updateForm;
const meeting = {
    update: Object.assign(update, update),
};

export default meeting;
