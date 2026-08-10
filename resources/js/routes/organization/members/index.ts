import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\OrganizationController::assign
 * @see app/Http/Controllers/OrganizationController.php:85
 * @route '/szervezet/team-tagsag'
 */
export const assign = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: assign.url(options),
    method: 'post',
});

assign.definition = {
    methods: ['post'],
    url: '/szervezet/team-tagsag',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\OrganizationController::assign
 * @see app/Http/Controllers/OrganizationController.php:85
 * @route '/szervezet/team-tagsag'
 */
assign.url = (options?: RouteQueryOptions) => {
    return assign.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\OrganizationController::assign
 * @see app/Http/Controllers/OrganizationController.php:85
 * @route '/szervezet/team-tagsag'
 */
assign.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: assign.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\OrganizationController::assign
 * @see app/Http/Controllers/OrganizationController.php:85
 * @route '/szervezet/team-tagsag'
 */
const assignForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: assign.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\OrganizationController::assign
 * @see app/Http/Controllers/OrganizationController.php:85
 * @route '/szervezet/team-tagsag'
 */
assignForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: assign.url(options),
    method: 'post',
});

assign.form = assignForm;
const members = {
    assign: Object.assign(assign, assign),
};

export default members;
