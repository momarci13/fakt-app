import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/eletut/kerelmek',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
const requests = {
    store: Object.assign(store, store),
};

export default requests;
