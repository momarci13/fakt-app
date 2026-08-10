import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\AdminController::stage
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
export const stage = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: stage.url(options),
    method: 'post',
});

stage.definition = {
    methods: ['post'],
    url: '/admin/importok',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::stage
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
stage.url = (options?: RouteQueryOptions) => {
    return stage.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::stage
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
stage.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stage.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::stage
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
const stageForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: stage.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::stage
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
stageForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: stage.url(options),
    method: 'post',
});

stage.form = stageForm;
/**
 * @see \App\Http\Controllers\AdminController::apply
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
export const apply = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: apply.url(args, options),
    method: 'post',
});

apply.definition = {
    methods: ['post'],
    url: '/admin/importok/{importBatch}/alkalmazas',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::apply
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
apply.url = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importBatch: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importBatch: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            importBatch: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        importBatch:
            typeof args.importBatch === 'object'
                ? args.importBatch.id
                : args.importBatch,
    };

    return (
        apply.definition.url
            .replace('{importBatch}', parsedArgs.importBatch.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AdminController::apply
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
apply.post = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: apply.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::apply
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
const applyForm = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: apply.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::apply
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
applyForm.post = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: apply.url(args, options),
    method: 'post',
});

apply.form = applyForm;
/**
 * @see \App\Http\Controllers\AdminController::rollback
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
export const rollback = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: rollback.url(args, options),
    method: 'post',
});

rollback.definition = {
    methods: ['post'],
    url: '/admin/importok/{importBatch}/visszavonas',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::rollback
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
rollback.url = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { importBatch: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { importBatch: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            importBatch: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        importBatch:
            typeof args.importBatch === 'object'
                ? args.importBatch.id
                : args.importBatch,
    };

    return (
        rollback.definition.url
            .replace('{importBatch}', parsedArgs.importBatch.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AdminController::rollback
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
rollback.post = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: rollback.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::rollback
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
const rollbackForm = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rollback.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::rollback
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
rollbackForm.post = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rollback.url(args, options),
    method: 'post',
});

rollback.form = rollbackForm;
const imports = {
    stage: Object.assign(stage, stage),
    apply: Object.assign(apply, apply),
    rollback: Object.assign(rollback, rollback),
};

export default imports;
