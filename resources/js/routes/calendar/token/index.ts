import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\CalendarController::rotate
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
export const rotate = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: rotate.url(options),
    method: 'post',
});

rotate.definition = {
    methods: ['post'],
    url: '/naptar/token',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\CalendarController::rotate
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
rotate.url = (options?: RouteQueryOptions) => {
    return rotate.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CalendarController::rotate
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
rotate.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rotate.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::rotate
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
const rotateForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rotate.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CalendarController::rotate
 * @see app/Http/Controllers/CalendarController.php:90
 * @route '/naptar/token'
 */
rotateForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rotate.url(options),
    method: 'post',
});

rotate.form = rotateForm;
const token = {
    rotate: Object.assign(rotate, rotate),
};

export default token;
