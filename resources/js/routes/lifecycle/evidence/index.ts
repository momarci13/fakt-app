import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\LifecycleController::download
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
export const download = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});

download.definition = {
    methods: ['get', 'head'],
    url: '/eletut/kerelmek/{memberRequest}/bizonyitek',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\LifecycleController::download
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
download.url = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { memberRequest: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { memberRequest: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            memberRequest: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        memberRequest:
            typeof args.memberRequest === 'object'
                ? args.memberRequest.id
                : args.memberRequest,
    };

    return (
        download.definition.url
            .replace('{memberRequest}', parsedArgs.memberRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\LifecycleController::download
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
download.get = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\LifecycleController::download
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
download.head = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\LifecycleController::download
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
const downloadForm = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\LifecycleController::download
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
downloadForm.get = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\LifecycleController::download
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
downloadForm.head = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

download.form = downloadForm;
const evidence = {
    download: Object.assign(download, download),
};

export default evidence;
