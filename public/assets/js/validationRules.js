Vue.use(VeeValidate);
Vue.component('ValidationProvider', VeeValidate.ValidationProvider);

function validPhone(phone){
    const phoneData = phone.match(/^[\+]{0,1}(?:998)?[\s-]*[\(]{0,1}([0-9]{2})[\)]{0,1}[\s-]*([0-9]{3})[\s-]*([0-9]{2})[\s-]*([0-9]{2})$/)
    return phoneData ? `998${phoneData.shift() && phoneData.join('')}` : null
}

VeeValidate.extend('phone', {
    validate: value => validPhone(value) !== null,
    message: i18n.buyer.validations.invalid_phone_number,
});

VeeValidate.extend('guarantPhoneMatch', {
    params: ['target'],
    validate: (value, {target}) => validPhone(value) !== validPhone(target),
    message: i18n.buyer.validations.guarants_phone_match_error,
});

VeeValidate.extend('guarantPersonalPhone', {
    params: ['target'],
    validate: (value, { target }) => validPhone(value) !== validPhone(target),
    message: i18n.buyer.validations.equal_phone_numbers,
});

VeeValidate.extend('min', {
    // ...VeeValidateRules.min,
    params: ['target'],
    validate: (value, { target }) => value.length == target,
    message: ((_, { target }) => `Минимальное количество символов ${target}`),
});

VeeValidate.extend('max', {
    params: ['target'],
    validate: (value, { target }) => value.length <= target,
    message: ((_, { target }) => `Максимальное количество символов ${target}`),
});

VeeValidate.extend('required', {
    ...VeeValidateRules.required,
    message: i18n.buyer.validations.required,
});

VeeValidate.extend('ext', {
    ...VeeValidateRules.ext,
    message: i18n.buyer.validations.img_error,
});
