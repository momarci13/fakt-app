import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../wayfinder';
import imports from './imports';
import semesters from './semesters';
import rules from './rules';
import announcements from './announcements';
import requests from './requests';
/**
 * @see \App\Http\Controllers\AdminController::index
 * @see app/Http/Controllers/AdminController.php:28
 * @route '/admin'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/admin',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\AdminController::index
 * @see app/Http/Controllers/AdminController.php:28
 * @route '/admin'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::index
 * @see app/Http/Controllers/AdminController.php:28
 * @route '/admin'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AdminController::index
 * @see app/Http/Controllers/AdminController.php:28
 * @route '/admin'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\AdminController::index
 * @see app/Http/Controllers/AdminController.php:28
 * @route '/admin'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\AdminController::index
 * @see app/Http/Controllers/AdminController.php:28
 * @route '/admin'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\AdminController::index
 * @see app/Http/Controllers/AdminController.php:28
 * @route '/admin'
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
 * @see \App\Http\Controllers\AdminController::invite
 * @see app/Http/Controllers/AdminController.php:44
 * @route '/admin/meghivok'
 */
export const invite = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: invite.url(options),
    method: 'post',
});

invite.definition = {
    methods: ['post'],
    url: '/admin/meghivok',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::invite
 * @see app/Http/Controllers/AdminController.php:44
 * @route '/admin/meghivok'
 */
invite.url = (options?: RouteQueryOptions) => {
    return invite.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::invite
 * @see app/Http/Controllers/AdminController.php:44
 * @route '/admin/meghivok'
 */
invite.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: invite.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::invite
 * @see app/Http/Controllers/AdminController.php:44
 * @route '/admin/meghivok'
 */
const inviteForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: invite.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::invite
 * @see app/Http/Controllers/AdminController.php:44
 * @route '/admin/meghivok'
 */
inviteForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: invite.url(options),
    method: 'post',
});

invite.form = inviteForm;
const admin = {
    index: Object.assign(index, index),
    invite: Object.assign(invite, invite),
    imports: Object.assign(imports, imports),
    semesters: Object.assign(semesters, semesters),
    rules: Object.assign(rules, rules),
    announcements: Object.assign(announcements, announcements),
    requests: Object.assign(requests, requests),
};

export default admin;
