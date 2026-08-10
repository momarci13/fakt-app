import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\OrganizationController::store
 * @see app/Http/Controllers/OrganizationController.php:102
 * @route '/szervezet/projektek'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/szervezet/projektek',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\OrganizationController::store
 * @see app/Http/Controllers/OrganizationController.php:102
 * @route '/szervezet/projektek'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\OrganizationController::store
 * @see app/Http/Controllers/OrganizationController.php:102
 * @route '/szervezet/projektek'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\OrganizationController::store
 * @see app/Http/Controllers/OrganizationController.php:102
 * @route '/szervezet/projektek'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\OrganizationController::store
 * @see app/Http/Controllers/OrganizationController.php:102
 * @route '/szervezet/projektek'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;
const projects = {
    store: Object.assign(store, store),
};

export default projects;
