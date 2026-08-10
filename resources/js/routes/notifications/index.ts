import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\NotificationController::read
 * @see app/Http/Controllers/NotificationController.php:10
 * @route '/ertesitesek/{notification}/olvasott'
 */
export const read = (
    args:
        | { notification: string | number }
        | [notification: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: read.url(args, options),
    method: 'post',
});

read.definition = {
    methods: ['post'],
    url: '/ertesitesek/{notification}/olvasott',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\NotificationController::read
 * @see app/Http/Controllers/NotificationController.php:10
 * @route '/ertesitesek/{notification}/olvasott'
 */
read.url = (
    args:
        | { notification: string | number }
        | [notification: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notification: args };
    }

    if (Array.isArray(args)) {
        args = {
            notification: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        notification: args.notification,
    };

    return (
        read.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\NotificationController::read
 * @see app/Http/Controllers/NotificationController.php:10
 * @route '/ertesitesek/{notification}/olvasott'
 */
read.post = (
    args:
        | { notification: string | number }
        | [notification: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: read.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\NotificationController::read
 * @see app/Http/Controllers/NotificationController.php:10
 * @route '/ertesitesek/{notification}/olvasott'
 */
const readForm = (
    args:
        | { notification: string | number }
        | [notification: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: read.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\NotificationController::read
 * @see app/Http/Controllers/NotificationController.php:10
 * @route '/ertesitesek/{notification}/olvasott'
 */
readForm.post = (
    args:
        | { notification: string | number }
        | [notification: string | number]
        | string
        | number,
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: read.url(args, options),
    method: 'post',
});

read.form = readForm;
const notifications = {
    read: Object.assign(read, read),
};

export default notifications;
