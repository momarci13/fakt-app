import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../wayfinder';
import mentor from './mentor';
/**
 * @see \App\Http\Controllers\AlumniController::index
 * @see app/Http/Controllers/AlumniController.php:15
 * @route '/alumni'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/alumni',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AlumniController::index
 * @see app/Http/Controllers/AlumniController.php:15
 * @route '/alumni'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlumniController::index
 * @see app/Http/Controllers/AlumniController.php:15
 * @route '/alumni'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlumniController::index
 * @see app/Http/Controllers/AlumniController.php:15
 * @route '/alumni'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AlumniController::index
 * @see app/Http/Controllers/AlumniController.php:15
 * @route '/alumni'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AlumniController::index
 * @see app/Http/Controllers/AlumniController.php:15
 * @route '/alumni'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AlumniController::index
 * @see app/Http/Controllers/AlumniController.php:15
 * @route '/alumni'
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
const alumni = {
    index: Object.assign(index, index),
    mentor: Object.assign(mentor, mentor),
};

export default alumni;
