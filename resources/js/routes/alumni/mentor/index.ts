import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\AlumniController::request
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
export const request = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: request.url(options),
    method: 'post',
});

request.definition = {
    methods: ['post'],
    url: '/alumni/mentor',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AlumniController::request
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
request.url = (options?: RouteQueryOptions) => {
    return request.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AlumniController::request
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
request.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: request.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlumniController::request
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
const requestForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: request.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AlumniController::request
 * @see app/Http/Controllers/AlumniController.php:23
 * @route '/alumni/mentor'
 */
requestForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: request.url(options),
    method: 'post',
});

request.form = requestForm;
const mentor = {
    request: Object.assign(request, request),
};

export default mentor;
