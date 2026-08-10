import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
const CalendarFeedController = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: CalendarFeedController.url(args, options),
    method: 'get',
});

CalendarFeedController.definition = {
    methods: ['get', 'head'],
    url: '/calendar/feed/{token}.ics',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
CalendarFeedController.url = (
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
        CalendarFeedController.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
CalendarFeedController.get = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: CalendarFeedController.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
CalendarFeedController.head = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: CalendarFeedController.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
const CalendarFeedControllerForm = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: CalendarFeedController.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
CalendarFeedControllerForm.get = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: CalendarFeedController.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CalendarFeedController::__invoke
 * @see app/Http/Controllers/CalendarFeedController.php:12
 * @route '/calendar/feed/{token}.ics'
 */
CalendarFeedControllerForm.head = (
    args:
        { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: CalendarFeedController.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

CalendarFeedController.form = CalendarFeedControllerForm;
export default CalendarFeedController;
