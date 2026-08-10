import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../../wayfinder';
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
/**
 * @see \App\Http\Controllers\LifecycleController::storeRequest
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
export const storeRequest = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeRequest.url(options),
    method: 'post',
});

storeRequest.definition = {
    methods: ['post'],
    url: '/eletut/kerelmek',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\LifecycleController::storeRequest
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
storeRequest.url = (options?: RouteQueryOptions) => {
    return storeRequest.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\LifecycleController::storeRequest
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
storeRequest.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeRequest.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\LifecycleController::storeRequest
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
const storeRequestForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeRequest.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\LifecycleController::storeRequest
 * @see app/Http/Controllers/LifecycleController.php:32
 * @route '/eletut/kerelmek'
 */
storeRequestForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeRequest.url(options),
    method: 'post',
});

storeRequest.form = storeRequestForm;
/**
 * @see \App\Http\Controllers\LifecycleController::downloadEvidence
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
export const downloadEvidence = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadEvidence.url(args, options),
    method: 'get',
});

downloadEvidence.definition = {
    methods: ['get', 'head'],
    url: '/eletut/kerelmek/{memberRequest}/bizonyitek',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\LifecycleController::downloadEvidence
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
downloadEvidence.url = (
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
        downloadEvidence.definition.url
            .replace('{memberRequest}', parsedArgs.memberRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\LifecycleController::downloadEvidence
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
downloadEvidence.get = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: downloadEvidence.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\LifecycleController::downloadEvidence
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
downloadEvidence.head = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: downloadEvidence.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\LifecycleController::downloadEvidence
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
const downloadEvidenceForm = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadEvidence.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\LifecycleController::downloadEvidence
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
downloadEvidenceForm.get = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadEvidence.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\LifecycleController::downloadEvidence
 * @see app/Http/Controllers/LifecycleController.php:58
 * @route '/eletut/kerelmek/{memberRequest}/bizonyitek'
 */
downloadEvidenceForm.head = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: downloadEvidence.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

downloadEvidence.form = downloadEvidenceForm;
/**
 * @see \App\Http\Controllers\LifecycleController::addProgress
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
export const addProgress = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: addProgress.url(options),
    method: 'post',
});

addProgress.definition = {
    methods: ['post'],
    url: '/eletut/eredmenyek',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\LifecycleController::addProgress
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
addProgress.url = (options?: RouteQueryOptions) => {
    return addProgress.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\LifecycleController::addProgress
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
addProgress.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addProgress.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\LifecycleController::addProgress
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
const addProgressForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: addProgress.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\LifecycleController::addProgress
 * @see app/Http/Controllers/LifecycleController.php:47
 * @route '/eletut/eredmenyek'
 */
addProgressForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: addProgress.url(options),
    method: 'post',
});

addProgress.form = addProgressForm;
const LifecycleController = {
    index,
    storeRequest,
    downloadEvidence,
    addProgress,
};

export default LifecycleController;
