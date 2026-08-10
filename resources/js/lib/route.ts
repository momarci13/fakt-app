export type HttpMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

export type RouteDefinition = {
    url: string;
    method: HttpMethod;
};

export type RouteFunction = (() => RouteDefinition) & {
    form: () => { action: string; method: HttpMethod };
};

export const makeRoute = (
    url: string,
    method: HttpMethod = 'get',
): RouteFunction => {
    const route = (() => ({ url, method })) as RouteFunction;
    route.form = () => ({ action: url, method });

    return route;
};
