import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../../wayfinder';
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
/**
 * @see \App\Http\Controllers\AlumniController::requestMentor
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
export const requestMentor = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: requestMentor.url(options),
    method: 'post',
});

requestMentor.definition = {
    methods: ['post'],
    url: '/alumni/mentor',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlumniController::requestMentor
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
requestMentor.url = (options?: RouteQueryOptions) => {
    return requestMentor.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlumniController::requestMentor
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
requestMentor.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: requestMentor.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlumniController::requestMentor
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
const requestMentorForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: requestMentor.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlumniController::requestMentor
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
requestMentorForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: requestMentor.url(options),
    method: 'post',
});

requestMentor.form = requestMentorForm;
const AlumniController = { index, requestMentor };

export default AlumniController;
