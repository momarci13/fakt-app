import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\CourseController::publicIndex
 * @see app/Http/Controllers/CourseController.php:20
 * @route '/kurzuskinalat'
 */
export const publicIndex = (
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: publicIndex.url(options),
    method: 'get',
});

publicIndex.definition = {
    methods: ['get', 'head'],
    url: '/kurzuskinalat',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\CourseController::publicIndex
 * @see app/Http/Controllers/CourseController.php:20
 * @route '/kurzuskinalat'
 */
publicIndex.url = (options?: RouteQueryOptions) => {
    return publicIndex.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CourseController::publicIndex
 * @see app/Http/Controllers/CourseController.php:20
 * @route '/kurzuskinalat'
 */
publicIndex.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicIndex.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CourseController::publicIndex
 * @see app/Http/Controllers/CourseController.php:20
 * @route '/kurzuskinalat'
 */
publicIndex.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicIndex.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\CourseController::publicIndex
 * @see app/Http/Controllers/CourseController.php:20
 * @route '/kurzuskinalat'
 */
const publicIndexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: publicIndex.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\CourseController::publicIndex
 * @see app/Http/Controllers/CourseController.php:20
 * @route '/kurzuskinalat'
 */
publicIndexForm.get = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: publicIndex.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CourseController::publicIndex
 * @see app/Http/Controllers/CourseController.php:20
 * @route '/kurzuskinalat'
 */
publicIndexForm.head = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: publicIndex.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'get',
});

publicIndex.form = publicIndexForm;
/**
 * @see \App\Http\Controllers\CourseController::index
 * @see app/Http/Controllers/CourseController.php:35
 * @route '/kurzusok'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/kurzusok',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\CourseController::index
 * @see app/Http/Controllers/CourseController.php:35
 * @route '/kurzusok'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CourseController::index
 * @see app/Http/Controllers/CourseController.php:35
 * @route '/kurzusok'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CourseController::index
 * @see app/Http/Controllers/CourseController.php:35
 * @route '/kurzusok'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\CourseController::index
 * @see app/Http/Controllers/CourseController.php:35
 * @route '/kurzusok'
 */
const indexForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\CourseController::index
 * @see app/Http/Controllers/CourseController.php:35
 * @route '/kurzusok'
 */
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
});
/**
 * @see \App\Http\Controllers\CourseController::index
 * @see app/Http/Controllers/CourseController.php:35
 * @route '/kurzusok'
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
 * @see \App\Http\Controllers\CourseController::store
 * @see app/Http/Controllers/CourseController.php:79
 * @route '/kurzusok'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/kurzusok',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\CourseController::store
 * @see app/Http/Controllers/CourseController.php:79
 * @route '/kurzusok'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CourseController::store
 * @see app/Http/Controllers/CourseController.php:79
 * @route '/kurzusok'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CourseController::store
 * @see app/Http/Controllers/CourseController.php:79
 * @route '/kurzusok'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CourseController::store
 * @see app/Http/Controllers/CourseController.php:79
 * @route '/kurzusok'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
/**
 * @see \App\Http\Controllers\CourseController::request
 * @see app/Http/Controllers/CourseController.php:53
 * @route '/kurzusok/{course}/jelentkezes'
 */
export const request = (
    args:
        | { course: number | { id: number } }
        | [course: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: request.url(args, options),
    method: 'post',
});

request.definition = {
    methods: ['post'],
    url: '/kurzusok/{course}/jelentkezes',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\CourseController::request
 * @see app/Http/Controllers/CourseController.php:53
 * @route '/kurzusok/{course}/jelentkezes'
 */
request.url = (
    args:
        | { course: number | { id: number } }
        | [course: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { course: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { course: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            course: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        course: typeof args.course === 'object' ? args.course.id : args.course,
    };

    return (
        request.definition.url
            .replace('{course}', parsedArgs.course.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\CourseController::request
 * @see app/Http/Controllers/CourseController.php:53
 * @route '/kurzusok/{course}/jelentkezes'
 */
request.post = (
    args:
        | { course: number | { id: number } }
        | [course: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: request.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CourseController::request
 * @see app/Http/Controllers/CourseController.php:53
 * @route '/kurzusok/{course}/jelentkezes'
 */
const requestForm = (
    args:
        | { course: number | { id: number } }
        | [course: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: request.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CourseController::request
 * @see app/Http/Controllers/CourseController.php:53
 * @route '/kurzusok/{course}/jelentkezes'
 */
requestForm.post = (
    args:
        | { course: number | { id: number } }
        | [course: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: request.url(args, options),
    method: 'post',
});

request.form = requestForm;
/**
 * @see \App\Http\Controllers\CourseController::review
 * @see app/Http/Controllers/CourseController.php:95
 * @route '/kurzusjelentkezesek/{enrollment}/elbiras'
 */
export const review = (
    args:
        | { enrollment: number | { id: number } }
        | [enrollment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
});

review.definition = {
    methods: ['patch'],
    url: '/kurzusjelentkezesek/{enrollment}/elbiras',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\CourseController::review
 * @see app/Http/Controllers/CourseController.php:95
 * @route '/kurzusjelentkezesek/{enrollment}/elbiras'
 */
review.url = (
    args:
        | { enrollment: number | { id: number } }
        | [enrollment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { enrollment: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { enrollment: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            enrollment: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        enrollment:
            typeof args.enrollment === 'object'
                ? args.enrollment.id
                : args.enrollment,
    };

    return (
        review.definition.url
            .replace('{enrollment}', parsedArgs.enrollment.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\CourseController::review
 * @see app/Http/Controllers/CourseController.php:95
 * @route '/kurzusjelentkezesek/{enrollment}/elbiras'
 */
review.patch = (
    args:
        | { enrollment: number | { id: number } }
        | [enrollment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\CourseController::review
 * @see app/Http/Controllers/CourseController.php:95
 * @route '/kurzusjelentkezesek/{enrollment}/elbiras'
 */
const reviewForm = (
    args:
        | { enrollment: number | { id: number } }
        | [enrollment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: review.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CourseController::review
 * @see app/Http/Controllers/CourseController.php:95
 * @route '/kurzusjelentkezesek/{enrollment}/elbiras'
 */
reviewForm.patch = (
    args:
        | { enrollment: number | { id: number } }
        | [enrollment: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: review.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        },
    }),
    method: 'post',
});

review.form = reviewForm;
const CourseController = { publicIndex, index, store, request, review };

export default CourseController;
