import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
    applyUrlDefaults,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\AdminController::review
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
export const review = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
});

review.definition = {
    methods: ['patch'],
    url: '/admin/kerelmek/{memberRequest}',
} satisfies RouteDefinition<['patch']>;

/**
 * @see \App\Http\Controllers\AdminController::review
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
review.url = (
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
        review.definition.url
            .replace('{memberRequest}', parsedArgs.memberRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\AdminController::review
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
review.patch = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
        | number
        | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\AdminController::review
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
const reviewForm = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
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
 * @see \App\Http\Controllers\AdminController::review
 * @see app/Http/Controllers/AdminController.php:299
 * @route '/admin/kerelmek/{memberRequest}'
 */
reviewForm.patch = (
    args:
        | { memberRequest: number | { id: number } }
        | [memberRequest: number | { id: number }]
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
const requests = {
    review: Object.assign(review, review),
};

export default requests;
