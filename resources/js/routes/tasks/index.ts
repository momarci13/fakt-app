import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../wayfinder';
import comments from './comments';
/**
 * @see \App\Http\Controllers\TaskController::index
 * @see app/Http/Controllers/TaskController.php:20
 * @route '/feladatok'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/feladatok',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\TaskController::index
 * @see app/Http/Controllers/TaskController.php:20
 * @route '/feladatok'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\TaskController::index
 * @see app/Http/Controllers/TaskController.php:20
 * @route '/feladatok'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\TaskController::index
 * @see app/Http/Controllers/TaskController.php:20
 * @route '/feladatok'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\TaskController::index
 * @see app/Http/Controllers/TaskController.php:20
 * @route '/feladatok'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\TaskController::index
 * @see app/Http/Controllers/TaskController.php:20
 * @route '/feladatok'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\TaskController::index
 * @see app/Http/Controllers/TaskController.php:20
 * @route '/feladatok'
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
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:34
 * @route '/feladatok'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/feladatok',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:34
 * @route '/feladatok'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:34
 * @route '/feladatok'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:34
 * @route '/feladatok'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:34
 * @route '/feladatok'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\TaskController::update
 * @see app/Http/Controllers/TaskController.php:61
 * @route '/feladatok/{task}'
 */
export const update = (
    args:
        | { task: number | { id: number } }
        | [task: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

update.definition = {
    methods: ['patch'],
    url: '/feladatok/{task}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\TaskController::update
 * @see app/Http/Controllers/TaskController.php:61
 * @route '/feladatok/{task}'
 */
update.url = (
    args:
        | { task: number | { id: number } }
        | [task: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { task: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { task: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            task: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        task: typeof args.task === 'object' ? args.task.id : args.task,
    };

    return (
        update.definition.url
            .replace('{task}', parsedArgs.task.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\TaskController::update
 * @see app/Http/Controllers/TaskController.php:61
 * @route '/feladatok/{task}'
 */
update.patch = (
    args:
        | { task: number | { id: number } }
        | [task: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\TaskController::update
 * @see app/Http/Controllers/TaskController.php:61
 * @route '/feladatok/{task}'
 */
const updateForm = (
    args:
        | { task: number | { id: number } }
        | [task: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\TaskController::update
 * @see app/Http/Controllers/TaskController.php:61
 * @route '/feladatok/{task}'
 */
updateForm.patch = (
    args:
        | { task: number | { id: number } }
        | [task: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

update.form = updateForm;
const tasks = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    comments: Object.assign(comments, comments),
};

export default tasks;
