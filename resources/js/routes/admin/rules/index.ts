import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/admin/szabalyok',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::store
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\AdminController::publish
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
export const publish = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: publish.url(options),
    method: 'post',
});

publish.definition = {
    methods: ['post'],
    url: '/admin/szabalyok/publikalas',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::publish
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
publish.url = (options?: RouteQueryOptions) => {
    return publish.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::publish
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
publish.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: publish.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::publish
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
const publishForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: publish.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::publish
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
publishForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: publish.url(options),
    method: 'post',
});

publish.form = publishForm;
const rules = {
    store: Object.assign(store, store),
    publish: Object.assign(publish, publish),
};

export default rules;
