import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../../wayfinder';
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
/**
 * @see \App\Http\Controllers\AdminController::stageMemberImport
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
export const stageMemberImport = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: stageMemberImport.url(options),
    method: 'post',
});

stageMemberImport.definition = {
    methods: ['post'],
    url: '/admin/importok',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::stageMemberImport
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
stageMemberImport.url = (options?: RouteQueryOptions) => {
    return stageMemberImport.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::stageMemberImport
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
stageMemberImport.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: stageMemberImport.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::stageMemberImport
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
const stageMemberImportForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: stageMemberImport.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::stageMemberImport
 * @see app/Http/Controllers/AdminController.php:56
 * @route '/admin/importok'
 */
stageMemberImportForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: stageMemberImport.url(options),
    method: 'post',
});

stageMemberImport.form = stageMemberImportForm;
/**
 * @see \App\Http\Controllers\AdminController::applyMemberImport
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
export const applyMemberImport = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: applyMemberImport.url(args, options),
    method: 'post',
});

applyMemberImport.definition = {
    methods: ['post'],
    url: '/admin/importok/{importBatch}/alkalmazas',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::applyMemberImport
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
applyMemberImport.url = (
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
        applyMemberImport.definition.url
            .replace('{importBatch}', parsedArgs.importBatch.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AdminController::applyMemberImport
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
applyMemberImport.post = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: applyMemberImport.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::applyMemberImport
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
const applyMemberImportForm = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: applyMemberImport.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::applyMemberImport
 * @see app/Http/Controllers/AdminController.php:135
 * @route '/admin/importok/{importBatch}/alkalmazas'
 */
applyMemberImportForm.post = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: applyMemberImport.url(args, options),
    method: 'post',
});

applyMemberImport.form = applyMemberImportForm;
/**
 * @see \App\Http\Controllers\AdminController::rollbackMemberImport
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
export const rollbackMemberImport = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: rollbackMemberImport.url(args, options),
    method: 'post',
});

rollbackMemberImport.definition = {
    methods: ['post'],
    url: '/admin/importok/{importBatch}/visszavonas',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::rollbackMemberImport
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
rollbackMemberImport.url = (
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
        rollbackMemberImport.definition.url
            .replace('{importBatch}', parsedArgs.importBatch.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AdminController::rollbackMemberImport
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
rollbackMemberImport.post = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: rollbackMemberImport.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::rollbackMemberImport
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
const rollbackMemberImportForm = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rollbackMemberImport.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::rollbackMemberImport
 * @see app/Http/Controllers/AdminController.php:166
 * @route '/admin/importok/{importBatch}/visszavonas'
 */
rollbackMemberImportForm.post = (
    args:
        | { importBatch: number | { id: number } }
        | [importBatch: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: rollbackMemberImport.url(args, options),
    method: 'post',
});

rollbackMemberImport.form = rollbackMemberImportForm;
/**
 * @see \App\Http\Controllers\AdminController::storeSemester
 * @see app/Http/Controllers/AdminController.php:252
 * @route '/admin/felevek'
 */
export const storeSemester = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeSemester.url(options),
    method: 'post',
});

storeSemester.definition = {
    methods: ['post'],
    url: '/admin/felevek',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::storeSemester
 * @see app/Http/Controllers/AdminController.php:252
 * @route '/admin/felevek'
 */
storeSemester.url = (options?: RouteQueryOptions) => {
    return storeSemester.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::storeSemester
 * @see app/Http/Controllers/AdminController.php:252
 * @route '/admin/felevek'
 */
storeSemester.post = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeSemester.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::storeSemester
 * @see app/Http/Controllers/AdminController.php:252
 * @route '/admin/felevek'
 */
const storeSemesterForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeSemester.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::storeSemester
 * @see app/Http/Controllers/AdminController.php:252
 * @route '/admin/felevek'
 */
storeSemesterForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeSemester.url(options),
    method: 'post',
});

storeSemester.form = storeSemesterForm;
/**
 * @see \App\Http\Controllers\AdminController::storeRule
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
export const storeRule = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: storeRule.url(options),
    method: 'post',
});

storeRule.definition = {
    methods: ['post'],
    url: '/admin/szabalyok',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::storeRule
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
storeRule.url = (options?: RouteQueryOptions) => {
    return storeRule.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::storeRule
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
storeRule.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeRule.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::storeRule
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
const storeRuleForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeRule.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::storeRule
 * @see app/Http/Controllers/AdminController.php:265
 * @route '/admin/szabalyok'
 */
storeRuleForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: storeRule.url(options),
    method: 'post',
});

storeRule.form = storeRuleForm;
/**
 * @see \App\Http\Controllers\AdminController::publishRules
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
export const publishRules = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: publishRules.url(options),
    method: 'post',
});

publishRules.definition = {
    methods: ['post'],
    url: '/admin/szabalyok/publikalas',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::publishRules
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
publishRules.url = (options?: RouteQueryOptions) => {
    return publishRules.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::publishRules
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
publishRules.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: publishRules.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::publishRules
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
const publishRulesForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: publishRules.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::publishRules
 * @see app/Http/Controllers/AdminController.php:278
 * @route '/admin/szabalyok/publikalas'
 */
publishRulesForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: publishRules.url(options),
    method: 'post',
});

publishRules.form = publishRulesForm;
/**
 * @see \App\Http\Controllers\AdminController::announce
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
export const announce = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: announce.url(options),
    method: 'post',
});

announce.definition = {
    methods: ['post'],
    url: '/admin/kozlemenyek',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\AdminController::announce
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
announce.url = (options?: RouteQueryOptions) => {
    return announce.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\AdminController::announce
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
announce.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: announce.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::announce
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
const announceForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: announce.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::announce
 * @see app/Http/Controllers/AdminController.php:289
 * @route '/admin/kozlemenyek'
 */
announceForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: announce.url(options),
    method: 'post',
});

announce.form = announceForm;
/**
 * @see \App\Http\Controllers\AdminController::reviewMemberRequest
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
export const reviewMemberRequest = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: reviewMemberRequest.url(args, options),
    method: 'patch',
});

reviewMemberRequest.definition = {
    methods: ['patch'],
    url: '/admin/kerelmek/{memberRequest}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\AdminController::reviewMemberRequest
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
reviewMemberRequest.url = (
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
        reviewMemberRequest.definition.url
            .replace('{memberRequest}', parsedArgs.memberRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AdminController::reviewMemberRequest
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
reviewMemberRequest.patch = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: reviewMemberRequest.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\AdminController::reviewMemberRequest
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
const reviewMemberRequestForm = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reviewMemberRequest.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\AdminController::reviewMemberRequest
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
reviewMemberRequestForm.patch = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: reviewMemberRequest.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

reviewMemberRequest.form = reviewMemberRequestForm;
const AdminController = {
    index,
    invite,
    stageMemberImport,
    applyMemberImport,
    rollbackMemberImport,
    storeSemester,
    storeRule,
    publishRules,
    announce,
    reviewMemberRequest,
};

export default AdminController;
