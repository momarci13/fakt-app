import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
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
const events = {
    store: Object.assign(store, store),
};

export default events;
