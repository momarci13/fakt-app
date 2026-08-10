import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../wayfinder';
import members from './members';
import projects from './projects';
/**
 * @see \App\Http\Controllers\OrganizationController::index
 * @see app/Http/Controllers/OrganizationController.php:21
 * @route '/szervezet'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/szervezet',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\OrganizationController::index
 * @see app/Http/Controllers/OrganizationController.php:21
 * @route '/szervezet'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\OrganizationController::index
 * @see app/Http/Controllers/OrganizationController.php:21
 * @route '/szervezet'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\OrganizationController::index
 * @see app/Http/Controllers/OrganizationController.php:21
 * @route '/szervezet'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\OrganizationController::index
 * @see app/Http/Controllers/OrganizationController.php:21
 * @route '/szervezet'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\OrganizationController::index
 * @see app/Http/Controllers/OrganizationController.php:21
 * @route '/szervezet'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\OrganizationController::index
 * @see app/Http/Controllers/OrganizationController.php:21
 * @route '/szervezet'
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
 * @see \App\Http\Controllers\OrganizationController::appoint
 * @see app/Http/Controllers/OrganizationController.php:38
 * @route '/szervezet/kinevezes'
 */
export const appoint = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: appoint.url(options),
    method: 'post',
});

appoint.definition = {
    methods: ['post'],
    url: '/szervezet/kinevezes',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\OrganizationController::appoint
 * @see app/Http/Controllers/OrganizationController.php:38
 * @route '/szervezet/kinevezes'
 */
appoint.url = (options?: RouteQueryOptions) => {
    return appoint.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\OrganizationController::appoint
 * @see app/Http/Controllers/OrganizationController.php:38
 * @route '/szervezet/kinevezes'
 */
appoint.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: appoint.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\OrganizationController::appoint
 * @see app/Http/Controllers/OrganizationController.php:38
 * @route '/szervezet/kinevezes'
 */
const appointForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: appoint.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\OrganizationController::appoint
 * @see app/Http/Controllers/OrganizationController.php:38
 * @route '/szervezet/kinevezes'
 */
appointForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: appoint.url(options),
    method: 'post',
});

appoint.form = appointForm;
/**
 * @see \App\Http\Controllers\OrganizationController::revoke
 * @see app/Http/Controllers/OrganizationController.php:68
 * @route '/szervezet/kinevezes/{roleAssignment}/visszavonas'
 */
export const revoke = (
    args:
        | { roleAssignment: number | { id: number } }
        | [roleAssignment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: revoke.url(args, options),
    method: 'patch',
});

revoke.definition = {
    methods: ['patch'],
    url: '/szervezet/kinevezes/{roleAssignment}/visszavonas',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\OrganizationController::revoke
 * @see app/Http/Controllers/OrganizationController.php:68
 * @route '/szervezet/kinevezes/{roleAssignment}/visszavonas'
 */
revoke.url = (
    args:
        | { roleAssignment: number | { id: number } }
        | [roleAssignment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { roleAssignment: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { roleAssignment: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            roleAssignment: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        roleAssignment:
            typeof args.roleAssignment === 'object'
                ? args.roleAssignment.id
                : args.roleAssignment,
    };

    return (
        revoke.definition.url
            .replace('{roleAssignment}', parsedArgs.roleAssignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\OrganizationController::revoke
 * @see app/Http/Controllers/OrganizationController.php:68
 * @route '/szervezet/kinevezes/{roleAssignment}/visszavonas'
 */
revoke.patch = (
    args:
        | { roleAssignment: number | { id: number } }
        | [roleAssignment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: revoke.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\OrganizationController::revoke
 * @see app/Http/Controllers/OrganizationController.php:68
 * @route '/szervezet/kinevezes/{roleAssignment}/visszavonas'
 */
const revokeForm = (
    args:
        | { roleAssignment: number | { id: number } }
        | [roleAssignment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: revoke.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\OrganizationController::revoke
 * @see app/Http/Controllers/OrganizationController.php:68
 * @route '/szervezet/kinevezes/{roleAssignment}/visszavonas'
 */
revokeForm.patch = (
    args:
        | { roleAssignment: number | { id: number } }
        | [roleAssignment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: revoke.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

revoke.form = revokeForm;
const organization = {
    index: Object.assign(index, index),
    appoint: Object.assign(appoint, appoint),
    revoke: Object.assign(revoke, revoke),
    members: Object.assign(members, members),
    projects: Object.assign(projects, projects),
};

export default organization;
