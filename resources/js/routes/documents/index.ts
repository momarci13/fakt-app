import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../wayfinder';
/**
 * @see \App\Http\Controllers\DocumentController::index
 * @see app/Http/Controllers/DocumentController.php:18
 * @route '/dokumentumok'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/dokumentumok',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\DocumentController::index
 * @see app/Http/Controllers/DocumentController.php:18
 * @route '/dokumentumok'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DocumentController::index
 * @see app/Http/Controllers/DocumentController.php:18
 * @route '/dokumentumok'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DocumentController::index
 * @see app/Http/Controllers/DocumentController.php:18
 * @route '/dokumentumok'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\DocumentController::index
 * @see app/Http/Controllers/DocumentController.php:18
 * @route '/dokumentumok'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\DocumentController::index
 * @see app/Http/Controllers/DocumentController.php:18
 * @route '/dokumentumok'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DocumentController::index
 * @see app/Http/Controllers/DocumentController.php:18
 * @route '/dokumentumok'
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
 * @see \App\Http\Controllers\DocumentController::store
 * @see app/Http/Controllers/DocumentController.php:36
 * @route '/dokumentumok'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/dokumentumok',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\DocumentController::store
 * @see app/Http/Controllers/DocumentController.php:36
 * @route '/dokumentumok'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\DocumentController::store
 * @see app/Http/Controllers/DocumentController.php:36
 * @route '/dokumentumok'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DocumentController::store
 * @see app/Http/Controllers/DocumentController.php:36
 * @route '/dokumentumok'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\DocumentController::store
 * @see app/Http/Controllers/DocumentController.php:36
 * @route '/dokumentumok'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\DocumentController::download
 * @see app/Http/Controllers/DocumentController.php:73
 * @route '/dokumentumok/{document}/letoltes'
 */
export const download = (
    args:
        | { document: number | { id: number } }
        | [document: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});

download.definition = {
    methods: ['get', 'head'],
    url: '/dokumentumok/{document}/letoltes',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\DocumentController::download
 * @see app/Http/Controllers/DocumentController.php:73
 * @route '/dokumentumok/{document}/letoltes'
 */
download.url = (
    args:
        | { document: number | { id: number } }
        | [document: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { document: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { document: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            document: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        document:
            typeof args.document === 'object'
                ? args.document.id
                : args.document,
    };

    return (
        download.definition.url
            .replace('{document}', parsedArgs.document.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\DocumentController::download
 * @see app/Http/Controllers/DocumentController.php:73
 * @route '/dokumentumok/{document}/letoltes'
 */
download.get = (
    args:
        | { document: number | { id: number } }
        | [document: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DocumentController::download
 * @see app/Http/Controllers/DocumentController.php:73
 * @route '/dokumentumok/{document}/letoltes'
 */
download.head = (
    args:
        | { document: number | { id: number } }
        | [document: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\DocumentController::download
 * @see app/Http/Controllers/DocumentController.php:73
 * @route '/dokumentumok/{document}/letoltes'
 */
const downloadForm = (
    args:
        | { document: number | { id: number } }
        | [document: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\DocumentController::download
 * @see app/Http/Controllers/DocumentController.php:73
 * @route '/dokumentumok/{document}/letoltes'
 */
downloadForm.get = (
    args:
        | { document: number | { id: number } }
        | [document: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: download.url(args, options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\DocumentController::download
 * @see app/Http/Controllers/DocumentController.php:73
 * @route '/dokumentumok/{document}/letoltes'
 */
downloadForm.head = (
    args:
        | { document: number | { id: number } }
        | [document: number | { id: number }]
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
const documents = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    download: Object.assign(download, download),
};

export default documents;
