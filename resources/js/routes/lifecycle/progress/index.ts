import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/eletut/eredmenyek',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\LifecycleController::store
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
const progress = {
    store: Object.assign(store, store),
};

export default progress;
