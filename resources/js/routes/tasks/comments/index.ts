import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:74
 * @route '/feladatok/{task}/hozzaszolas'
 */
export const store = (
    args:
        | { task: number | { id: number } }
        | [task: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/feladatok/{task}/hozzaszolas',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:74
 * @route '/feladatok/{task}/hozzaszolas'
 */
store.url = (
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
        store.definition.url
            .replace('{task}', parsedArgs.task.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:74
 * @route '/feladatok/{task}/hozzaszolas'
 */
store.post = (
    args:
        | { task: number | { id: number } }
        | [task: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:74
 * @route '/feladatok/{task}/hozzaszolas'
 */
const storeForm = (
    args:
        | { task: number | { id: number } }
        | [task: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\TaskController::store
 * @see app/Http/Controllers/TaskController.php:74
 * @route '/feladatok/{task}/hozzaszolas'
 */
storeForm.post = (
    args:
        | { task: number | { id: number } }
        | [task: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
});

store.form = storeForm;
const comments = {
    store: Object.assign(store, store),
};

export default comments;
