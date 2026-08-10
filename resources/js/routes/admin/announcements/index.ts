import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/admin/kozlemenyek',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
const announcements = {
    store: Object.assign(store, store),
};

export default announcements;
