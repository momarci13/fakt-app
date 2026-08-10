import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../wayfinder';
import requests from './requests';
import evidence from './evidence';
import progress from './progress';
/**
 * @see \App\Http\Controllers\LifecycleController::index
 * @see app/Http/Controllers/LifecycleController.php:20
 * @route '/eletut'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/eletut',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\LifecycleController::index
 * @see app/Http/Controllers/LifecycleController.php:20
 * @route '/eletut'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\LifecycleController::index
 * @see app/Http/Controllers/LifecycleController.php:20
 * @route '/eletut'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\LifecycleController::index
 * @see app/Http/Controllers/LifecycleController.php:20
 * @route '/eletut'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\LifecycleController::index
 * @see app/Http/Controllers/LifecycleController.php:20
 * @route '/eletut'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\LifecycleController::index
 * @see app/Http/Controllers/LifecycleController.php:20
 * @route '/eletut'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\LifecycleController::index
 * @see app/Http/Controllers/LifecycleController.php:20
 * @route '/eletut'
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
const lifecycle = {
    index: Object.assign(index, index),
    requests: Object.assign(requests, requests),
    evidence: Object.assign(evidence, evidence),
    progress: Object.assign(progress, progress),
};

export default lifecycle;
