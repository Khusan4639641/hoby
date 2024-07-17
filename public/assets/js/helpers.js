function routeToJs(url, params) {
    if(url.replace(/\s/g, "").length == 0 || Object.keys(params).length == 0) return '';
    let route = url
    for (const key in params) {
        if (params.hasOwnProperty(key)) route = route.replace(`%${key}%`, params[key])
    }
    return route
}
