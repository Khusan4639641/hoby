Vue.config.devtools = true;
//TODO: Добавить динамики с определением языка
function translit ( str ) {
    let ru = {
        'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
        'е': 'e', 'ё': 'e', 'ж': 'j', 'з': 'z', 'и': 'i',
        'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
        'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
        'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch', 'ш': 'sh',
        'щ': 'shch', 'ы': 'y', 'э': 'e', 'ю': 'u', 'я': 'ya'
    }, n_str = [];

    str = str.toLowerCase();
    str = str.replace(/[\s,.:;!?]/g, '-')
    str = str.replace(/[ъь]+/g, '').replace(/й/g, 'i');

    for ( let i = 0; i < str.length; ++i ) {
        n_str.push(
            ru[ str[i] ]
            || ru[ str[i].toLowerCase() ] == undefined && str[i]
            || ru[ str[i].toLowerCase() ].replace(/^(.)/, function ( match ) { return match.toUpperCase() })
        );
    }

    return n_str.join('');
}


function parseErrors (response) {

    let objErrors = response.data.response.errors,
        objMessages = response.data.response.message,
        errors = [];

    if(response.data.status === "error"){

        Object.keys(objErrors).map(function(objectKey, index) {
            let arr = objErrors[objectKey];
            if(arr.length > 0){
                arr.forEach(element => (errors.push(element)));
            }
        });

        objMessages.forEach(element => (errors.push(element.text)));
    }

    return errors;
}

function copy(o) {
    if (o === null) return null;

    let output, v, key;
    output = Array.isArray(o) ? [] : {};
    for (key in o) {
        v = o[key];
        output[key] = (typeof v === "object") ? copy(v) : v;
    }
    return output;
}
